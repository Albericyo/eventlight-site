<?php
/**
 * Plan de feu : le dessin au trait d'une installation, composé à partir de « couches ».
 *
 * Chaque couche correspond à un élément d'un pack (sono, totems, étincelles…).
 * - el_couches( $lignes ) lit les lignes d'un pack et en déduit les couches.
 * - el_plan_de_feu( $couches, $options ) renvoie le SVG. Avec l'option « complet », toutes les
 *   couches sont dessinées et masquées par défaut : le composeur de la page d'accueil les allume.
 *
 * Le projecteur de chaque totem reprend le signe du logo : un boîtier 11:10 et un faisceau
 * de pente 3:8.
 */

defined( 'ABSPATH' ) || exit;

const EL_PF_LARGEUR = 720;
const EL_PF_SOL     = 336;
const EL_PF_BAS     = 346;
const EL_PF_PENTE   = 0.375;

/** Hauteur à laquelle commence chaque couche : le cadre du dessin s'ajuste à la plus haute. */
function el_pf_sommets() {
	return array(
		'ceremonie'  => 22,
		'mapping'    => 44,
		'archi'      => 88,
		'brouillard' => 108,
		'totems-4'   => 164,
		'totems-2'   => 164,
		'son'        => 176,
		'ondes'      => 176,
		'geysers'    => 184,
		'etincelles' => 210,
		'animateur'  => 230,
		'micro'      => 238,
		'pupitre'    => 246,
		'fumee'      => 304,
	);
}

/** Nom de chaque couche, tel qu'on le lit dans l'administration. */
function el_pf_libelles() {
	return array(
		'son'        => 'Sono',
		'ondes'      => 'Musique d\'ambiance',
		'totems-2'   => '2 totems et leurs projecteurs',
		'totems-4'   => '4 totems et leurs projecteurs',
		'brouillard' => 'Brouillard',
		'etincelles' => 'Étincelles froides',
		'fumee'      => 'Fumée lourde',
		'geysers'    => 'Geysers',
		'animateur'  => 'Animateur',
		'micro'      => 'Micro sans fil',
		'pupitre'    => 'Pupitre',
		'ceremonie'  => 'Kit cérémonie',
		'archi'      => 'Éclairage architectural',
		'mapping'    => 'Vidéo mapping',
	);
}

/** Lit les lignes d'un pack et renvoie les couches à dessiner. Première règle qui correspond. */
function el_couches( $lignes ) {
	$regles = array(
		array( '/(\d+)\s+totems?/iu', null ),
		array( '/c[ée]r[ée]monie/iu', 'ceremonie' ),
		array( '/syst[èe]me son|sono/iu', 'son' ),
		array( '/brouillard/iu', 'brouillard' ),
		array( '/[ée]tincelles/iu', 'etincelles' ),
		array( '/fum[ée]e lourde/iu', 'fumee' ),
		array( '/geysers?/iu', 'geysers' ),
		array( '/animateur/iu', 'animateur' ),
		array( '/pupitre/iu', 'pupitre' ),
		array( '/micro/iu', 'micro' ),
		array( '/[ée]clairage architectural/iu', 'archi' ),
		array( '/musiques? d\'ambiance/iu', 'ondes' ),
		array( '/mapping/iu', 'mapping' ),
	);
	$couches = array();
	foreach ( (array) $lignes as $ligne ) {
		foreach ( $regles as $regle ) {
			if ( preg_match( $regle[0], (string) $ligne, $m ) ) {
				$couche = null === $regle[1] ? 'totems-' . ( '2' === $m[1] ? 2 : 4 ) : $regle[1];
				if ( ! in_array( $couche, $couches, true ) ) {
					$couches[] = $couche;
				}
				break;
			}
		}
	}
	return $couches;
}

/** Un nombre arrondi au dixième, écrit au plus court. */
function el_pf_n( $v ) {
	$s = number_format( floor( $v * 10 + 0.5 ) / 10, 1, '.', '' );
	$s = rtrim( rtrim( $s, '0' ), '.' );
	return ( '-0' === $s || '' === $s ) ? '0' : $s;
}

