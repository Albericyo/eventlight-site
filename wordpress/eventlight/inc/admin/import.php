<?php
/**
 * Import du contenu livré avec le thème : formules, catalogue, réalisations, photos, pages.
 *
 * L'import n'écrase rien. Chaque élément porte une clé (« _el_cle ») : s'il existe déjà, il est
 * laissé tel quel, modifié ou non. On peut donc relancer l'import après une interruption.
 * Il avance par petites étapes, pour ne pas dépasser le temps accordé par l'hébergeur.
 */

defined( 'ABSPATH' ) || exit;

/** Le contenu à importer, lu dans import/contenu.json. */
function el_import_donnees() {
	static $donnees = null;
	if ( null === $donnees ) {
		$fichier = get_theme_file_path( 'import/contenu.json' );
		$donnees = is_readable( $fichier ) ? json_decode( (string) file_get_contents( $fichier ), true ) : false;
		if ( ! is_array( $donnees ) ) {
			$donnees = false;
		}
	}
	return $donnees;
}

/** Vrai si les photos sont livrées avec le thème. Sinon, elles sont téléchargées. */
function el_import_photos_locales() {
	return is_dir( get_theme_file_path( 'import/img' ) );
}

/** Toutes les photos à importer : clé => description, avec le titre et le rang dans leur contenu. */
function el_import_photos() {
	static $photos = null;
	if ( null !== $photos ) {
		return $photos;
	}
	$photos  = array();
	$donnees = el_import_donnees();
	if ( ! $donnees ) {
		return $photos;
	}
	foreach ( array( 'produits' => 'nom', 'realisations' => 'titre' ) as $groupe => $champ_titre ) {
		foreach ( $donnees[ $groupe ] as $contenu ) {
			foreach ( $contenu['images'] as $i => $image ) {
				$image['titre']          = $contenu[ $champ_titre ] . ', photo ' . ( $i + 1 );
				$photos[ $image['cle'] ] = $image;
			}
		}
	}
	return $photos;
}

/** La liste ordonnée des étapes. */
function el_import_etapes() {
	$donnees = el_import_donnees();
	if ( ! $donnees ) {
		return array();
	}
	$etapes = array( 'types', 'categories' );
	foreach ( array_keys( el_import_photos() ) as $cle ) {
		$etapes[] = 'photo:' . $cle;
	}
	foreach ( $donnees['formules'] as $f ) {
		$etapes[] = 'formule:' . $f['slug'];
	}
	foreach ( $donnees['produits'] as $p ) {
		$etapes[] = 'produit:' . $p['slug'];
	}
	foreach ( $donnees['realisations'] as $r ) {
		$etapes[] = 'realisation:' . $r['slug'];
	}
	$etapes[] = 'questions';
	$etapes[] = 'pages';
	$etapes[] = 'reglages';
	$etapes[] = 'fin';
	return $etapes;
}

/** Les remarques à montrer à la fin de l'import. Avec un argument, en ajoute une. */
function el_import_note( $texte = null ) {
	static $notes = array();
	if ( null !== $texte ) {
		$notes[] = (string) $texte;
	}
	return $notes;
}

/** L'élément déjà importé qui porte cette clé, corbeille comprise. 0 s'il n'y en a pas. */
function el_import_trouver( $cle, $type ) {
	$ids = get_posts(
		array(
			'post_type'        => $type,
			'post_status'      => array( 'publish', 'draft', 'pending', 'private', 'future', 'inherit', 'trash' ),
			'numberposts'      => 1,
			'fields'           => 'ids',
			'meta_key'         => '_el_cle', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_value'       => $cle, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			'suppress_filters' => true,
		)
	);
	return $ids ? (int) $ids[0] : 0;
}

/**
 * Copie un fichier image depuis le thème, ou le télécharge quand le thème est livré sans les photos.
 * Le fichier est écrit sous un nom provisoire, puis renommé : une coupure ne laisse pas d'image tronquée.
 */
