<?php
/**
 * Fiches de l'administration : formules, produits, réalisations, questions, catégories.
 */

defined( 'ABSPATH' ) || exit;

/** Les champs de chaque type de contenu. La clé est aussi le nom de la valeur enregistrée (« _el_cle »). */
function el_fiches() {
	$types = array();
	$terms = get_terms(
		array(
			'taxonomy'   => 'el_type',
			'hide_empty' => false,
		)
	);
	foreach ( is_wp_error( $terms ) ? array() : $terms as $t ) {
		$types[ $t->term_id ] = $t->name;
	}
	$formules = array();
	foreach ( el_contenus( 'el_formule' ) as $post ) {
		$formules[ $post->ID ] = get_the_title( $post );
	}
	$evenements = array();
	foreach ( el_types_evenement() as $type ) {
		$evenements[ $type ] = $type;
	}

	return array(
		'el_formule'     => array(
			'titre'  => 'La formule',
			'champs' => array(
				'accroche'    => array(
					'type'  => 'texte',
					'label' => 'Accroche',
					'aide'  => 'Une phrase, affichée dans la liste des formules.',
				),
				'h1'          => array(
					'type'  => 'texte',
					'label' => 'Grand titre de la page',
					'aide'  => 'Vide : le nom de la formule.',
				),
				'description' => array(
					'type'  => 'zone',
					'label' => 'Introduction',
				),
				'devis_type'  => array(
					'type'  => 'choix',
					'label' => 'Type d\'événement coché dans le formulaire de devis',
					'choix' => $evenements,
				),
				'types'       => array(
					'type'  => 'cases',
					'label' => 'Réalisations montrées sur la page',
					'aide'  => 'Les réalisations de ces types s\'affichent sous « Déjà fait ».',
					'choix' => $types,
					'vide'  => 'Aucun type de réalisation n\'existe encore.',
				),
				'options'     => array(
					'type'  => 'lignes',
					'label' => 'En option, au devis',
					'aide'  => 'Une ligne par option.',
				),
			),
		),
		'el_produit'     => array(
			'titre'  => 'Le produit',
			'champs' => array(
				'prix'        => array(
					'type'    => 'texte',
					'label'   => 'Tarif',
					'aide'    => 'Comme vous l\'écririez : « 40,00 €/j », ou « 110,00 €/j (la paire) ».',
					'court'   => true,
					'exemple' => '40,00 €/j',
				),
				'stock'       => array(
					'type'  => 'nombre',
					'label' => 'Quantité en stock',
					'aide'  => 'Le nombre d\'exemplaires que vous pouvez sortir. Vide : le produit n\'est pas suivi dans le planning.',
				),
				'description' => array(
					'type'  => 'zone',
					'label' => 'Description',
				),
				'details'     => array(
					'type'    => 'lignes',
					'label'   => 'Caractéristiques',
					'aide'    => 'Une ligne par caractéristique.',
					'rangees' => 8,
				),
				'usages'      => array(
					'type'  => 'cases',
					'label' => 'On le sort souvent pour',
					'choix' => $formules,
					'vide'  => 'Aucune formule n\'existe encore.',
				),
				'youtube'     => array(
					'type'  => 'texte',
					'label' => 'Vidéo de démonstration',
					'aide'  => 'L\'adresse de la vidéo sur YouTube. Elle ne se charge qu\'au clic du visiteur.',
				),
				'galerie'     => array(
					'type'  => 'photos',
					'label' => 'Photos',
					'aide'  => 'Faites glisser les photos pour les ordonner. La première détourée sur fond blanc sert de couverture ; sans photo, le pictogramme de la catégorie s\'affiche.',
				),
			),
		),
		'el_realisation' => array(
			'titre'  => 'La réalisation',
			'champs' => array(
				'sous_titre'  => array(
					'type'  => 'texte',
					'label' => 'Sous-titre',
					'aide'  => 'Le lieu et la date, par exemple.',
				),
				'annee'       => array(
					'type'  => 'texte',
					'label' => 'Année',
					'court' => true,
				),
				'texte'       => array(
					'type'    => 'zone',
					'label'   => 'Présentation',
					'rangees' => 5,
				),
				'production'  => array(
					'type'  => 'texte',
					'label' => 'Production',
				),
				'credits'     => array(
					'type'  => 'lignes',
					'label' => 'Crédits',
					'aide'  => 'Une ligne par crédit, sous la forme « Rôle : Nom ».',
				),
				'logiciel'    => array(
					'type'  => 'texte',
					'label' => 'Logiciels',
				),
				'technologie' => array(
					'type'  => 'texte',
					'label' => 'Matériel',
				),
				'youtube'     => array(
					'type'  => 'texte',
					'label' => 'Vidéo',
					'aide'  => 'L\'adresse de la vidéo sur YouTube. Elle remplace la photo en tête de page.',
				),
				'galerie'     => array(
					'type'  => 'photos',
					'label' => 'Photos',
					'aide'  => 'Faites glisser les photos pour les ordonner. La première sert de couverture, sauf si une image de couverture est choisie dans la colonne de droite.',
				),
			),
		),
		'el_question'    => array(
			'titre'  => 'La réponse',
			'champs' => array(
				'reponse' => array(
					'type'    => 'zone',
					'label'   => 'Réponse',
					'aide'    => 'La question est le titre, au-dessus. Questions et réponses s\'affichent en bas de la page d\'accueil.',
					'rangees' => 6,
				),
			),
		),
	);
}

