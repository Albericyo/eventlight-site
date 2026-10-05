<?php
/**
 * Lecture du contenu : formules, catalogue, réalisations, questions.
 * Chaque fonction renvoie des tableaux simples, prêts pour les gabarits.
 */

defined( 'ABSPATH' ) || exit;

/** Une valeur enregistrée avec un contenu, sous forme de texte. */
function el_meta( $id, $cle ) {
	$v = get_post_meta( $id, '_el_' . $cle, true );
	return is_scalar( $v ) ? (string) $v : '';
}

/** Une valeur enregistrée avec un contenu, sous forme de liste. */
function el_meta_liste( $id, $cle ) {
	$v = get_post_meta( $id, '_el_' . $cle, true );
	if ( is_array( $v ) ) {
		return array_values( $v );
	}
	return el_lignes( $v );
}

/** Les contenus publiés d'un type, dans l'ordre choisi dans l'administration. */
function el_contenus( $type ) {
	return get_posts(
		array(
			'post_type'        => $type,
			'post_status'      => 'publish',
			'numberposts'      => -1,
			'orderby'          => array(
				'menu_order' => 'ASC',
				'title'      => 'ASC',
			),
			'suppress_filters' => false,
		)
	);
}

/* ---------------------------------------------------------------------- formules */

function el_formule( $post ) {
	$post = get_post( $post );
	if ( ! $post || 'el_formule' !== $post->post_type ) {
		return null;
	}
	$packs = array();
	$bruts = get_post_meta( $post->ID, '_el_packs', true );
	foreach ( is_array( $bruts ) ? $bruts : array() as $p ) {
		$nom = isset( $p['nom'] ) ? trim( (string) $p['nom'] ) : '';
		if ( '' === $nom ) {
			continue;
		}
		$packs[] = array(
			'nom'      => $nom,
			'prix'     => isset( $p['prix'] ) ? trim( (string) $p['prix'] ) : '',
			'featured' => ! empty( $p['featured'] ),
			'items'    => el_lignes( isset( $p['items'] ) ? $p['items'] : array() ),
			'materiel' => el_lignes_materiel( isset( $p['materiel'] ) ? $p['materiel'] : array() ),
		);
	}
	$types = array();
	foreach ( (array) get_post_meta( $post->ID, '_el_types', true ) as $terme_id ) {
		$terme = $terme_id ? get_term( (int) $terme_id, 'el_type' ) : null;
		if ( $terme && ! is_wp_error( $terme ) ) {
			$types[] = $terme->name;
		}
	}
	$titre = get_the_title( $post );
	$h1    = el_meta( $post->ID, 'h1' );
	$devis = el_meta( $post->ID, 'devis_type' );
	return array(
		'id'              => $post->ID,
		'slug'            => $post->post_name,
		'titre'           => $titre,
		'url'             => get_permalink( $post ),
		'accroche'        => el_meta( $post->ID, 'accroche' ),
		'h1'              => '' !== $h1 ? $h1 : $titre,
		'description'     => el_meta( $post->ID, 'description' ),
		'devisType'       => '' !== $devis ? $devis : $titre,
		'typesPortfolio'  => $types,
		'optionsHorsPack' => el_meta_liste( $post->ID, 'options' ),
		'packs'           => $packs,
	);
}

function el_formules() {
	static $cache = null;
	if ( null === $cache ) {
		$cache = array_values( array_filter( array_map( 'el_formule', el_contenus( 'el_formule' ) ) ) );
	}
	return $cache;
}

/** Le pack mis en avant d'une formule : celui marqué « recommandé », sinon le premier. */
function el_pack_recommande( $formule ) {
	if ( empty( $formule['packs'] ) ) {
		return null;
	}
	$choisi = $formule['packs'][0];
	foreach ( $formule['packs'] as $p ) {
		if ( $p['featured'] ) {
			$choisi = $p;
		}
	}
	return $choisi;
}

/* ---------------------------------------------------------------------- catalogue */