function el_import_fichier( $chemin, $destination ) {
	$provisoire = $destination . '.part';
	$local      = get_theme_file_path( 'import/img/' . $chemin );
	if ( is_readable( $local ) ) {
		return copy( $local, $provisoire ) && rename( $provisoire, $destination );
	}
	$donnees = el_import_donnees();
	$source  = isset( $donnees['images'] ) ? $donnees['images'] : array();
	if ( empty( $source['depot'] ) || empty( $source['versions'] ) ) {
		return false;
	}
	foreach ( $source['versions'] as $version ) {
		$adresse = 'https://raw.githubusercontent.com/' . $source['depot'] . '/' . rawurlencode( $version ) . '/' . $source['dossier'] . '/' . $chemin;
		$reponse = wp_remote_get(
			$adresse,
			array(
				'timeout'  => 25,
				'stream'   => true,
				'filename' => $provisoire,
			)
		);
		if ( ! is_wp_error( $reponse ) && 200 === wp_remote_retrieve_response_code( $reponse ) && file_exists( $provisoire ) && filesize( $provisoire ) > 100 ) {
			return rename( $provisoire, $destination );
		}
		if ( file_exists( $provisoire ) ) {
			wp_delete_file( $provisoire );
		}
	}
	return false;
}

/** Importe une photo dans la médiathèque, avec toutes ses tailles. */
function el_import_photo( $cle ) {
	$photos = el_import_photos();
	if ( ! isset( $photos[ $cle ] ) ) {
		return 'Photo inconnue : ' . $cle;
	}
	if ( el_import_trouver( 'photo:' . $cle, 'attachment' ) ) {
		return '';
	}
	$photo   = $photos[ $cle ];
	$envois  = wp_upload_dir();
	$relatif = 'eventlight/' . dirname( $cle );
	$dossier = trailingslashit( $envois['basedir'] ) . $relatif;
	if ( ! wp_mkdir_p( $dossier ) ) {
		return 'Impossible de créer le dossier ' . $relatif . ' dans wp-content/uploads.';
	}
	$nom      = basename( $cle );
	$largeurs = $photo['largeurs'];
	sort( $largeurs );
	$fichiers = array();
	foreach ( $largeurs as $l ) {
		$fichier = $nom . '-' . $l . '.webp';
		if ( ! file_exists( $dossier . '/' . $fichier ) && ! el_import_fichier( $cle . '-' . $l . '.webp', $dossier . '/' . $fichier ) ) {
			return 'Photo introuvable : ' . $cle . '-' . $l . '.webp';
		}
		$dimensions = function_exists( 'getimagesize' ) ? @getimagesize( $dossier . '/' . $fichier ) : false; // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		$fichiers[] = array(
			'file'      => $fichier,
			'width'     => $dimensions ? (int) $dimensions[0] : (int) $l,
			'height'    => $dimensions ? (int) $dimensions[1] : (int) round( $l * $photo['h'] / $photo['w'] ),
			'mime-type' => 'image/webp',
			'filesize'  => (int) filesize( $dossier . '/' . $fichier ),
		);
	}
	$grande = array_pop( $fichiers );
	$id     = wp_insert_attachment(
		array(
			'post_title'     => $photo['titre'],
			'post_mime_type' => 'image/webp',
			'post_status'    => 'inherit',
		),
		$dossier . '/' . $grande['file'],
		0,
		true
	);
	if ( is_wp_error( $id ) ) {
		return 'Photo non enregistrée : ' . $cle . ' (' . $id->get_error_message() . ')';
	}
	$tailles = array();
	foreach ( $fichiers as $i => $f ) {
		$tailles[ 'el-' . $f['width'] ] = $f;
		if ( 0 === $i ) {
			// La plus petite version sert aussi de vignette dans l'administration.
			$tailles['medium'] = $f;
		}
	}
	wp_update_attachment_metadata(
		$id,
		array(
			'width'      => $grande['width'],
			'height'     => $grande['height'],
			'file'       => $relatif . '/' . $grande['file'],
			'filesize'   => $grande['filesize'],
			'sizes'      => $tailles,
			'image_meta' => array(),
		)
	);
	update_post_meta( $id, '_el_cle', 'photo:' . $cle );
	update_post_meta( $id, '_el_fond', $photo['fond'] );
	update_post_meta( $id, '_el_orig', (int) $photo['w'] . 'x' . (int) $photo['h'] );
	return '';
}

