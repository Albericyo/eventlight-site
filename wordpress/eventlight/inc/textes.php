<?php
/**
 * Textes et prix : typographie française, lecture des prix, petits outils de mise en forme.
 */

defined( 'ABSPATH' ) || exit;

/** Typographie française : espaces insécables avant : ; ! ? » € % et après «. */
function el_typo( $texte ) {
	$texte = (string) $texte;
	if ( '' === $texte || ! preg_match( '/[;!?:»«€%]/u', $texte ) ) {
		return $texte;
	}
	$sortie = preg_replace(
		array( '/ ([;!?])/u', '/ ([:»])/u', '/« /u', '/([0-9]) (€|%)/u' ),
		array( "\u{202f}$1", "\u{00a0}$1", "«\u{00a0}", "$1\u{00a0}$2" ),
		$texte
	);
	return null === $sortie ? $texte : $sortie;
}

/**
 * La même règle, appliquée à tout le texte d'une page : on saute les balises, les scripts,
 * les styles, les zones de saisie et les commentaires.
 */
function el_typographie( $html ) {
	$html   = (string) $html;
	$taille = strlen( $html );
	$sortie = '';
	$i      = 0;
	while ( $i < $taille ) {
		$ouvre = strpos( $html, '<', $i );
		if ( false === $ouvre ) {
			$sortie .= el_typo( substr( $html, $i ) );
			break;
		}
		if ( $ouvre > $i ) {
			$sortie .= el_typo( substr( $html, $i, $ouvre - $i ) );
		}
		if ( '<!--' === substr( $html, $ouvre, 4 ) ) {
			$fin = strpos( $html, '-->', $ouvre + 4 );
			$fin = false === $fin ? $taille : $fin + 3;
		} elseif ( preg_match( '/\G<(script|style|textarea)\b/i', $html, $m, 0, $ouvre ) ) {
			$fin = stripos( $html, '</' . $m[1], $ouvre );
			if ( false !== $fin ) {
				$fin = strpos( $html, '>', $fin );
			}
			$fin = false === $fin ? $taille : $fin + 1;
		} else {
			$fin = strpos( $html, '>', $ouvre );
			$fin = false === $fin ? $taille : $fin + 1;
		}
		$sortie .= substr( $html, $ouvre, $fin - $ouvre );
		$i       = $fin;
	}
	return $sortie;
}

/** « 40,00 €/j (la paire) » donne montant « 40 », unité « €/j », note « la paire ». */
function el_prix_detail( $valeur ) {
	$s = (string) $valeur;
	if ( ! preg_match( '/([\d\s]+)(?:,(\d+))?\s*€(\/j)?\s*(?:\((.+)\))?/u', $s, $m ) ) {
		return array(
			'montant' => $s,
			'unite'   => '',
			'note'    => '',
		);
	}
	$centimes = ( isset( $m[2] ) && '' !== $m[2] && '00' !== $m[2] ) ? ',' . $m[2] : '';
	return array(
		'montant' => trim( $m[1] ) . $centimes,
		'unite'   => ! empty( $m[3] ) ? '€/j' : '€',
		'note'    => isset( $m[4] ) ? $m[4] : '',
	);
}

/** Le nombre contenu dans un prix : « 40,00 €/j » donne 40. */
function el_prix_nombre( $valeur ) {
	$s = preg_replace( '/,/', '.', (string) $valeur, 1 );
	return preg_match( '/[\d.]+/', $s, $m ) ? (float) $m[0] : 0.0;
}

/** Le prix tel qu'attendu par les données structurées : « 40.00 ». */
function el_prix_schema( $valeur ) {
	if ( ! $valeur ) {
		return '';
	}
	$s = preg_replace( '/,/', '.', (string) $valeur, 1 );
	return preg_match( '/[\d.]+/', $s, $m ) ? $m[0] : '';
}

/** Prix le plus bas d'une liste de produits, pour « dès 9 € par jour ». */
function el_prix_min( $produits ) {
	$nombres = array();
	foreach ( (array) $produits as $p ) {
		$n = el_prix_nombre( isset( $p['prix'] ) ? $p['prix'] : '' );
		if ( $n ) {
			$nombres[] = $n;
		}
	}
	return $nombres ? el_nombre( min( $nombres ) ) : '0';
}

/** Un nombre écrit sans zéros inutiles : 9.0 donne « 9 », 12.5 donne « 12.5 ». */
function el_nombre( $n ) {
	$s = rtrim( rtrim( number_format( (float) $n, 2, '.', '' ), '0' ), '.' );
	return ( '' === $s || '-0' === $s ) ? '0' : $s;
}

/** Coupe « Rôle : Nom » en deux pour les fiches techniques. */
function el_credit( $ligne ) {
	$ligne = (string) $ligne;
	$i     = strpos( $ligne, ' : ' );
	if ( false === $i ) {
		return array(
			'role' => '',
			'nom'  => $ligne,
		);
	}
	return array(
		'role' => substr( $ligne, 0, $i ),
		'nom'  => substr( $ligne, $i + 3 ),
	);
}

/** « Mapping - Pignan Millénaire » donne « Pignan Millénaire » : le type est déjà affiché en étiquette. */
function el_titre_court( $titre ) {
	$titre = preg_replace( '/^Mapping(?: en 48h)? - /u', '', (string) $titre );
	return str_replace( ' - ', ', ', $titre );
}

/** Remplace les tirets de séparation par des virgules. */
function el_virgules( $texte ) {
	return str_replace( ' - ', ', ', (string) $texte );
}

/** Découpe un texte en lignes non vides. */
function el_lignes( $texte ) {
	if ( is_array( $texte ) ) {
		$lignes = $texte;
	} else {
		$lignes = preg_split( '/\r\n|\r|\n/', (string) $texte );
	}
	$sortie = array();
	foreach ( $lignes as $l ) {
		$l = trim( (string) $l );
		if ( '' !== $l ) {
			$sortie[] = $l;
		}
	}
	return $sortie;
}

/** Découpe des lignes « Titre | Texte » en paires. */
function el_paires( $texte ) {
	$sortie = array();
	foreach ( el_lignes( $texte ) as $l ) {
		$morceaux = explode( '|', $l, 2 );
		$sortie[] = array(
			trim( $morceaux[0] ),
			isset( $morceaux[1] ) ? trim( $morceaux[1] ) : '',
		);
	}
	return $sortie;
}

/** Un numéro français « 07 81 53 36 00 » au format international « +33781533600 ». */
function el_tel_iso( $tel ) {
	$chiffres = preg_replace( '/[^\d+]/', '', (string) $tel );
	if ( '' === $chiffres ) {
		return '';
	}
	if ( '+' === $chiffres[0] ) {
		return $chiffres;
	}
	if ( 0 === strpos( $chiffres, '00' ) ) {
		return '+' . substr( $chiffres, 2 );
	}
	if ( '0' === $chiffres[0] ) {
		return '+33' . substr( $chiffres, 1 );
	}
	return $chiffres;
}

/** Identifiant YouTube, que l'on ait saisi l'identifiant seul ou l'adresse de la vidéo. */
function el_youtube_id( $valeur ) {
	$valeur = trim( (string) $valeur );
	if ( '' === $valeur ) {
		return '';
	}
	if ( preg_match( '~(?:youtu\.be/|youtube(?:-nocookie)?\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|live/))([A-Za-z0-9_-]{6,})~', $valeur, $m ) ) {
		return $m[1];
	}
	return preg_match( '/^[A-Za-z0-9_-]{6,}$/', $valeur ) ? $valeur : '';
}