function el_categories() {
	static $cache = null;
	if ( null !== $cache ) {
		return $cache;
	}
	$cache  = array();
	$termes = get_terms(
		array(
			'taxonomy'   => 'el_categorie',
			'hide_empty' => false,
		)
	);
	if ( is_wp_error( $termes ) ) {
		return $cache;
	}
	foreach ( $termes as $t ) {
		$picto   = (string) get_term_meta( $t->term_id, 'el_picto', true );
		$h1      = (string) get_term_meta( $t->term_id, 'el_h1', true );
		$cache[] = array(
			'id'             => $t->term_id,
			'slug'           => $t->slug,
			'nom'            => $t->name,
			'url'            => get_term_link( $t ),
			'h1'             => '' !== $h1 ? $h1 : 'Location : ' . $t->name,
			'intro'          => trim( wp_strip_all_tags( $t->description ) ),
			'seoTitle'       => (string) get_term_meta( $t->term_id, 'el_seo_titre', true ),
			'seoDescription' => (string) get_term_meta( $t->term_id, 'el_seo_description', true ),
			'picto'          => '' !== $picto ? $picto : ( el_a_picto( $t->slug ) ? $t->slug : 'defaut' ),
			'ordre'          => (int) get_term_meta( $t->term_id, 'el_ordre', true ),
		);
	}
	usort(
		$cache,
		function ( $a, $b ) {
			if ( $a['ordre'] !== $b['ordre'] ) {
				return $a['ordre'] < $b['ordre'] ? -1 : 1;
			}
			return strcasecmp( $a['nom'], $b['nom'] );
		}
	);
	return $cache;
}

function el_categorie_par( $champ, $valeur ) {
	foreach ( el_categories() as $c ) {
		if ( $c[ $champ ] === $valeur ) {
			return $c;
		}
	}
	return null;
}

function el_produit( $post ) {
	$post = get_post( $post );
	if ( ! $post || 'el_produit' !== $post->post_type ) {
		return null;
	}
	$termes    = get_the_terms( $post, 'el_categorie' );
	$categorie = ( $termes && ! is_wp_error( $termes ) ) ? el_categorie_par( 'id', $termes[0]->term_id ) : null;

	$usages = array();
	foreach ( (array) get_post_meta( $post->ID, '_el_usages', true ) as $formule_id ) {
		$f = $formule_id ? get_post( (int) $formule_id ) : null;
		if ( $f && 'el_formule' === $f->post_type && 'publish' === $f->post_status ) {
			$usages[] = array(
				'id'    => $f->ID,
				'titre' => get_the_title( $f ),
				'url'   => get_permalink( $f ),
			);
		}
	}
	return array(
		'id'            => $post->ID,
		'slug'          => $post->post_name,
		'nom'           => get_the_title( $post ),
		'url'           => get_permalink( $post ),
		'categorie'     => $categorie ? $categorie['nom'] : '',
		'categorieSlug' => $categorie ? $categorie['slug'] : '',
		'categorieId'   => $categorie ? $categorie['id'] : 0,
		'categorieUrl'  => $categorie ? $categorie['url'] : get_post_type_archive_link( 'el_produit' ),
		'categoriePicto' => $categorie ? $categorie['picto'] : 'defaut',
		'prix'          => el_meta( $post->ID, 'prix' ),
		'description'   => el_meta( $post->ID, 'description' ),
		'details'       => el_meta_liste( $post->ID, 'details' ),
		'usages'        => $usages,
		'youtube'       => el_youtube_id( el_meta( $post->ID, 'youtube' ) ),
	);
}

function el_produits() {
	static $cache = null;
	if ( null === $cache ) {
		$posts = el_contenus( 'el_produit' );
		el_galeries_amorcer( $posts );
		$cache = array_values( array_filter( array_map( 'el_produit', $posts ) ) );
	}
	return $cache;
}

function el_produits_de_categorie( $categorie_id ) {
	$sortie = array();
	foreach ( el_produits() as $p ) {
		if ( $p['categorieId'] === $categorie_id ) {
			$sortie[] = $p;
		}
	}
	return $sortie;
}