function el_pf_points( $liste ) {
	$sortie = array();
	foreach ( $liste as $p ) {
		$sortie[] = el_pf_n( $p[0] ) . ',' . el_pf_n( $p[1] );
	}
	return implode( ' ', $sortie );
}

/** Coupe un polygone au niveau du sol : on ne garde que ce qui est au-dessus. */
function el_pf_au_dessus_du_sol( $poly ) {
	$sortie = array();
	$nombre = count( $poly );
	for ( $i = 0; $i < $nombre; $i++ ) {
		$a      = $poly[ $i ];
		$b      = $poly[ ( $i + 1 ) % $nombre ];
		$a_dans = $a[1] <= EL_PF_SOL;
		$b_dans = $b[1] <= EL_PF_SOL;
		if ( $a_dans ) {
			$sortie[] = $a;
		}
		if ( $a_dans !== $b_dans ) {
			$t        = ( EL_PF_SOL - $a[1] ) / ( $b[1] - $a[1] );
			$sortie[] = array( $a[0] + $t * ( $b[0] - $a[0] ), EL_PF_SOL );
		}
	}
	return $sortie;
}

/** Faisceau : pointe en (x, y), direction en degrés (0 = vers la droite, 90 = vers le bas). */
function el_pf_faisceau( $x, $y, $angle, $longueur ) {
	$a  = ( $angle * M_PI ) / 180;
	$ex = $x + $longueur * cos( $a );
	$ey = $y + $longueur * sin( $a );
	$d  = $longueur * EL_PF_PENTE;
	$px = -sin( $a ) * $d;
	$py = cos( $a ) * $d;
	return '<polygon class="pf-lum" points="' . el_pf_points(
		el_pf_au_dessus_du_sol(
			array(
				array( $x, $y ),
				array( $ex + $px, $ey + $py ),
				array( $ex - $px, $ey - $py ),
			)
		)
	) . '"/>';
}

function el_pf_totem( $x, $angle ) {
	return array(
		'faisceau' => el_pf_faisceau( $x, 182, $angle, 250 ),
		'corps'    =>
			'<rect class="pf-plein" x="' . el_pf_n( $x - 8 ) . '" y="196" width="16" height="135"/>' .
			'<rect x="' . el_pf_n( $x - 18 ) . '" y="331" width="36" height="5"/>' .
			'<path d="M' . el_pf_n( $x - 13 ) . ' 196H' . el_pf_n( $x + 13 ) . '"/>' .
			'<rect class="pf-plein" x="' . el_pf_n( $x - 11 ) . '" y="172" width="22" height="20"/>',
	);
}

function el_pf_enceinte( $x, $sens ) {
	$o     = $x + $sens * 19;
	$ondes = '';
	foreach ( array( 16, 30, 44 ) as $r ) {
		$y0     = 212 - $r * 0.64;
		$y1     = 212 + $r * 0.64;
		$dx     = $sens * $r * 0.77;
		$ondes .= '<path d="M' . el_pf_n( $o + $dx ) . ' ' . el_pf_n( $y0 ) . 'A' . $r . ' ' . $r . ' 0 0 ' . ( $sens > 0 ? 1 : 0 ) . ' ' . el_pf_n( $o + $dx ) . ' ' . el_pf_n( $y1 ) . '"/>';
	}
	return array(
		'corps' =>
			'<rect class="pf-plein" x="' . el_pf_n( $x - 30 ) . '" y="288" width="60" height="48"/>' .
			'<circle cx="' . el_pf_n( $x ) . '" cy="312" r="15"/>' .
			'<path d="M' . el_pf_n( $x ) . ' 288V240"/>' .
			'<rect class="pf-plein" x="' . el_pf_n( $x - 19 ) . '" y="184" width="38" height="56"/>' .
			'<circle cx="' . el_pf_n( $x ) . '" cy="220" r="11"/><circle cx="' . el_pf_n( $x ) . '" cy="198" r="4"/>',
		'ondes' => $ondes,
	);
}

