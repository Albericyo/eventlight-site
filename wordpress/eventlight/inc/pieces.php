<?php
/**
 * Les pièces de structure : ce qu'on possède vraiment, et ce que chaque produit en consomme.
 *
 * Un totem n'existe pas tout seul : c'est une embase et un truss. Un pont de 4 m, ce sont deux
 * treuils et deux truss de 2 m. Comme les mêmes pièces servent à plusieurs produits, on compte
 * les pièces, pas les produits : un truss de 2 m posé dans un totem n'est plus dans le pont.
 *
 * Un produit « a une recette » quand on lui a indiqué ses pièces. Son stock est alors calculé
 * (ce qu'on peut en monter au maximum), et ses disponibilités, par dates, aussi.
 */

defined( 'ABSPATH' ) || exit;

/** Les pièces, dans l'ordre où on les range. */
function el_pieces_catalogue() {
	return array(
		'truss-2m'   => 'Truss 2 m',
		'truss-1-5m' => 'Truss 1,50 m',
		'embase'     => 'Embase de totem',
		'treuil'     => 'Treuil 4 m',
		'monotube'   => 'Monotube',
	);
}

/** Les pièces suivies : pièce => quantité possédée. Une pièce sans quantité n'est pas comptée. */
function el_pieces_stocks() {
	$option = get_option( 'el_pieces', array() );
	$stocks = array();
	if ( is_array( $option ) ) {
		foreach ( el_pieces_catalogue() as $piece => $nom ) {
			if ( isset( $option[ $piece ] ) && '' !== $option[ $piece ] && is_numeric( $option[ $piece ] ) ) {
				$stocks[ $piece ] = max( 0, (int) $option[ $piece ] );
			}
		}
	}
	return $stocks;
}

/** La recette d'un produit : pièce => quantité par exemplaire. Vide quand le produit n'en a pas. */
function el_recette( $produit_id ) {
	$brut   = get_post_meta( (int) $produit_id, '_el_pieces', true );
	$recette = array();
	if ( is_array( $brut ) ) {
		$catalogue = el_pieces_catalogue();
		foreach ( $brut as $piece => $q ) {
			if ( isset( $catalogue[ $piece ] ) && (int) $q > 0 ) {
				$recette[ $piece ] = (int) $q;
			}
		}
	}
	return $recette;
}

/** Les recettes de tous les produits publiés qui en ont une : identifiant => recette. */
function el_recettes() {
	static $recettes = null;
	if ( null === $recettes ) {
		$recettes = array();
		foreach ( el_contenus( 'el_produit' ) as $post ) {
			$r = el_recette( $post->ID );
			if ( $r ) {
				$recettes[ $post->ID ] = $r;
			}
		}
	}
	return $recettes;
}

/** Combien d'exemplaires on peut monter avec ces pièces. Null quand aucune des pièces n'est suivie. */
function el_recette_capacite( $recette, $pieces ) {
	$max = null;
	foreach ( $recette as $piece => $q ) {
		if ( ! isset( $pieces[ $piece ] ) ) {
			continue;
		}
		$n   = (int) floor( $pieces[ $piece ] / $q );
		$max = null === $max ? $n : min( $max, $n );
	}
	return null === $max ? null : max( 0, $max );
}

/** Les pièces que consomme une liste de lignes ( produit, q ) : pièce => quantité. */
function el_pieces_utilisees( $lignes ) {
	$recettes = el_recettes();
	$total    = array();
	foreach ( $lignes as $l ) {
		if ( empty( $recettes[ $l['produit'] ] ) ) {
			continue;
		}
		foreach ( $recettes[ $l['produit'] ] as $piece => $q ) {
			$total[ $piece ] = ( isset( $total[ $piece ] ) ? $total[ $piece ] : 0 ) + $q * $l['q'];
		}
	}
	return $total;
}

/**
 * Les pièces sorties sur une période, jour le plus chargé de la période.
 *
 * @return array pièce => array( 'ferme' => confirmé, 'attente' => demandé ailleurs ).
 */