/** Les identifiants des photos importées d'un contenu, dans l'ordre. */
function el_import_galerie( $images ) {
	$ids = array();
	foreach ( $images as $image ) {
		$id = el_import_trouver( 'photo:' . $image['cle'], 'attachment' );
		if ( $id ) {
			$ids[] = $id;
		}
	}
	return $ids;
}

/** Crée un contenu du thème s'il n'existe pas encore. Renvoie son identifiant, ou 0 s'il existait déjà. */
function el_import_creer( $type, $cle, $slug, $titre, $ordre ) {
	if ( el_import_trouver( $cle, $type ) || ( '' !== $slug && get_page_by_path( $slug, OBJECT, $type ) ) ) {
		return 0;
	}
	$id = wp_insert_post(
		array(
			'post_type'   => $type,
			'post_status' => 'publish',
			'post_title'  => wp_slash( $titre ),
			'post_name'   => $slug,
			'menu_order'  => $ordre,
		),
		true
	);
	if ( is_wp_error( $id ) ) {
		return $id;
	}
	update_post_meta( $id, '_el_cle', $cle );
	return $id;
}

/** Enregistre des valeurs sur un contenu. */
function el_import_metas( $id, $metas ) {
	foreach ( $metas as $cle => $valeur ) {
		if ( '' === $valeur || null === $valeur || array() === $valeur ) {
			continue;
		}
		update_post_meta( $id, '_el_' . $cle, is_string( $valeur ) ? wp_slash( $valeur ) : $valeur );
	}
}

/** L'identifiant d'un contenu importé, retrouvé par son nom dans l'adresse. */
function el_import_id( $slug, $type ) {
	$post = get_page_by_path( $slug, OBJECT, $type );
	return $post ? (int) $post->ID : 0;
}

/**
 * Exécute une étape.
 *
 * @return string Message d'erreur, ou vide si tout s'est bien passé.
 */