/** Les produits qu'on sort pour une formule donnée. */
function el_produits_pour_formule( $formule_id ) {
	$sortie = array();
	foreach ( el_produits() as $p ) {
		foreach ( $p['usages'] as $u ) {
			if ( $u['id'] === $formule_id ) {
				$sortie[] = $p;
				break;
			}
		}
	}
	return $sortie;
}

/* ---------------------------------------------------------------------- réalisations */

function el_realisation( $post ) {
	$post = get_post( $post );
	if ( ! $post || 'el_realisation' !== $post->post_type ) {
		return null;
	}
	$termes = get_the_terms( $post, 'el_type' );
	return array(
		'id'          => $post->ID,
		'slug'        => $post->post_name,
		'titre'       => get_the_title( $post ),
		'url'         => get_permalink( $post ),
		'sousTitre'   => el_meta( $post->ID, 'sous_titre' ),
		'annee'       => el_meta( $post->ID, 'annee' ),
		'type'        => ( $termes && ! is_wp_error( $termes ) ) ? $termes[0]->name : '',
		'production'  => el_meta( $post->ID, 'production' ),
		'credits'     => el_meta_liste( $post->ID, 'credits' ),
		'logiciel'    => el_meta( $post->ID, 'logiciel' ),
		'technologie' => el_meta( $post->ID, 'technologie' ),
		'texte'       => el_meta( $post->ID, 'texte' ),
		'youtube'     => el_youtube_id( el_meta( $post->ID, 'youtube' ) ),
	);
}

function el_portfolio() {
	static $cache = null;
	if ( null === $cache ) {
		$posts = el_contenus( 'el_realisation' );
		el_galeries_amorcer( $posts );
		$cache = array_values( array_filter( array_map( 'el_realisation', $posts ) ) );
	}
	return $cache;
}

function el_realisation_par_id( $id ) {
	foreach ( el_portfolio() as $p ) {
		if ( $p['id'] === (int) $id ) {
			return $p;
		}
	}
	return null;
}

/** Les types de réalisation présents, dans l'ordre d'apparition, avec leur nombre. */
function el_types_de_realisation() {
	$types = array();
	foreach ( el_portfolio() as $p ) {
		if ( '' === $p['type'] ) {
			continue;
		}
		$types[ $p['type'] ] = isset( $types[ $p['type'] ] ) ? $types[ $p['type'] ] + 1 : 1;
	}
	return $types;
}

/* ---------------------------------------------------------------------- questions */

function el_faq() {
	static $cache = null;
	if ( null === $cache ) {
		$cache = array();
		foreach ( el_contenus( 'el_question' ) as $post ) {
			$reponse = el_meta( $post->ID, 'reponse' );
			if ( '' !== $reponse ) {
				$cache[] = array(
					'q' => get_the_title( $post ),
					'a' => $reponse,
				);
			}
		}
	}
	return $cache;
}

/* ---------------------------------------------------------------------- composeur */

/** Données du composeur de la page d'accueil : types d'événement, pistes, options, packs. */
function el_composeur() {
	$fichier = get_theme_file_path( 'data/composeur.json' );
	$base    = is_readable( $fichier ) ? json_decode( (string) file_get_contents( $fichier ), true ) : null;
	if ( ! is_array( $base ) ) {
		return null;
	}
	$formules = array();
	foreach ( el_formules() as $f ) {
		$packs = array();
		foreach ( $f['packs'] as $p ) {
			$packs[] = array(
				'nom'      => $p['nom'],
				'prix'     => $p['prix'],
				'featured' => $p['featured'],
				'items'    => $p['items'],
				'couches'  => el_couches( $p['items'] ),
			);
		}
		$formules[] = array(
			'slug'  => $f['slug'],
			'titre' => $f['titre'],
			'packs' => $packs,
		);
	}
	$site = el_site();
	return array(
		'eventTypes'  => isset( $base['eventTypes'] ) ? $base['eventTypes'] : array(),
		'tracks'      => isset( $base['tracks'] ) ? $base['tracks'] : array(),
		'addons'      => isset( $base['addons'] ) ? $base['addons'] : array(),
		'pricingNote' => el_reglage( 'note_prix' ),
		'tvaNote'     => $site['tva'],
		'formules'    => $formules,
	);
}