function el_pieces_sorties( $debut, $fin, $sauf = 0 ) {
	$par_jour = array(
		'ferme'   => array(),
		'attente' => array(),
	);
	$fermes   = el_statuts_fermes();
	foreach ( el_dossiers_entre( $debut, $fin ) as $d ) {
		if ( $d['id'] === (int) $sauf || ! $d['lignes'] ) {
			continue;
		}
		$utilisees = el_pieces_utilisees( $d['lignes'] );
		if ( ! $utilisees ) {
			continue;
		}
		$sorte = in_array( $d['statut'], $fermes, true ) ? 'ferme' : 'attente';
		foreach ( el_jours( max( $d['debut'], $debut ), min( $d['fin'], $fin ) ) as $jour ) {
			foreach ( $utilisees as $piece => $q ) {
				$par_jour[ $sorte ][ $piece ][ $jour ] = ( isset( $par_jour[ $sorte ][ $piece ][ $jour ] ) ? $par_jour[ $sorte ][ $piece ][ $jour ] : 0 ) + $q;
			}
		}
	}
	$sorties = array();
	foreach ( el_pieces_stocks() as $piece => $stock ) {
		$sorties[ $piece ] = array(
			'ferme'   => isset( $par_jour['ferme'][ $piece ] ) ? max( $par_jour['ferme'][ $piece ] ) : 0,
			'attente' => isset( $par_jour['attente'][ $piece ] ) ? max( $par_jour['attente'][ $piece ] ) : 0,
		);
	}
	return $sorties;
}

/** Ce qu'il reste de chaque pièce sur une période : pièce => array( 'stock', 'ferme', 'attente', 'dispo' ). */
function el_pieces_dispos( $debut, $fin, $sauf = 0 ) {
	$sorties = el_pieces_sorties( $debut, $fin, $sauf );
	$dispos  = array();
	foreach ( el_pieces_stocks() as $piece => $stock ) {
		$dispos[ $piece ] = array(
			'stock'   => $stock,
			'ferme'   => $sorties[ $piece ]['ferme'],
			'attente' => $sorties[ $piece ]['attente'],
			'dispo'   => $stock - $sorties[ $piece ]['ferme'],
		);
	}
	return $dispos;
}

/**
 * Les pièces qui manqueraient si ce dossier était confirmé tel quel.
 *
 * @return array array( 'piece', 'nom', 'demande', 'dispo' ).
 */
function el_pieces_manques( $dossier ) {
	if ( ! $dossier || '' === $dossier['debut'] || ! $dossier['lignes'] ) {
		return array();
	}
	$utilisees = el_pieces_utilisees( $dossier['lignes'] );
	if ( ! $utilisees ) {
		return array();
	}
	$dispos    = el_pieces_dispos( $dossier['debut'], $dossier['fin'], $dossier['id'] );
	$catalogue = el_pieces_catalogue();
	$manques   = array();
	foreach ( $utilisees as $piece => $q ) {
		if ( isset( $dispos[ $piece ] ) && $q > $dispos[ $piece ]['dispo'] ) {
			$manques[] = array(
				'piece'   => $piece,
				'nom'     => $catalogue[ $piece ],
				'demande' => $q,
				'dispo'   => max( 0, $dispos[ $piece ]['dispo'] ),
			);
		}
	}
	return $manques;
}

/**
 * Ramène des lignes ( produit, q ) à ce que les pièces permettent, dans l'ordre des lignes.
 *
 * @return array array( lignes, bool ajuste ).
 */
function el_pieces_plafonner( $lignes ) {
	$pieces   = el_pieces_stocks();
	$recettes = el_recettes();
	$utilise  = array();
	$ajuste   = false;
	$sortie   = array();
	foreach ( $lignes as $l ) {
		if ( ! empty( $recettes[ $l['produit'] ] ) ) {
			$reste = array();
			foreach ( $pieces as $piece => $stock ) {
				$reste[ $piece ] = $stock - ( isset( $utilise[ $piece ] ) ? $utilise[ $piece ] : 0 );
			}
			$possible = el_recette_capacite( $recettes[ $l['produit'] ], $reste );
			if ( null !== $possible && $l['q'] > $possible ) {
				$l['q']  = $possible;
				$ajuste = true;
			}
			foreach ( $recettes[ $l['produit'] ] as $piece => $q ) {
				$utilise[ $piece ] = ( isset( $utilise[ $piece ] ) ? $utilise[ $piece ] : 0 ) + $q * $l['q'];
			}
		}
		if ( $l['q'] > 0 ) {
			$sortie[] = $l;
		}
	}
	return array( $sortie, $ajuste );
}

/** Les recettes et les pièces, lisibles par le script du site, pour plafonner la sélection. */
function el_pieces_pour_le_site() {
	$recettes = el_recettes();
	if ( ! $recettes ) {
		return;
	}
	$par_slug = array();
	foreach ( $recettes as $id => $recette ) {
		$post = get_post( $id );
		if ( $post ) {
			$par_slug[ $post->post_name ] = $recette;
		}
	}
	$donnees = array(
		'pieces'   => (object) el_pieces_stocks(),
		'recettes' => (object) $par_slug,
	);
	echo '<script type="application/json" id="el-pieces">' . wp_json_encode( $donnees, JSON_HEX_TAG | JSON_HEX_AMP ) . '</script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
add_action( 'wp_head', 'el_pieces_pour_le_site', 1 );