/** Les champs de référencement, communs aux pages et aux contenus du thème. */
function el_fiche_seo() {
	return array(
		'seo_titre'       => array(
			'type'  => 'texte',
			'label' => 'Titre pour Google',
			'aide'  => 'Vide : le thème en compose un.',
		),
		'seo_description' => array(
			'type'    => 'zone',
			'label'   => 'Description pour Google',
			'rangees' => 3,
		),
		'noindex'         => array(
			'type'    => 'case',
			'label'   => 'Visibilité',
			'libelle' => 'Demander aux moteurs de recherche de ne pas montrer cette page',
		),
	);
}

/** Le jeton de sécurité des fiches, écrit une seule fois par écran. */
function el_fiche_jeton() {
	static $fait = false;
	if ( ! $fait ) {
		$fait = true;
		wp_nonce_field( 'el_fiche', 'el_fiche_jeton' );
	}
}

function el_boites() {
	foreach ( el_fiches() as $type => $fiche ) {
		add_meta_box(
			'el-fiche',
			$fiche['titre'],
			function ( $post ) use ( $fiche ) {
				el_fiche_jeton();
				el_champs_afficher(
					$fiche['champs'],
					function ( $cle ) use ( $post ) {
						$v = get_post_meta( $post->ID, '_el_' . $cle, true );
						return ( 'stock' === $cle && '' === $v ) ? '' : $v;
					}
				);
			},
			$type,
			'normal',
			'high'
		);
	}
	add_meta_box( 'el-packs', 'Les packs', 'el_boite_packs', 'el_formule', 'normal', 'high' );

	foreach ( array( 'page', 'el_formule', 'el_produit', 'el_realisation' ) as $type ) {
		add_meta_box(
			'el-seo',
			'Référencement',
			function ( $post ) {
				el_fiche_jeton();
				el_champs_afficher(
					el_fiche_seo(),
					function ( $cle ) use ( $post ) {
						return get_post_meta( $post->ID, '_el_' . $cle, true );
					},
					'el_seo'
				);
			},
			$type,
			'normal',
			'low'
		);
	}
}
add_action( 'add_meta_boxes', 'el_boites' );

