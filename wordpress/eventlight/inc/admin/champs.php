<?php
/**
 * Champs de formulaire de l'administration : affichage et nettoyage.
 *
 * Un champ est décrit par un tableau : « type », « label », « aide », et selon le type
 * « choix », « libelle », « nombre », « rangees ». Les réglages du thème et les fiches
 * (formule, produit, réalisation…) utilisent les mêmes champs.
 */

defined( 'ABSPATH' ) || exit;

/** Liste déroulante des réalisations. */
function el_champ_select_realisation( $nom, $id, $valeur ) {
	$html = '<select name="' . esc_attr( $nom ) . '" id="' . esc_attr( $id ) . '"><option value="0">Aucune</option>';
	foreach ( el_contenus( 'el_realisation' ) as $post ) {
		$html .= '<option value="' . (int) $post->ID . '"' . selected( (int) $valeur, $post->ID, false ) . '>' . esc_html( get_the_title( $post ) ) . '</option>';
	}
	return $html . '</select>';
}

/** Liste déroulante des produits, rangés par catégorie, avec leur stock. */
function el_champ_options_produits( $valeur = 0 ) {
	static $groupes = null;
	if ( null === $groupes ) {
		$groupes = array();
		$stocks  = el_stocks();
		foreach ( el_produits() as $p ) {
			$groupe                           = '' !== $p['categorie'] ? $p['categorie'] : 'Autres';
			$groupes[ $groupe ][ $p['id'] ] = $p['nom'] . ( isset( $stocks[ $p['id'] ] ) ? ' (stock ' . $stocks[ $p['id'] ] . ')' : '' );
		}
	}
	$html = '<option value="0">Choisir un produit</option>';
	foreach ( $groupes as $groupe => $produits ) {
		$html .= '<optgroup label="' . esc_attr( $groupe ) . '">';
		foreach ( $produits as $pid => $nom ) {
			$html .= '<option value="' . (int) $pid . '"' . selected( (int) $valeur, $pid, false ) . '>' . esc_html( $nom ) . '</option>';
		}
		$html .= '</optgroup>';
	}
	return $html;
}

/** Une vignette d'image dans un sélecteur de photos. */
function el_champ_vignette( $id ) {
	$adresse = wp_get_attachment_image_url( $id, 'medium' );
	if ( ! $adresse ) {
		return '';
	}
	return '<li data-id="' . (int) $id . '"><img src="' . esc_url( $adresse ) . '" alt=""><button type="button" class="el-photos-retirer" aria-label="Retirer cette photo">&times;</button></li>';
}

/**
 * Sélecteur de photos de la médiathèque.
 *
 * @param string    $nom      Nom du champ.
 * @param int[]|int $ids      Photos choisies.
 * @param bool      $multiple Plusieurs photos, que l'on peut ordonner en les faisant glisser.
 */
function el_champ_photos( $nom, $ids, $multiple = true ) {
	$ids  = array_values( array_filter( array_map( 'intval', (array) $ids ) ) );
	$html = '<div class="el-photos" data-multiple="' . ( $multiple ? '1' : '0' ) . '">';
	$html .= '<input type="hidden" name="' . esc_attr( $nom ) . '" value="' . esc_attr( implode( ',', $ids ) ) . '">';
	$html .= '<ul class="el-photos-liste">';
	foreach ( $ids as $id ) {
		$html .= el_champ_vignette( $id );
	}
	$html .= '</ul>';
	$html .= '<button type="button" class="button el-photos-ajouter">' . ( $multiple ? 'Ajouter des photos' : 'Choisir une image' ) . '</button>';
	return $html . '</div>';
}

/**
 * Lignes de matériel : un produit et une quantité par ligne.
 *
 * @param string $nom    Nom du champ : « el[lignes] » donne el[lignes][0][produit], el[lignes][0][q]…
 * @param array  $lignes Voir el_lignes_materiel().
 * @param bool   $dispo  Réserve une colonne pour la disponibilité aux dates du dossier.
 */