function el_import_etape( $etape ) {
	$donnees = el_import_donnees();
	$parties = explode( ':', $etape, 2 );
	$genre   = $parties[0];
	$cible   = isset( $parties[1] ) ? $parties[1] : '';

	switch ( $genre ) {
		case 'types':
			foreach ( $donnees['types'] as $nom ) {
				if ( '' !== $nom && ! term_exists( $nom, 'el_type' ) ) {
					wp_insert_term( $nom, 'el_type' );
				}
			}
			return '';

		case 'categories':
			foreach ( $donnees['categories'] as $i => $c ) {
				if ( get_term_by( 'slug', $c['slug'], 'el_categorie' ) ) {
					continue;
				}
				$terme = wp_insert_term(
					$c['nom'],
					'el_categorie',
					array(
						'slug'        => $c['slug'],
						'description' => $c['intro'],
					)
				);
				if ( is_wp_error( $terme ) ) {
					return 'Catégorie non créée : ' . $c['nom'] . ' (' . $terme->get_error_message() . ')';
				}
				update_term_meta( $terme['term_id'], 'el_h1', wp_slash( $c['h1'] ) );
				update_term_meta( $terme['term_id'], 'el_seo_titre', wp_slash( $c['seoTitle'] ) );
				update_term_meta( $terme['term_id'], 'el_seo_description', wp_slash( $c['seoDescription'] ) );
				update_term_meta( $terme['term_id'], 'el_ordre', ( $i + 1 ) * 10 );
			}
			return '';

		case 'photo':
			return el_import_photo( $cible );

		case 'formule':
			foreach ( $donnees['formules'] as $i => $f ) {
				if ( $f['slug'] !== $cible ) {
					continue;
				}
				$id = el_import_creer( 'el_formule', 'formule:' . $f['slug'], $f['slug'], $f['titre'], ( $i + 1 ) * 10 );
				if ( is_wp_error( $id ) ) {
					return 'Formule non créée : ' . $f['titre'];
				}
				if ( ! $id ) {
					return '';
				}
				$types = array();
				foreach ( $f['types'] as $nom ) {
					$terme = get_term_by( 'name', $nom, 'el_type' );
					if ( $terme ) {
						$types[] = (int) $terme->term_id;
					}
				}
				el_import_metas(
					$id,
					array(
						'accroche'        => $f['accroche'],
						'h1'              => $f['h1'],
						'description'     => $f['description'],
						'seo_titre'       => $f['seo_titre'],
						'seo_description' => $f['seo_description'],
						'devis_type'      => $f['devis_type'],
						'types'           => $types,
						'options'         => $f['options'],
					)
				);
				update_post_meta( $id, '_el_packs', wp_slash( $f['packs'] ) );
			}
			return '';

		case 'produit':
			foreach ( $donnees['produits'] as $i => $p ) {
				if ( $p['slug'] !== $cible ) {
					continue;
				}
				$id = el_import_creer( 'el_produit', 'produit:' . $p['slug'], $p['slug'], $p['nom'], ( $i + 1 ) * 10 );
				if ( is_wp_error( $id ) ) {
					return 'Produit non créé : ' . $p['nom'];
				}
				if ( ! $id ) {
					return '';
				}
				$categorie = get_term_by( 'name', $p['categorie'], 'el_categorie' );
				if ( $categorie ) {
					wp_set_object_terms( $id, (int) $categorie->term_id, 'el_categorie' );
				}
				$usages = array();
				foreach ( $p['usages'] as $slug ) {
					$formule = el_import_id( $slug, 'el_formule' );
					if ( $formule ) {
						$usages[] = $formule;
					}
				}
				$galerie = el_import_galerie( $p['images'] );
				el_import_metas(
					$id,
					array(
						'prix'        => $p['prix'],
						'description' => $p['description'],
						'details'     => $p['details'],
						'usages'      => $usages,
						'youtube'     => $p['youtube'],
						'galerie'     => $galerie,
					)
				);
				if ( null !== $p['stock'] ) {
					update_post_meta( $id, '_el_stock', (string) (int) $p['stock'] );
				}
				foreach ( $galerie as $photo ) {
					wp_update_post(
						array(
							'ID'          => $photo,
							'post_parent' => $id,
						)
					);
				}
			}
			return '';

		case 'realisation':
			foreach ( $donnees['realisations'] as $i => $r ) {
				if ( $r['slug'] !== $cible ) {
					continue;
				}
				$id = el_import_creer( 'el_realisation', 'realisation:' . $r['slug'], $r['slug'], $r['titre'], ( $i + 1 ) * 10 );
				if ( is_wp_error( $id ) ) {
					return 'Réalisation non créée : ' . $r['titre'];
				}
				if ( ! $id ) {
					return '';
				}
				$type = '' !== $r['type'] ? get_term_by( 'name', $r['type'], 'el_type' ) : null;
				if ( $type ) {
					wp_set_object_terms( $id, (int) $type->term_id, 'el_type' );
				}
				$galerie = el_import_galerie( $r['images'] );
				el_import_metas(
					$id,
					array(
						'sous_titre'  => $r['sous_titre'],
						'annee'       => $r['annee'],
						'texte'       => $r['texte'],
						'production'  => $r['production'],
						'credits'     => $r['credits'],
						'logiciel'    => $r['logiciel'],
						'technologie' => $r['technologie'],
						'youtube'     => $r['youtube'],
						'galerie'     => $galerie,
					)
				);
				if ( $r['couverture'] && isset( $galerie[ $r['couverture'] - 1 ] ) ) {
					set_post_thumbnail( $id, $galerie[ $r['couverture'] - 1 ] );
				}
				foreach ( $galerie as $photo ) {
					wp_update_post(
						array(
							'ID'          => $photo,
							'post_parent' => $id,
						)
					);
				}
			}
			return '';

		case 'questions':
			foreach ( $donnees['questions'] as $i => $q ) {
				$id = el_import_creer( 'el_question', 'question:' . md5( $q['q'] ), '', $q['q'], ( $i + 1 ) * 10 );
				if ( $id && ! is_wp_error( $id ) ) {
					el_import_metas( $id, array( 'reponse' => $q['a'] ) );
				}
			}
			return '';

		case 'pages':
			foreach ( $donnees['pages'] as $p ) {
				$chemin = ( '' !== $p['parent'] ? $p['parent'] . '/' : '' ) . $p['slug'];
				$existante = get_page_by_path( $chemin );
				if ( $existante ) {
					// Une page de ce nom existait avant l'import : on n'y touche pas, mais on le dit.
					if ( 'page:' . $chemin !== get_post_meta( $existante->ID, '_el_cle', true ) ) {
						el_import_note( 'La page « ' . $chemin . ' » existait déjà : son contenu n\'a pas été modifié. Elle s\'affiche désormais avec la mise en page du thème.' );
					}
					continue;
				}
				if ( el_import_trouver( 'page:' . $chemin, 'page' ) ) {
					continue;
				}
				$parent = '' !== $p['parent'] ? get_page_by_path( $p['parent'] ) : null;
				$id     = wp_insert_post(
					array(
						'post_type'    => 'page',
						'post_status'  => 'publish',
						'post_title'   => wp_slash( $p['titre'] ),
						'post_name'    => $p['slug'],
						'post_content' => wp_slash( $p['contenu'] ),
						'post_parent'  => $parent ? $parent->ID : 0,
					),
					true
				);
				if ( is_wp_error( $id ) ) {
					return 'Page non créée : ' . $p['titre'];
				}
				update_post_meta( $id, '_el_cle', 'page:' . $chemin );
				update_post_meta( $id, '_wp_page_template', 'page-' . $p['slug'] . '.php' );
				el_import_metas(
					$id,
					array(
						'seo_titre'       => $p['seo_titre'],
						'seo_description' => $p['seo_description'],
						'noindex'         => $p['noindex'] ? 1 : '',
					)
				);
			}
			return '';

		case 'reglages':
			$reglages = get_option( 'el_reglages', array() );
			$reglages = is_array( $reglages ) ? $reglages : array();
			if ( empty( $reglages['scenes'] ) ) {
				$scenes = array();
				foreach ( $donnees['accueil']['scenes'] as $s ) {
					foreach ( $donnees['realisations'] as $r ) {
						if ( $r['slug'] === $s['realisation'] && isset( $r['images'][ $s['image'] ] ) ) {
							$image = el_import_trouver( 'photo:' . $r['images'][ $s['image'] ]['cle'], 'attachment' );
							if ( $image ) {
								$scenes[] = array(
									'image'       => $image,
									'label'       => $s['label'],
									'realisation' => el_import_id( $r['slug'], 'el_realisation' ),
									'cadrage'     => $s['cadrage'],
									'legende'     => $s['legende'],
								);
							}
						}
					}
				}
				if ( $scenes ) {
					$reglages['scenes'] = $scenes;
				}
			}
			if ( empty( $reglages['accueil_realisations'] ) ) {
				$reglages['accueil_realisations'] = array_values( array_filter( array_map( 'el_import_id', $donnees['accueil']['realisations'], array_fill( 0, count( $donnees['accueil']['realisations'] ), 'el_realisation' ) ) ) );
			}
			if ( empty( $reglages['mapping_vedette'] ) ) {
				$reglages['mapping_vedette'] = el_import_id( $donnees['accueil']['mapping_vedette'], 'el_realisation' );
			}
			update_option( 'el_reglages', $reglages );
			return '';

		case 'fin':
			flush_rewrite_rules();
			update_option( 'el_import_fait', time(), false );
			return '';
	}
	return 'Étape inconnue : ' . $etape;
}

