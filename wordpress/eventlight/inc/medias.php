<?php
/**
 * Images : tailles produites à l'envoi, balises <img> adaptatives, type de fond.
 *
 * Le « fond » d'une image dit comment l'afficher : un objet détouré sur blanc se pose entier
 * dans sa vitrine, une photo remplit le cadre, un visuel sur noir se montre entier sur écran noir.
 */

defined( 'ABSPATH' ) || exit;

function el_tailles_images() {
	add_theme_support( 'post-thumbnails', array( 'el_realisation' ) );
	add_image_size( 'el-480', 480, 9999 );
	add_image_size( 'el-960', 960, 9999 );
	add_image_size( 'el-1600', 1600, 9999 );
}
add_action( 'after_setup_theme', 'el_tailles_images' );

/** Les quatre fonds possibles, avec leur libellé pour l'administration. */
function el_fonds() {
	return array(
		'image'       => 'Photo : elle remplit le cadre',
		'clair'       => 'Objet détouré sur fond blanc',
		'sombre'      => 'Visuel sur fond noir',
		'transparent' => 'Image à fond transparent',
	);
}

/**
 * Tout ce qu'il faut pour afficher une image de la médiathèque.
 *
 * @return array|null « sources » (largeur => adresse, par largeur croissante), « w », « h », « fond ».
 */
function el_media( $id ) {
	static $cache = array();
	$id = (int) $id;
	if ( ! $id ) {
		return null;
	}
	if ( array_key_exists( $id, $cache ) ) {
		return $cache[ $id ];
	}
	$cache[ $id ] = null;
	$meta         = wp_get_attachment_metadata( $id );
	$adresse      = wp_get_attachment_url( $id );
	if ( ! $adresse || ! is_array( $meta ) || empty( $meta['width'] ) || empty( $meta['height'] ) ) {
		return null;
	}
	$largeur  = (int) $meta['width'];
	$hauteur  = (int) $meta['height'];
	$dossier  = trailingslashit( dirname( $adresse ) );
	$sources  = array( $largeur => $adresse );
	$rapport  = $largeur / $hauteur;
	$tailles  = isset( $meta['sizes'] ) && is_array( $meta['sizes'] ) ? $meta['sizes'] : array();
	foreach ( $tailles as $t ) {
		if ( empty( $t['file'] ) || empty( $t['width'] ) || empty( $t['height'] ) ) {
			continue;
		}
		$l = (int) $t['width'];
		// On écarte les recadrages (vignette carrée) et les tailles trop petites pour servir.
		if ( $l >= $largeur || $l < 300 || abs( $l / $rapport - (int) $t['height'] ) > 1.5 ) {
			continue;
		}
		$sources[ $l ] = $dossier . $t['file'];
	}
	ksort( $sources );

	$origine = (string) get_post_meta( $id, '_el_orig', true );
	if ( preg_match( '/^(\d+)x(\d+)$/', $origine, $m ) ) {
		$largeur = (int) $m[1];
		$hauteur = (int) $m[2];
	}
	$fond = (string) get_post_meta( $id, '_el_fond', true );

	$cache[ $id ] = array(
		'id'      => $id,
		'sources' => $sources,
		'w'       => $largeur,
		'h'       => $hauteur,
		'fond'    => isset( el_fonds()[ $fond ] ) ? $fond : 'image',
	);
	return $cache[ $id ];
}

/** L'adresse de la plus grande version d'une image. */
function el_media_grande( $media ) {
	$adresses = array_values( $media['sources'] );
	return end( $adresses );
}

/** L'adresse de la plus petite version d'une image. */
function el_media_petite( $media ) {
	$adresses = array_values( $media['sources'] );
	return reset( $adresses );
}

/** Les identifiants des photos d'un contenu, dans l'ordre choisi. */
function el_galerie_ids( $post_id ) {
	$ids = get_post_meta( $post_id, '_el_galerie', true );
	if ( ! is_array( $ids ) ) {
		$ids = preg_split( '/[\s,]+/', (string) $ids, -1, PREG_SPLIT_NO_EMPTY );
	}
	return array_values( array_filter( array_map( 'intval', $ids ) ) );
}

/** Les photos d'un contenu (produit ou réalisation). */
function el_galerie( $post_id ) {
	$sortie = array();
	foreach ( el_galerie_ids( $post_id ) as $id ) {
		$m = el_media( $id );
		if ( $m ) {
			$sortie[] = $m;
		}
	}
	return $sortie;
}