function el_pf_fontaine( $x ) {
	$angles    = array( -22, -16.5, -11, -5.5, 0, 5.5, 11, 16.5, 22 );
	$longueurs = array( 54, 72, 88, 100, 108, 100, 88, 72, 54 );
	$rayons    = '';
	foreach ( $angles as $i => $deg ) {
		$a       = ( ( $deg - 90 ) * M_PI ) / 180;
		$rayons .= '<path d="M' . el_pf_n( $x ) . ' 326L' . el_pf_n( $x + $longueurs[ $i ] * cos( $a ) ) . ' ' . el_pf_n( 326 + $longueurs[ $i ] * sin( $a ) ) . '"/>';
	}
	return '<g class="pf-jets">' . $rayons . '</g><rect class="pf-plein" x="' . el_pf_n( $x - 8 ) . '" y="326" width="16" height="10"/>';
}

function el_pf_geyser( $x ) {
	return '<path class="pf-lum" d="M' . el_pf_n( $x - 4 ) . ' 324L' . el_pf_n( $x - 13 ) . ' 204a13 13 0 0 1 26 0L' . el_pf_n( $x + 4 ) . ' 324Z"/>' .
		'<rect class="pf-plein" x="' . el_pf_n( $x - 7 ) . '" y="324" width="14" height="12"/>';
}

function el_pf_projecteur_sol( $x ) {
	return array(
		'faisceau' => '<polygon class="pf-lum" points="' . el_pf_points(
			array(
				array( $x, 330 ),
				array( $x - 30, 96 ),
				array( $x + 30, 96 ),
			)
		) . '"/>',
		'corps'    => '<rect class="pf-plein" x="' . el_pf_n( $x - 7 ) . '" y="328" width="14" height="8"/>',
	);
}

/** Brume : une ligne ondulée en travers de la scène. */
function el_pf_brume( $y, $x0, $x1, $pas ) {
	$d = 'M' . $x0 . ' ' . $y;
	for ( $x = $x0, $i = 0; $x < $x1; $x += $pas, $i++ ) {
		$d .= 'q' . el_pf_n( $pas / 2 ) . ' ' . ( $i % 2 ? 7 : -7 ) . ' ' . $pas . ' 0';
	}
	return '<path d="' . $d . '"/>';
}