function el_champ_materiel( $nom, $lignes, $dispo = false ) {
	$rangee = function ( $i, $produit, $q ) use ( $nom, $dispo ) {
		return '<tr class="el-ligne">' .
			'<td><select name="' . esc_attr( $nom . '[' . $i . '][produit]' ) . '" aria-label="Produit">' . el_champ_options_produits( $produit ) . '</select></td>' .
			'<td><input type="number" class="small-text" min="1" max="9999" name="' . esc_attr( $nom . '[' . $i . '][q]' ) . '" value="' . esc_attr( $q ) . '" aria-label="Quantité"></td>' .
			( $dispo ? '<td class="el-ligne-dispo" aria-live="polite"></td>' : '' ) .
			'<td><button type="button" class="button-link el-ligne-retirer">Retirer</button></td>' .
			'</tr>';
	};
	$html = '<div class="el-lignes" data-suivant="' . count( $lignes ) . '"><table class="el-lignes-table"><tbody>';
	foreach ( array_values( $lignes ) as $i => $l ) {
		$html .= $rangee( $i, $l['produit'], $l['q'] );
	}
	$html .= '</tbody></table>';
	$html .= '<button type="button" class="button el-ligne-ajouter">Ajouter du matériel</button>';
	$html .= '<script type="text/template" class="el-ligne-modele">' . $rangee( '__i__', 0, 1 ) . '</script>';
	return $html . '</div>';
}

/**
 * Le code HTML d'un champ.
 *
 * @param string $nom    Attribut name.
 * @param string $id     Attribut id.
 * @param array  $champ  Description du champ.
 * @param mixed  $valeur Valeur actuelle.
 */
function el_champ_html( $nom, $id, $champ, $valeur ) {
	$type = isset( $champ['type'] ) ? $champ['type'] : 'texte';
	switch ( $type ) {
		case 'zone':
		case 'lignes':
		case 'paires':
			if ( is_array( $valeur ) ) {
				$valeur = implode( "\n", $valeur );
			}
			$rangees = isset( $champ['rangees'] ) ? (int) $champ['rangees'] : max( 3, min( 10, substr_count( (string) $valeur, "\n" ) + 2 ) );
			return '<textarea class="large-text" rows="' . $rangees . '" name="' . esc_attr( $nom ) . '" id="' . esc_attr( $id ) . '">' . esc_textarea( (string) $valeur ) . '</textarea>';

		case 'case':
			return '<label><input type="checkbox" name="' . esc_attr( $nom ) . '" id="' . esc_attr( $id ) . '" value="1"' . checked( ! empty( $valeur ), true, false ) . '> ' . esc_html( isset( $champ['libelle'] ) ? $champ['libelle'] : '' ) . '</label>';

		case 'choix':
			$html = '<select name="' . esc_attr( $nom ) . '" id="' . esc_attr( $id ) . '">';
			foreach ( $champ['choix'] as $v => $libelle ) {
				$html .= '<option value="' . esc_attr( $v ) . '"' . selected( (string) $valeur, (string) $v, false ) . '>' . esc_html( $libelle ) . '</option>';
			}
			return $html . '</select>';

		case 'cases':
			$html    = '<fieldset class="el-cases"><legend class="screen-reader-text">' . esc_html( $champ['label'] ) . '</legend>';
			$cochees = array_map( 'strval', (array) $valeur );
			foreach ( $champ['choix'] as $v => $libelle ) {
				$html .= '<label><input type="checkbox" name="' . esc_attr( $nom ) . '[]" value="' . esc_attr( $v ) . '"' . checked( in_array( (string) $v, $cochees, true ), true, false ) . '> ' . esc_html( $libelle ) . '</label>';
			}
			if ( ! $champ['choix'] ) {
				$html .= '<span class="description">' . esc_html( isset( $champ['vide'] ) ? $champ['vide'] : 'Rien à cocher pour le moment.' ) . '</span>';
			}
			return $html . '</fieldset>';

		case 'realisation':
			return el_champ_select_realisation( $nom, $id, $valeur );

		case 'realisations':
			$html   = '<div class="el-suite">';
			$valeur = array_values( (array) $valeur );
			$nombre = isset( $champ['nombre'] ) ? (int) $champ['nombre'] : 5;
			for ( $i = 0; $i < $nombre; $i++ ) {
				$html .= el_champ_select_realisation( $nom . '[]', $id . '-' . $i, isset( $valeur[ $i ] ) ? $valeur[ $i ] : 0 );
			}
			return $html . '</div>';

		case 'scenes':
			$valeur = array_values( (array) $valeur );
			$html   = '<table class="widefat striped el-scenes"><thead><tr><th>Image</th><th>Bouton</th><th>Réalisation liée</th><th>Cadrage</th><th>Légende</th></tr></thead><tbody>';
			for ( $i = 0; $i < 4; $i++ ) {
				$s     = isset( $valeur[ $i ] ) && is_array( $valeur[ $i ] ) ? $valeur[ $i ] : array();
				$base  = $nom . '[' . $i . ']';
				$html .= '<tr>' .
					'<td>' . el_champ_photos( $base . '[image]', isset( $s['image'] ) ? $s['image'] : 0, false ) . '</td>' .
					'<td><input type="text" class="regular-text" name="' . esc_attr( $base . '[label]' ) . '" value="' . esc_attr( isset( $s['label'] ) ? $s['label'] : '' ) . '" placeholder="Une scène" aria-label="Texte du bouton"></td>' .
					'<td>' . el_champ_select_realisation( $base . '[realisation]', $id . '-r-' . $i, isset( $s['realisation'] ) ? $s['realisation'] : 0 ) . '</td>' .
					'<td><input type="text" class="small-text el-cadrage" name="' . esc_attr( $base . '[cadrage]' ) . '" value="' . esc_attr( isset( $s['cadrage'] ) ? $s['cadrage'] : '' ) . '" placeholder="50% 50%" aria-label="Cadrage"></td>' .
					'<td><input type="text" class="large-text" name="' . esc_attr( $base . '[legende]' ) . '" value="' . esc_attr( isset( $s['legende'] ) ? $s['legende'] : '' ) . '" aria-label="Légende"></td>' .
					'</tr>';
			}
			return $html . '</tbody></table>';

		case 'photos':
			return el_champ_photos( $nom, $valeur, true );

		case 'nombre':
			return '<input type="number" class="small-text" min="0" name="' . esc_attr( $nom ) . '" id="' . esc_attr( $id ) . '" value="' . esc_attr( is_scalar( $valeur ) ? (string) $valeur : '' ) . '">';

		case 'date':
			return '<input type="date" name="' . esc_attr( $nom ) . '" id="' . esc_attr( $id ) . '" value="' . esc_attr( is_scalar( $valeur ) ? (string) $valeur : '' ) . '">';

		case 'email':
		case 'url':
		case 'tel':
		case 'texte':
		default:
			$types  = array(
				'email' => 'email',
				'url'   => 'url',
				'tel'   => 'tel',
			);
			$classe = ! empty( $champ['court'] ) ? 'regular-text' : 'large-text';
			return '<input type="' . ( isset( $types[ $type ] ) ? $types[ $type ] : 'text' ) . '" class="' . $classe . '" name="' . esc_attr( $nom ) . '" id="' . esc_attr( $id ) . '" value="' . esc_attr( is_scalar( $valeur ) ? (string) $valeur : '' ) . '"' . ( isset( $champ['exemple'] ) ? ' placeholder="' . esc_attr( $champ['exemple'] ) . '"' : '' ) . '>';
	}
}