/** Charge d'un coup les informations des photos d'une liste de contenus. */
function el_galeries_amorcer( $posts ) {
	$ids = array();
	foreach ( $posts as $post ) {
		$ids = array_merge( $ids, el_galerie_ids( $post->ID ) );
		$une = (int) get_post_thumbnail_id( $post->ID );
		if ( $une ) {
			$ids[] = $une;
		}
	}
	$ids = array_values( array_unique( $ids ) );
	if ( $ids ) {
		_prime_post_caches( $ids, false, true );
	}
}

/** Photo de couverture d'un produit : de préférence un détourage sur fond clair. */
function el_couverture_produit( $post_id ) {
	$liste = el_galerie( $post_id );
	foreach ( $liste as $m ) {
		if ( 'clair' === $m['fond'] || 'transparent' === $m['fond'] ) {
			return $m;
		}
	}
	return $liste ? $liste[0] : null;
}

/** Image de couverture d'une réalisation : celle choisie dans l'administration, sinon la première photo. */
function el_couverture_realisation( $post_id ) {
	$une = (int) get_post_thumbnail_id( $post_id );
	if ( $une ) {
		$m = el_media( $une );
		if ( $m ) {
			return $m;
		}
	}
	$liste = el_galerie( $post_id );
	return $liste ? $liste[0] : null;
}

/**
 * <img> adaptatif.
 *
 * @param array|null $media      Voir el_media().
 * @param string     $alt        Texte alternatif.
 * @param string     $sizes      Attribut sizes.
 * @param string     $chargement « eager » pour l'image principale d'une page, sinon chargement différé.
 * @param string     $classe     Classe CSS.
 */
function el_img( $media, $alt = '', $sizes = '100vw', $chargement = 'lazy', $classe = '' ) {
	if ( ! $media ) {
		return '';
	}
	$largeurs = array_keys( $media['sources'] );
	$defaut   = $largeurs[0];
	foreach ( $largeurs as $l ) {
		if ( $l <= 960 ) {
			$defaut = $l;
		}
	}
	$attributs = array( 'src="' . esc_url( $media['sources'][ $defaut ] ) . '"' );
	if ( count( $largeurs ) > 1 ) {
		$jeu = array();
		foreach ( $media['sources'] as $l => $adresse ) {
			$jeu[] = esc_url( $adresse ) . ' ' . $l . 'w';
		}
		$attributs[] = 'srcset="' . implode( ', ', $jeu ) . '"';
		$attributs[] = 'sizes="' . esc_attr( $sizes ? $sizes : '100vw' ) . '"';
	}
	$attributs[] = 'width="' . (int) $media['w'] . '"';
	$attributs[] = 'height="' . (int) $media['h'] . '"';
	$attributs[] = 'alt="' . esc_attr( $alt ) . '"';
	$attributs[] = 'eager' === $chargement ? 'fetchpriority="high"' : 'loading="lazy"';
	$attributs[] = 'decoding="async"';
	if ( '' !== $classe ) {
		$attributs[] = 'class="' . esc_attr( $classe ) . '"';
	}
	$attributs[] = 'data-fond="' . esc_attr( $media['fond'] ) . '"';
	return '<img ' . implode( ' ', $attributs ) . '>';
}

/* ---------------------------------------------------------------------- type de fond */

/**
 * Fond clair, sombre, transparent ou quelconque : moyenne des pixels du pourtour.
 *
 * @param string $fichier Chemin d'une image, de préférence une petite version.
 */