/** Fait avancer l'import de quelques étapes, pendant six secondes au plus. */
function el_ajax_import() {
	check_ajax_referer( 'el_admin', 'jeton' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => 'Vous n\'avez pas le droit de lancer l\'import.' ) );
	}
	$etapes = el_import_etapes();
	if ( ! $etapes ) {
		wp_send_json_error( array( 'message' => 'Le fichier import/contenu.json du thème est introuvable ou illisible.' ) );
	}
	$rang = isset( $_POST['rang'] ) ? absint( $_POST['rang'] ) : 0;

	// Les adresses lisibles sont nécessaires au thème : on les active à la demande, avant de commencer.
	if ( 0 === $rang && ! empty( $_POST['permaliens'] ) && ! get_option( 'permalink_structure' ) ) {
		global $wp_rewrite;
		$wp_rewrite->set_permalink_structure( '/%postname%/' );
		flush_rewrite_rules();
	}

	if ( function_exists( 'set_time_limit' ) ) {
		@set_time_limit( 60 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, Squiz.PHP.DiscouragedFunctions.Discouraged
	}
	$depart  = microtime( true );
	$total   = count( $etapes );
	$erreurs = array();
	while ( $rang < $total && microtime( true ) - $depart < 6 ) {
		$erreur = el_import_etape( $etapes[ $rang ] );
		if ( '' !== $erreur ) {
			$erreurs[] = $erreur;
		}
		++$rang;
	}
	wp_send_json_success(
		array(
			'rang'    => $rang,
			'total'   => $total,
			'fini'    => $rang >= $total,
			'erreurs' => $erreurs,
			'notes'   => el_import_note(),
		)
	);
}
add_action( 'wp_ajax_el_import', 'el_ajax_import' );