/**
 * Nettoie la valeur reçue d'un champ.
 *
 * @param array $champ Description du champ.
 * @param mixed $brut  Valeur reçue, déjà débarrassée des barres obliques ajoutées par WordPress.
 */
function el_champ_nettoyer( $champ, $brut ) {
	$type = isset( $champ['type'] ) ? $champ['type'] : 'texte';
	switch ( $type ) {
		case 'zone':
		case 'lignes':
		case 'paires':
			return is_string( $brut ) ? trim( sanitize_textarea_field( $brut ) ) : '';

		case 'case':
			return empty( $brut ) ? 0 : 1;

		case 'choix':
			return ( is_scalar( $brut ) && isset( $champ['choix'][ (string) $brut ] ) ) ? (string) $brut : (string) key( $champ['choix'] );

		case 'cases':
			$valeurs = array();
			foreach ( (array) $brut as $v ) {
				if ( is_scalar( $v ) && isset( $champ['choix'][ (string) $v ] ) ) {
					$valeurs[] = (string) $v;
				}
			}
			return $valeurs;

		case 'realisation':
			return absint( $brut );

		case 'realisations':
			return array_values( array_unique( array_filter( array_map( 'absint', (array) $brut ) ) ) );

		case 'scenes':
			$scenes = array();
			foreach ( (array) $brut as $s ) {
				if ( ! is_array( $s ) ) {
					continue;
				}
				$image = isset( $s['image'] ) ? absint( $s['image'] ) : 0;
				if ( ! $image ) {
					continue;
				}
				$scenes[] = array(
					'image'       => $image,
					'label'       => isset( $s['label'] ) ? sanitize_text_field( $s['label'] ) : '',
					'realisation' => isset( $s['realisation'] ) ? absint( $s['realisation'] ) : 0,
					'cadrage'     => isset( $s['cadrage'] ) ? trim( preg_replace( '/[^0-9a-z%. -]/i', '', (string) $s['cadrage'] ) ) : '',
					'legende'     => isset( $s['legende'] ) ? sanitize_text_field( $s['legende'] ) : '',
				);
			}
			return $scenes;

		case 'photos':
			$ids = is_array( $brut ) ? $brut : preg_split( '/[\s,]+/', (string) $brut, -1, PREG_SPLIT_NO_EMPTY );
			return array_values( array_unique( array_filter( array_map( 'absint', $ids ) ) ) );

		case 'nombre':
			return ( is_scalar( $brut ) && '' !== trim( (string) $brut ) && is_numeric( $brut ) ) ? (string) max( 0, (int) $brut ) : '';

		case 'date':
			return ( is_string( $brut ) && el_est_date( $brut ) ) ? $brut : '';

		case 'email':
			return is_string( $brut ) ? sanitize_email( $brut ) : '';

		case 'url':
			return is_string( $brut ) ? esc_url_raw( trim( $brut ) ) : '';

		default:
			return is_string( $brut ) ? sanitize_text_field( $brut ) : '';
	}
}