/** Dessins de chaque couche, du fond vers l'avant. */
function el_pf_dessins() {
	static $dessins = null;
	if ( null !== $dessins ) {
		return $dessins;
	}

	$t4   = array( el_pf_totem( 150, 24 ), el_pf_totem( 240, 50 ), el_pf_totem( 480, 130 ), el_pf_totem( 570, 156 ) );
	$t2   = array( el_pf_totem( 200, 34 ), el_pf_totem( 520, 146 ) );
	$eg   = el_pf_enceinte( 66, 1 );
	$ed   = el_pf_enceinte( 654, -1 );
	$sols = array_map( 'el_pf_projecteur_sol', array( 150, 255, 360, 465, 570 ) );

	$colonne  = function ( $liste, $cle ) {
		$s = '';
		foreach ( $liste as $element ) {
			$s .= $element[ $cle ];
		}
		return $s;
	};
	$fenetres = '';
	foreach ( array( 258, 312, 382, 436 ) as $x ) {
		$fenetres .= '<rect x="' . $x . '" y="116" width="26" height="34"/><rect x="' . $x . '" y="176" width="26" height="40"/>';
	}

	$dessins = array(
		'archi'      => '<path d="M112 96H608"/>' . $colonne( $sols, 'faisceau' ) . $colonne( $sols, 'corps' ),
		'mapping'    =>
			'<path class="pf-lum" d="M236 246V96L360 52L484 96V246Z"/>' .
			$fenetres .
			'<path d="M236 96H484M300 246V96M420 246V96"/>',
		'brouillard' => '<g class="pf-brume">' . el_pf_brume( 136, 162, 558, 44 ) . el_pf_brume( 160, 118, 602, 44 ) . '</g>',
		'totems-4'   => $colonne( $t4, 'faisceau' ) . $colonne( $t4, 'corps' ),
		'totems-2'   => $colonne( $t2, 'faisceau' ) . $colonne( $t2, 'corps' ),
		'son'        => $eg['corps'] . $ed['corps'],
		'ondes'      => '<g class="pf-ondes">' . $eg['ondes'] . $ed['ondes'] . '</g>',
		'ceremonie'  =>
			'<path d="M24 100H132M52 100V56a26 26 0 0 1 52 0V100"/>' .
			'<rect class="pf-plein" x="30" y="70" width="12" height="18"/><path d="M36 88V100"/>' .
			'<rect class="pf-plein" x="114" y="70" width="12" height="18"/><path d="M120 88V100"/>' .
			'<circle cx="78" cy="82" r="3.5"/><path d="M78 85.5V100"/>',
		'etincelles' => el_pf_fontaine( 195 ) . el_pf_fontaine( 525 ),
		'geysers'    => el_pf_geyser( 122 ) . el_pf_geyser( 598 ),
		'pupitre'    => '<path class="pf-plein" d="M336 268L342 254H378L384 268Z"/><rect class="pf-plein" x="354" y="268" width="12" height="68"/>',
		'animateur'  =>
			'<circle class="pf-plein" cx="360" cy="250" r="12"/><path d="M346 249a14 14 0 0 1 28 0"/>' .
			'<path class="pf-plein" d="M334 300V290q0 -24 26 -24t26 24V300Z"/>' .
			'<rect class="pf-plein" x="332" y="288" width="56" height="12"/>' .
			'<rect class="pf-plein" x="306" y="300" width="108" height="36"/>',
		'micro'      => '<path d="M440 336V264"/><rect class="pf-plein" x="435.5" y="246" width="9" height="18" rx="4.5"/>',
		'fumee'      => '<path class="pf-plein" d="M150 336V326' . str_repeat( 'a21 13 0 0 1 42 0', 10 ) . 'V336Z"/>',
	);
	return $dessins;
}

/**
 * Le dessin d'une installation.
 *
 * @param string[] $couches Couches à dessiner (voir el_pf_libelles()).
 * @param array    $options « titre » : texte lu par les lecteurs d'écran. « complet » : toutes les couches, masquées par défaut.
 */
function el_plan_de_feu( $couches, $options = array() ) {
	$actives = array_values( array_unique( (array) $couches ) );
	$complet = ! empty( $options['complet'] );
	$titre   = isset( $options['titre'] ) ? (string) $options['titre'] : '';

	$corps = '';
	foreach ( el_pf_dessins() as $nom => $svg ) {
		$active = in_array( $nom, $actives, true );
		if ( ! $complet && ! $active ) {
			continue;
		}
		$corps .= '<g data-couche="' . $nom . '"' . ( $complet && ! $active ? ' hidden' : '' ) . '>' . $svg . '</g>';
	}

	if ( $complet ) {
		$haut = 14;
	} else {
		$sommets = el_pf_sommets();
		$plus    = 246;
		foreach ( $actives as $c ) {
			$plus = min( $plus, isset( $sommets[ $c ] ) ? $sommets[ $c ] : 176 );
		}
		$haut = max( 14, $plus - 8 );
	}

	$balise_titre = '' !== $titre ? '<title>' . str_replace( array( '&', '<' ), array( '&amp;', '&lt;' ), $titre ) . '</title>' : '';
	$aria         = '' !== $titre ? 'role="img"' : 'aria-hidden="true" focusable="false"';

	return '<svg class="pf" viewBox="0 ' . $haut . ' ' . EL_PF_LARGEUR . ' ' . ( EL_PF_BAS - $haut ) . '" ' . $aria . '>' . $balise_titre .
		'<g fill="none" stroke="currentColor" stroke-linejoin="miter">' . $corps . '<path d="M24 ' . EL_PF_SOL . 'H696"/></g></svg>';
}