function el_page_import() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$donnees = el_import_donnees();
	echo '<div class="wrap el-import"><h1>Importer le contenu</h1>';
	if ( ! $donnees ) {
		echo '<div class="notice notice-error inline"><p>Le contenu à importer est introuvable. Le thème a été installé sans son dossier « import ».</p></div></div>';
		return;
	}
	$deja = array(
		'formules'     => (int) wp_count_posts( 'el_formule' )->publish,
		'produits'     => (int) wp_count_posts( 'el_produit' )->publish,
		'realisations' => (int) wp_count_posts( 'el_realisation' )->publish,
	);
	?>
	<p class="el-import-intro">Cette étape met en place le contenu du site : <?php echo count( $donnees['formules'] ); ?> formules, <?php echo count( $donnees['produits'] ); ?> produits, <?php echo count( $donnees['realisations'] ); ?> réalisations, <?php echo count( el_import_photos() ); ?> photos, <?php echo count( $donnees['questions'] ); ?> questions fréquentes et <?php echo count( $donnees['pages'] ); ?> pages (devis, contact, vidéo mapping, mentions légales, charte).</p>
	<p>Rien de ce qui existe déjà dans votre WordPress n'est modifié ni supprimé. Vous pouvez relancer l'import sans risque : ce qui est déjà là reste tel quel.</p>
	<?php if ( array_sum( $deja ) ) : ?>
	<p>Déjà en place : <?php echo (int) $deja['formules']; ?> formules, <?php echo (int) $deja['produits']; ?> produits, <?php echo (int) $deja['realisations']; ?> réalisations.</p>
	<?php endif; ?>
	<?php if ( ! el_import_photos_locales() ) : ?>
	<p>Cette version du thème est livrée sans les photos : elles seront téléchargées pendant l'import (19 Mo environ), ce qui prend quelques minutes.</p>
	<?php endif; ?>
	<?php if ( ! get_option( 'permalink_structure' ) ) : ?>
	<p><label><input type="checkbox" id="el-import-permaliens" checked> Activer les adresses lisibles (réglage « Titre de la publication » des permaliens). Le thème en a besoin : sans elles, ses liens ne mènent nulle part.</label></p>
	<?php endif; ?>

	<p><button type="button" class="button button-primary button-hero" id="el-import-lancer">Importer le contenu</button></p>
	<div class="el-import-suivi" hidden>
		<progress id="el-import-barre" max="100" value="0"></progress>
		<p id="el-import-etat" role="status"></p>
		<ul id="el-import-erreurs"></ul>
			<ul id="el-import-notes"></ul>
	</div>
	<div class="el-import-fin" hidden>
		<h2>C'est en place</h2>
		<p><a class="button button-primary" href="<?php echo esc_url( home_url( '/' ) ); ?>">Voir le site</a></p>
		<p>À faire ensuite :</p>
		<ol>
			<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=eventlight&onglet=devis' ) ); ?>">Réglages, onglet Devis et mentions</a> : l'adresse qui reçoit les demandes, le nom de l'hébergeur et le directeur de la publication.</li>
			<li><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=el_produit&page=el-stock' ) ); ?>">Location, Stock</a> : la quantité de chaque produit, pour que le planning calcule les disponibilités.</li>
			<li>Envoyer une demande de devis depuis le site, pour vérifier que l'e-mail arrive.</li>
		</ol>
	</div>
</div>
	<?php
}