/**
 * Affiche une série de champs dans une fiche : un libellé, le champ, une aide éventuelle.
 *
 * @param array    $champs  cle => description.
 * @param callable $valeur  Fonction qui donne la valeur d'une clé.
 * @param string   $prefixe Préfixe des attributs name : « el » donne el[cle].
 */
function el_champs_afficher( $champs, $valeur, $prefixe = 'el' ) {
	foreach ( $champs as $cle => $champ ) {
		$id   = 'el-' . str_replace( '_', '-', $cle );
		$seul = in_array( $champ['type'], array( 'case', 'cases', 'photos', 'scenes', 'realisations' ), true );
		echo '<div class="el-champ el-champ-' . esc_attr( $champ['type'] ) . '">';
		if ( $seul ) {
			echo '<span class="el-champ-label">' . esc_html( $champ['label'] ) . '</span>';
		} else {
			echo '<label class="el-champ-label" for="' . esc_attr( $id ) . '">' . esc_html( $champ['label'] ) . '</label>';
		}
		echo el_champ_html( $prefixe . '[' . $cle . ']', $id, $champ, call_user_func( $valeur, $cle ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		if ( ! empty( $champ['aide'] ) ) {
			echo '<p class="description">' . esc_html( $champ['aide'] ) . '</p>';
		}
		echo '</div>';
	}
}

/** Feuille de style et script de l'administration, sur les écrans du thème seulement. */
function el_admin_fichiers( $ecran_id ) {
	$ecran = get_current_screen();
	$types = array( 'el_formule', 'el_produit', 'el_realisation', 'el_question', 'el_dossier', 'page' );
	$nous  = $ecran && ( in_array( $ecran->post_type, $types, true ) || 'el_categorie' === $ecran->taxonomy || false !== strpos( (string) $ecran_id, 'eventlight' ) || false !== strpos( (string) $ecran_id, 'el-' ) );
	if ( ! $nous ) {
		return;
	}
	wp_enqueue_media();
	wp_enqueue_style( 'eventlight-admin', get_theme_file_uri( 'admin/admin.css' ), array(), el_version( 'admin/admin.css' ) );
	wp_enqueue_script( 'eventlight-admin', get_theme_file_uri( 'admin/admin.js' ), array( 'jquery', 'jquery-ui-sortable' ), el_version( 'admin/admin.js' ), true );
	wp_localize_script(
		'eventlight-admin',
		'elAdmin',
		array(
			'ajax'  => admin_url( 'admin-ajax.php' ),
			'jeton' => wp_create_nonce( 'el_admin' ),
		)
	);
}
add_action( 'admin_enqueue_scripts', 'el_admin_fichiers' );