function el_fond_de( $fichier ) {
	if ( ! function_exists( 'imagecreatefromstring' ) || ! is_readable( $fichier ) || filesize( $fichier ) > 12 * MB_IN_BYTES ) {
		return 'image';
	}
	// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
	$source = @imagecreatefromstring( (string) file_get_contents( $fichier ) );
	if ( ! $source ) {
		return 'image';
	}
	$n     = 24;
	$petit = imagecreatetruecolor( $n, $n );
	imagealphablending( $petit, false );
	imagesavealpha( $petit, true );
	imagecopyresampled( $petit, $source, 0, 0, 0, 0, $n, $n, imagesx( $source ), imagesy( $source ) );
	unset( $source );

	$lumiere = 0.0;
	$opacite = 0.0;
	$nombre  = 0;
	for ( $y = 0; $y < $n; $y++ ) {
		for ( $x = 0; $x < $n; $x++ ) {
			if ( $x > 0 && $x < $n - 1 && $y > 0 && $y < $n - 1 ) {
				continue;
			}
			$c        = imagecolorat( $petit, $x, $y );
			$lumiere += ( 0.2126 * ( ( $c >> 16 ) & 0xFF ) + 0.7152 * ( ( $c >> 8 ) & 0xFF ) + 0.0722 * ( $c & 0xFF ) ) / 255;
			$opacite += 1 - ( ( $c >> 24 ) & 0x7F ) / 127;
			$nombre++;
		}
	}
	unset( $petit );
	$lumiere /= $nombre;
	$opacite /= $nombre;
	if ( $opacite < 0.5 ) {
		return 'transparent';
	}
	if ( $lumiere > 0.86 ) {
		return 'clair';
	}
	if ( $lumiere < 0.16 ) {
		return 'sombre';
	}
	return 'image';
}

/** À l'envoi d'une image, on note son type de fond. On peut le corriger ensuite dans la médiathèque. */
function el_fond_a_l_envoi( $meta, $id ) {
	if ( ! is_array( $meta ) || empty( $meta['width'] ) || get_post_meta( $id, '_el_fond', true ) ) {
		return $meta;
	}
	$fichier = get_attached_file( $id );
	if ( ! $fichier ) {
		return $meta;
	}
	// La plus petite version non recadrée suffit, et ménage la mémoire.
	$choisi  = $fichier;
	$rapport = $meta['width'] / max( 1, $meta['height'] );
	$plus    = (int) $meta['width'];
	foreach ( isset( $meta['sizes'] ) && is_array( $meta['sizes'] ) ? $meta['sizes'] : array() as $t ) {
		if ( empty( $t['file'] ) || empty( $t['width'] ) || empty( $t['height'] ) ) {
			continue;
		}
		if ( $t['width'] >= 200 && $t['width'] < $plus && abs( $t['width'] / $rapport - $t['height'] ) <= 1.5 ) {
			$candidat = trailingslashit( dirname( $fichier ) ) . $t['file'];
			if ( is_readable( $candidat ) ) {
				$choisi = $candidat;
				$plus   = (int) $t['width'];
			}
		}
	}
	// Faute de petite version, on n'ouvre pas un original trop grand pour la mémoire du serveur.
	$trop_grand = $choisi === $fichier && $meta['width'] * $meta['height'] > 16000000;
	update_post_meta( $id, '_el_fond', $trop_grand ? 'image' : el_fond_de( $choisi ) );
	return $meta;
}
add_filter( 'wp_generate_attachment_metadata', 'el_fond_a_l_envoi', 10, 2 );

/** Le choix du fond, dans la fiche d'une image de la médiathèque. */
function el_fond_champ( $champs, $post ) {
	if ( ! wp_attachment_is_image( $post->ID ) ) {
		return $champs;
	}
	$actuel = (string) get_post_meta( $post->ID, '_el_fond', true );
	$html   = '<select name="attachments[' . (int) $post->ID . '][el_fond]" id="attachments-' . (int) $post->ID . '-el_fond">';
	foreach ( el_fonds() as $valeur => $libelle ) {
		$html .= '<option value="' . esc_attr( $valeur ) . '"' . selected( $actuel ? $actuel : 'image', $valeur, false ) . '>' . esc_html( $libelle ) . '</option>';
	}
	$html .= '</select>';
	$champs['el_fond'] = array(
		'label' => 'Affichage sur le site',
		'input' => 'html',
		'html'  => $html,
		'helps' => 'Un objet détouré est montré entier dans sa vitrine. Une photo remplit le cadre.',
	);
	return $champs;
}
add_filter( 'attachment_fields_to_edit', 'el_fond_champ', 10, 2 );

function el_fond_enregistrer( $post, $donnees ) {
	if ( isset( $donnees['el_fond'] ) && isset( el_fonds()[ $donnees['el_fond'] ] ) ) {
		update_post_meta( $post['ID'], '_el_fond', $donnees['el_fond'] );
	}
	return $post;
}
add_filter( 'attachment_fields_to_save', 'el_fond_enregistrer', 10, 2 );
