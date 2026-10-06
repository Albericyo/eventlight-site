<?php
/**
 * Types de contenu : formules, matériel en location, réalisations, questions fréquentes.
 * Adresses identiques à celles du site d'origine. Les demandes de devis et les réservations
 * sont dans planning.php.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Libellés d'administration d'un type de contenu.
 *
 * @param string $un      « une formule », « un produit ».
 * @param string $pluriel « Formules ».
 * @param bool   $feminin Accord des adjectifs.
 */
function el_libelles( $un, $pluriel, $feminin = false ) {
	$nom       = preg_replace( '/^une? /u', '', $un );
	$majuscule = function ( $s ) {
		return mb_strtoupper( mb_substr( $s, 0, 1 ) ) . mb_substr( $s, 1 );
	};
	$min       = mb_strtolower( $pluriel );
	return array(
		'name'                  => $pluriel,
		'singular_name'         => $majuscule( $nom ),
		'menu_name'             => $pluriel,
		'all_items'             => $feminin ? 'Toutes les ' . $min : 'Tous les ' . $min,
		'add_new'               => 'Ajouter',
		'add_new_item'          => 'Ajouter ' . $un,
		'edit_item'             => 'Modifier ' . ( $feminin ? 'la ' : 'le ' ) . $nom,
		'new_item'              => ( $feminin ? 'Nouvelle ' : 'Nouveau ' ) . $nom,
		'view_item'             => 'Voir sur le site',
		'view_items'            => 'Voir sur le site',
		'search_items'          => 'Rechercher',
		'not_found'             => $feminin ? 'Aucune ' . $nom . ' pour le moment.' : 'Aucun ' . $nom . ' pour le moment.',
		'not_found_in_trash'    => 'Rien dans la corbeille.',
		'item_published'        => $majuscule( $nom ) . ( $feminin ? ' publiée.' : ' publié.' ),
		'item_updated'          => $majuscule( $nom ) . ( $feminin ? ' mise à jour.' : ' mis à jour.' ),
		'featured_image'        => 'Image de couverture',
		'set_featured_image'    => 'Choisir l\'image de couverture',
		'remove_featured_image' => 'Retirer l\'image de couverture',
		'use_featured_image'    => 'Utiliser comme image de couverture',
	);
}

function el_types() {
	register_post_type(
		'el_formule',
		array(
			'labels'        => el_libelles( 'une formule', 'Formules', true ),
			'public'        => true,
			'has_archive'   => 'nos-formules',
			'rewrite'       => array(
				'slug'       => 'nos-formules',
				'with_front' => false,
				'feeds'      => false,
			),
			'menu_position' => 22,
			'menu_icon'     => 'dashicons-tickets-alt',
			'supports'      => array( 'title', 'page-attributes' ),
			'show_in_rest'  => false,
		)
	);

	register_post_type(
		'el_produit',
		array(
			'labels'        => array_merge( el_libelles( 'un produit', 'Produits' ), array( 'menu_name' => 'Location' ) ),
			'public'        => true,
			'has_archive'   => 'location',
			'rewrite'       => array(
				'slug'       => 'location',
				'with_front' => false,
				'feeds'      => false,
			),
			'menu_position' => 23,
			'menu_icon'     => 'dashicons-lightbulb',
			'supports'      => array( 'title', 'page-attributes' ),
			'show_in_rest'  => false,
		)
	);

	register_taxonomy(
		'el_categorie',
		'el_produit',
		array(
			'labels'            => array(
				'name'          => 'Catégories de matériel',
				'singular_name' => 'Catégorie',
				'menu_name'     => 'Catégories',
				'all_items'     => 'Toutes les catégories',
				'edit_item'     => 'Modifier la catégorie',
				'view_item'     => 'Voir sur le site',
				'update_item'   => 'Mettre à jour',
				'add_new_item'  => 'Ajouter une catégorie',
				'new_item_name' => 'Nom de la catégorie',
				'search_items'  => 'Rechercher',
				'not_found'     => 'Aucune catégorie.',
				'back_to_items' => 'Retour aux catégories',
			),
			'public'            => true,
			'hierarchical'      => true,
			'show_admin_column' => true,
			'show_in_rest'      => false,
			'rewrite'           => false,
			'query_var'         => 'el_categorie',
		)
	);

	register_post_type(
		'el_realisation',
		array(
			'labels'        => el_libelles( 'une réalisation', 'Réalisations', true ),
			'public'        => true,
			'has_archive'   => 'portfolio',
			'rewrite'       => array(
				'slug'       => 'portfolio',
				'with_front' => false,
				'feeds'      => false,
			),
			'menu_position' => 24,
			'menu_icon'     => 'dashicons-format-video',
			'supports'      => array( 'title', 'thumbnail', 'page-attributes' ),
			'show_in_rest'  => false,
		)
	);

	register_taxonomy(
		'el_type',
		'el_realisation',
		array(
			'labels'             => array(
				'name'          => 'Types de réalisation',
				'singular_name' => 'Type',
				'menu_name'     => 'Types',
				'all_items'     => 'Tous les types',
				'edit_item'     => 'Modifier le type',
				'update_item'   => 'Mettre à jour',
				'add_new_item'  => 'Ajouter un type',
				'new_item_name' => 'Nom du type',
				'search_items'  => 'Rechercher',
				'not_found'     => 'Aucun type.',
				'back_to_items' => 'Retour aux types',
			),
			'public'             => false,
			'show_ui'            => true,
			'hierarchical'       => true,
			'show_admin_column'  => true,
			'show_in_rest'       => false,
			'rewrite'            => false,
			'query_var'          => false,
			'publicly_queryable' => false,
		)
	);

	register_post_type(
		'el_question',
		array(
			'labels'       => array_merge( el_libelles( 'une question', 'Questions fréquentes', true ), array( 'all_items' => 'Questions fréquentes' ) ),
			'public'       => false,
			'show_ui'      => true,
			'show_in_menu' => 'eventlight',
			'supports'     => array( 'title', 'page-attributes' ),
			'show_in_rest' => false,
		)
	);
}
add_action( 'init', 'el_types' );