/** Les packs d'une formule : nom, prix, contenu, et le matériel sorti pour ce pack. */
function el_boite_packs( $post ) {
	$packs = get_post_meta( $post->ID, '_el_packs', true );
	$packs = is_array( $packs ) ? array_values( $packs ) : array();
	echo '<p class="description">Quatre packs au plus, du moins cher au plus complet. Le schéma de chaque pack se dessine d\'après ses lignes : « Système son », « 4 totems », « Brouillard », « Étincelles froides », « Fumée lourde », « Animateur », « Micro », « Pupitre », « Geysers », « Éclairage architectural », « Cérémonie ». Un pack sans nom n\'est pas affiché.</p>';
	echo '<div class="el-packs">';
	for ( $i = 0; $i < 4; $i++ ) {
		$p        = isset( $packs[ $i ] ) && is_array( $packs[ $i ] ) ? $packs[ $i ] : array();
		$base     = 'el_packs[' . $i . ']';
		$items    = isset( $p['items'] ) ? implode( "\n", el_lignes( $p['items'] ) ) : '';
		$materiel = el_lignes_materiel( isset( $p['materiel'] ) ? $p['materiel'] : array() );
		echo '<fieldset class="el-pack"><legend>Pack ' . ( $i + 1 ) . '</legend>';
		echo '<div class="el-pack-tete">';
		echo '<label>Nom<input type="text" class="regular-text" name="' . esc_attr( $base . '[nom]' ) . '" value="' . esc_attr( isset( $p['nom'] ) ? $p['nom'] : '' ) . '" placeholder="Pack Essentiel"></label>';
		echo '<label>Prix<input type="text" class="regular-text" name="' . esc_attr( $base . '[prix]' ) . '" value="' . esc_attr( isset( $p['prix'] ) ? $p['prix'] : '' ) . '" placeholder="499 €"></label>';
		echo '<label class="el-pack-reco"><input type="radio" name="el_pack_recommande" value="' . (int) $i . '"' . checked( ! empty( $p['featured'] ), true, false ) . '> Pack recommandé</label>';
		echo '</div>';
		echo '<label>Ce que comprend le pack, une ligne par élément<textarea class="large-text" rows="' . max( 6, substr_count( $items, "\n" ) + 3 ) . '" name="' . esc_attr( $base . '[items]' ) . '">' . esc_textarea( $items ) . '</textarea></label>';
		echo '<div class="el-pack-materiel"><span class="el-champ-label">Matériel sorti pour ce pack</span>';
		echo '<p class="description">Facultatif. Renseigné, il se reporte d\'un clic dans une réservation et le stock se met à jour tout seul.</p>';
		echo el_champ_materiel( $base . '[materiel]', $materiel ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '</div></fieldset>';
	}
	echo '</div>';
}

/** Enregistre les fiches. */
function el_fiche_enregistrer( $post_id, $post ) {
	if ( ! isset( $_POST['el_fiche_jeton'] ) || ! wp_verify_nonce( sanitize_key( $_POST['el_fiche_jeton'] ), 'el_fiche' ) ) {
		return;
	}
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	$fiches = el_fiches();

	if ( isset( $fiches[ $post->post_type ] ) && isset( $_POST['el'] ) && is_array( $_POST['el'] ) ) {
		$recu = wp_unslash( $_POST['el'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- nettoyé champ par champ.
		foreach ( $fiches[ $post->post_type ]['champs'] as $cle => $champ ) {
			$valeur = el_champ_nettoyer( $champ, isset( $recu[ $cle ] ) ? $recu[ $cle ] : null );
			if ( 'cases' === $champ['type'] ) {
				$valeur = array_map( 'intval', $valeur );
			}
			update_post_meta( $post_id, '_el_' . $cle, is_string( $valeur ) ? wp_slash( $valeur ) : $valeur );
		}
	}

	if ( 'el_formule' === $post->post_type && isset( $_POST['el_packs'] ) && is_array( $_POST['el_packs'] ) ) {
		$recu       = wp_unslash( $_POST['el_packs'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$recommande = isset( $_POST['el_pack_recommande'] ) ? absint( $_POST['el_pack_recommande'] ) : -1;
		$packs      = array();
		foreach ( $recu as $i => $p ) {
			$nom = isset( $p['nom'] ) && is_string( $p['nom'] ) ? sanitize_text_field( $p['nom'] ) : '';
			if ( '' === $nom ) {
				continue;
			}
			$packs[] = array(
				'nom'      => $nom,
				'prix'     => isset( $p['prix'] ) && is_string( $p['prix'] ) ? sanitize_text_field( $p['prix'] ) : '',
				'featured' => (int) $i === $recommande,
				'items'    => el_lignes( isset( $p['items'] ) && is_string( $p['items'] ) ? sanitize_textarea_field( $p['items'] ) : '' ),
				'materiel' => el_lignes_materiel( isset( $p['materiel'] ) ? $p['materiel'] : array() ),
			);
		}
		update_post_meta( $post_id, '_el_packs', wp_slash( $packs ) );
	}

	if ( isset( $_POST['el_seo'] ) && is_array( $_POST['el_seo'] ) ) {
		$recu = wp_unslash( $_POST['el_seo'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		foreach ( el_fiche_seo() as $cle => $champ ) {
			$valeur = el_champ_nettoyer( $champ, isset( $recu[ $cle ] ) ? $recu[ $cle ] : null );
			if ( '' === $valeur || 0 === $valeur ) {
				delete_post_meta( $post_id, '_el_' . $cle );
			} else {
				update_post_meta( $post_id, '_el_' . $cle, is_string( $valeur ) ? wp_slash( $valeur ) : $valeur );
			}
		}
	}
}
add_action( 'save_post', 'el_fiche_enregistrer', 10, 2 );

/** Le texte d'invite du champ titre. */
function el_invite_titre( $texte, $post ) {
	$invites = array(
		'el_formule'     => 'Nom de la formule',
		'el_produit'     => 'Nom du produit',
		'el_realisation' => 'Titre de la réalisation',
		'el_question'    => 'La question, telle qu\'un client la poserait',
		'el_dossier'     => 'Nom du dossier, par exemple « Mariage Dupont »',
	);
	return isset( $invites[ $post->post_type ] ) ? $invites[ $post->post_type ] : $texte;
}
add_filter( 'enter_title_here', 'el_invite_titre', 10, 2 );

/* ---------------------------------------------------------------------- catégories du catalogue */

function el_categorie_champs() {
	return array(
		'el_h1'              => array( 'Grand titre de la page', 'texte', 'Vide : « Location : » suivi du nom de la catégorie.' ),
		'el_picto'           => array( 'Pictogramme', 'picto', 'Affiché pour la catégorie, et pour ses produits sans photo.' ),
		'el_ordre'           => array( 'Ordre', 'nombre', 'Les catégories s\'affichent de la plus petite valeur à la plus grande.' ),
		'el_seo_titre'       => array( 'Titre pour Google', 'texte', '' ),
		'el_seo_description' => array( 'Description pour Google', 'zone', '' ),
	);
}

function el_categorie_champ_html( $cle, $champ, $valeur ) {
	if ( 'picto' === $champ[1] ) {
		$html = '<select name="' . esc_attr( $cle ) . '" id="' . esc_attr( $cle ) . '"><option value="">Automatique</option>';
		foreach ( el_pictos_choix() as $v => $libelle ) {
			$html .= '<option value="' . esc_attr( $v ) . '"' . selected( $valeur, $v, false ) . '>' . esc_html( $libelle ) . '</option>';
		}
		return $html . '</select>';
	}
	if ( 'zone' === $champ[1] ) {
		return '<textarea name="' . esc_attr( $cle ) . '" id="' . esc_attr( $cle ) . '" rows="3" class="large-text">' . esc_textarea( $valeur ) . '</textarea>';
	}
	return '<input type="' . ( 'nombre' === $champ[1] ? 'number' : 'text' ) . '" name="' . esc_attr( $cle ) . '" id="' . esc_attr( $cle ) . '" value="' . esc_attr( $valeur ) . '"' . ( 'nombre' === $champ[1] ? ' class="small-text"' : '' ) . '>';
}

function el_categorie_ajout() {
	wp_nonce_field( 'el_categorie', 'el_categorie_jeton' );
	foreach ( el_categorie_champs() as $cle => $champ ) {
		echo '<div class="form-field"><label for="' . esc_attr( $cle ) . '">' . esc_html( $champ[0] ) . '</label>';
		echo el_categorie_champ_html( $cle, $champ, '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '' !== $champ[2] ? '<p>' . esc_html( $champ[2] ) . '</p>' : '';
		echo '</div>';
	}
}
add_action( 'el_categorie_add_form_fields', 'el_categorie_ajout' );

function el_categorie_modification( $terme ) {
	wp_nonce_field( 'el_categorie', 'el_categorie_jeton' );
	foreach ( el_categorie_champs() as $cle => $champ ) {
		echo '<tr class="form-field"><th scope="row"><label for="' . esc_attr( $cle ) . '">' . esc_html( $champ[0] ) . '</label></th><td>';
		echo el_categorie_champ_html( $cle, $champ, (string) get_term_meta( $terme->term_id, $cle, true ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '' !== $champ[2] ? '<p class="description">' . esc_html( $champ[2] ) . '</p>' : '';
		echo '</td></tr>';
	}
}
add_action( 'el_categorie_edit_form_fields', 'el_categorie_modification' );

function el_categorie_enregistrer( $terme_id ) {
	if ( ! isset( $_POST['el_categorie_jeton'] ) || ! wp_verify_nonce( sanitize_key( $_POST['el_categorie_jeton'] ), 'el_categorie' ) || ! current_user_can( 'manage_categories' ) ) {
		return;
	}
	foreach ( el_categorie_champs() as $cle => $champ ) {
		$brut = isset( $_POST[ $cle ] ) && is_string( $_POST[ $cle ] ) ? wp_unslash( $_POST[ $cle ] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( 'nombre' === $champ[1] ) {
			$valeur = '' === trim( $brut ) ? '' : (string) (int) $brut;
		} elseif ( 'picto' === $champ[1] ) {
			$valeur = isset( el_pictos_choix()[ $brut ] ) ? $brut : '';
		} elseif ( 'zone' === $champ[1] ) {
			$valeur = sanitize_textarea_field( $brut );
		} else {
			$valeur = sanitize_text_field( $brut );
		}
		if ( '' === $valeur ) {
			delete_term_meta( $terme_id, $cle );
		} else {
			update_term_meta( $terme_id, $cle, wp_slash( $valeur ) );
		}
	}
}
add_action( 'created_el_categorie', 'el_categorie_enregistrer' );
add_action( 'edited_el_categorie', 'el_categorie_enregistrer' );

/* ---------------------------------------------------------------------- colonnes des listes */

function el_colonnes_produit( $colonnes ) {
	$sortie = array();
	foreach ( $colonnes as $cle => $nom ) {
		if ( 'title' === $cle ) {
			$sortie['el_photo'] = 'Photo';
		}
		if ( 'date' === $cle ) {
			$sortie['el_prix']  = 'Tarif';
			$sortie['el_stock'] = 'Stock';
			$sortie['el_ordre'] = 'Ordre';
			continue;
		}
		$sortie[ $cle ] = $nom;
	}
	return $sortie;
}
add_filter( 'manage_el_produit_posts_columns', 'el_colonnes_produit' );

function el_colonnes_realisation( $colonnes ) {
	$sortie = array();
	foreach ( $colonnes as $cle => $nom ) {
		if ( 'title' === $cle ) {
			$sortie['el_photo'] = 'Photo';
		}
		if ( 'date' === $cle ) {
			$sortie['el_annee'] = 'Année';
			$sortie['el_ordre'] = 'Ordre';
			continue;
		}
		$sortie[ $cle ] = $nom;
	}
	return $sortie;
}
add_filter( 'manage_el_realisation_posts_columns', 'el_colonnes_realisation' );

function el_colonnes_formule( $colonnes ) {
	$sortie = array();
	foreach ( $colonnes as $cle => $nom ) {
		if ( 'date' === $cle ) {
			$sortie['el_packs'] = 'Packs';
			$sortie['el_ordre'] = 'Ordre';
			continue;
		}
		$sortie[ $cle ] = $nom;
	}
	return $sortie;
}
add_filter( 'manage_el_formule_posts_columns', 'el_colonnes_formule' );

function el_colonnes_question( $colonnes ) {
	unset( $colonnes['date'] );
	$colonnes['el_ordre'] = 'Ordre';
	return $colonnes;
}
add_filter( 'manage_el_question_posts_columns', 'el_colonnes_question' );

function el_colonne( $colonne, $post_id ) {
	switch ( $colonne ) {
		case 'el_photo':
			$media = 'el_produit' === get_post_type( $post_id ) ? el_couverture_produit( $post_id ) : el_couverture_realisation( $post_id );
			if ( $media ) {
				echo '<img class="el-colonne-photo" src="' . esc_url( el_media_petite( $media ) ) . '" alt="" loading="lazy">';
			}
			break;
		case 'el_prix':
			echo esc_html( el_meta( $post_id, 'prix' ) );
			break;
		case 'el_stock':
			$stock = get_post_meta( $post_id, '_el_stock', true );
			echo '' === $stock ? '<span class="el-discret">non suivi</span>' : (int) $stock;
			break;
		case 'el_annee':
			echo esc_html( el_meta( $post_id, 'annee' ) );
			break;
		case 'el_packs':
			$f = el_formule( $post_id );
			foreach ( $f ? $f['packs'] : array() as $p ) {
				echo esc_html( $p['nom'] . ' : ' . $p['prix'] ) . '<br>';
			}
			break;
		case 'el_ordre':
			echo (int) get_post_field( 'menu_order', $post_id );
			break;
	}
}
add_action( 'manage_posts_custom_column', 'el_colonne', 10, 2 );