/** Les types de contenu du thème que le visiteur peut consulter. */
function el_types_publics() {
	return array( 'el_formule', 'el_produit', 'el_realisation' );
}

/**
 * /location/son/ est une catégorie, /location/elokance-800c/ un produit : même forme d'adresse.
 * WordPress croit lire un produit ; si le nom est celui d'une catégorie, on corrige la demande.
 */
function el_requete_categorie( $vars ) {
	if ( is_admin() || empty( $vars['el_produit'] ) ) {
		return $vars;
	}
	$nom = $vars['el_produit'];
	if ( get_term_by( 'slug', $nom, 'el_categorie' ) ) {
		unset( $vars['el_produit'], $vars['post_type'], $vars['name'], $vars['page'] );
		$vars['el_categorie'] = $nom;
	}
	return $vars;
}
add_filter( 'request', 'el_requete_categorie' );

function el_lien_categorie( $lien, $terme, $taxonomie ) {
	if ( 'el_categorie' === $taxonomie && get_option( 'permalink_structure' ) ) {
		return home_url( user_trailingslashit( '/location/' . $terme->slug ) );
	}
	return $lien;
}
add_filter( 'term_link', 'el_lien_categorie', 10, 3 );

/**
 * Les pages de liste du thème affichent tout leur contenu d'un coup, avec leurs propres requêtes.
 * La requête principale de WordPress est réduite au minimum.
 */
function el_requete_principale( $query ) {
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}
	if ( $query->is_post_type_archive( el_types_publics() ) || $query->is_tax( 'el_categorie' ) ) {
		$query->set( 'posts_per_page', 1 );
		$query->set( 'no_found_rows', true );
	}
}
add_action( 'pre_get_posts', 'el_requete_principale' );

/** Dans l'administration, les listes suivent l'ordre choisi (champ « Ordre »), puis le titre. */
function el_ordre_admin( $query ) {
	if ( ! is_admin() || ! $query->is_main_query() || $query->get( 'orderby' ) ) {
		return;
	}
	$type = $query->get( 'post_type' );
	if ( in_array( $type, array( 'el_formule', 'el_produit', 'el_realisation', 'el_question' ), true ) ) {
		$query->set(
			'orderby',
			array(
				'menu_order' => 'ASC',
				'title'      => 'ASC',
			)
		);
	}
}
add_action( 'pre_get_posts', 'el_ordre_admin' );

/** Les pages 2, 3… des listes n'existent pas : retour à la liste. */
function el_pas_de_pagination() {
	if ( is_paged() && ( is_post_type_archive( el_types_publics() ) || is_tax( 'el_categorie' ) ) ) {
		$cible = is_tax() ? get_term_link( get_queried_object() ) : get_post_type_archive_link( get_query_var( 'post_type' ) );
		if ( $cible && ! is_wp_error( $cible ) ) {
			wp_safe_redirect( $cible, 301 );
			exit;
		}
	}
}
add_action( 'template_redirect', 'el_pas_de_pagination' );

/** Les adresses du thème sont enregistrées à l'activation, puis après chaque mise à jour du thème. */
function el_adresses_a_jour() {
	$version = wp_get_theme()->get( 'Version' );
	if ( get_option( 'el_adresses' ) !== $version ) {
		flush_rewrite_rules( false );
		update_option( 'el_adresses', $version );
	}
}
add_action( 'init', 'el_adresses_a_jour', 99 );

function el_activation() {
	delete_option( 'el_adresses' );
}
add_action( 'after_switch_theme', 'el_activation' );

/** L'éditeur de blocs ne sert pas aux contenus du thème : chacun a son propre formulaire. */
function el_sans_blocs( $utiliser, $type ) {
	if ( in_array( $type, array( 'el_formule', 'el_produit', 'el_realisation', 'el_question', 'el_dossier' ), true ) ) {
		return false;
	}
	return $utiliser;
}
add_filter( 'use_block_editor_for_post_type', 'el_sans_blocs', 10, 2 );
