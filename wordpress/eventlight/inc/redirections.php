<?php
/**
 * Anciennes adresses du site (Google Sites, puis site statique) et plan du site.
 */

defined( 'ABSPATH' ) || exit;

/** Une ancienne adresse qui ne mène plus nulle part est renvoyée vers la bonne page. */
function el_anciennes_adresses() {
	if ( ! is_404() ) {
		return;
	}
	$chemin = rawurldecode( trim( el_chemin_courant(), '/' ) );
	$carte  = array(
		'accueil'                           => '/',
		'nos-formules/comité-dentreprise'   => '/nos-formules/comite-entreprise/',
		'location/louer'                    => '/location/',
		'conditions-générales-de-vente'     => '/mentions-legales/',
		'conditions-generales-de-vente'     => '/mentions-legales/',
		'cgv'                               => '/mentions-legales/',
		'realisations'                      => '/portfolio/',
		'formules'                          => '/nos-formules/',
		'404.html'                          => '/',
	);
	if ( isset( $carte[ $chemin ] ) ) {
		wp_safe_redirect( el_url( $carte[ $chemin ] ), 301 );
		exit;
	}
}
add_action( 'template_redirect', 'el_anciennes_adresses' );

/** Le plan du site que WordPress fournit aux moteurs de recherche : sans les comptes, sans les pages non indexées. */
function el_plan_sans_comptes( $fournisseur, $nom ) {
	return 'users' === $nom ? false : $fournisseur;
}
add_filter( 'wp_sitemaps_add_provider', 'el_plan_sans_comptes', 10, 2 );

function el_plan_sans_pages_cachees( $args, $type ) {
	if ( 'page' === $type ) {
		$args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			'relation' => 'OR',
			array(
				'key'     => '_el_noindex',
				'compare' => 'NOT EXISTS',
			),
			array(
				'key'     => '_el_noindex',
				'value'   => '1',
				'compare' => '!=',
			),
		);
	}
	return $args;
}
add_filter( 'wp_sitemaps_posts_query_args', 'el_plan_sans_pages_cachees', 10, 2 );

/**
 * La charte graphique est un document interne : elle n'est pas une page du site.
 * Elle est servie à l'adresse /?el-charte=1 aux seuls administrateurs (c'est ce qu'affiche
 * le menu Event'Light > Charte graphique). Pour tous les autres, cette adresse n'existe pas.
 */
function el_charte_servir() {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( ! isset( $_GET['el-charte'] ) ) {
		return;
	}
	if ( ! current_user_can( 'manage_options' ) ) {
		global $wp_query;
		$wp_query->set_404();
		status_header( 404 );
		nocache_headers();
		return;
	}
	nocache_headers();
	header( 'X-Robots-Tag: noindex, nofollow' );
	status_header( 200 );
	add_filter( 'show_admin_bar', '__return_false' );
	add_filter(
		'pre_get_document_title',
		function () {
			return 'Charte graphique | Event\'Light';
		}
	);
	require get_theme_file_path( 'inc/charte.php' );
	exit;
}
add_action( 'template_redirect', 'el_charte_servir', 1 );

/**
 * Les premières versions du thème créaient une page « charte » publique. On la supprime, une seule fois,
 * si c'est bien la page créée par l'import (gabarit page-charte.php).
 */
function el_retirer_page_charte() {
	if ( get_option( 'el_charte_retiree' ) ) {
		return;
	}
	$pages = get_posts(
		array(
			'post_type'      => 'page',
			'post_status'    => array( 'any', 'trash' ),
			'name'           => 'charte',
			'posts_per_page' => 5,
			'no_found_rows'  => true,
		)
	);
	$pages = array_merge(
		$pages,
		get_posts(
			array(
				'post_type'      => 'page',
				'post_status'    => 'trash',
				'name'           => 'charte__trashed',
				'posts_per_page' => 5,
				'no_found_rows'  => true,
			)
		)
	);
	foreach ( $pages as $p ) {
		if ( 'page-charte.php' === get_post_meta( $p->ID, '_wp_page_template', true ) ) {
			wp_delete_post( $p->ID, true );
		}
	}
	update_option( 'el_charte_retiree', '1', false );
}
add_action( 'init', 'el_retirer_page_charte', 20 );

/** Les trois pages de liste (formules, location, réalisations) figurent aussi dans le plan du site. */
function el_plan_listes() {
	if ( ! class_exists( 'WP_Sitemaps_Provider' ) || class_exists( 'EL_Plan_Listes' ) ) {
		return;
	}
	// phpcs:ignore Generic.Files.OneObjectStructurePerFile.MultipleFound, Generic.Classes.OpeningBraceSameLine
	class EL_Plan_Listes extends WP_Sitemaps_Provider {
		public function __construct() {
			$this->name        = 'listes';
			$this->object_type = 'listes';
		}

		public function get_url_list( $page_num, $object_subtype = '' ) {
			$adresses = array();
			foreach ( el_types_publics() as $type ) {
				$lien = get_post_type_archive_link( $type );
				if ( $lien ) {
					$adresses[] = array( 'loc' => $lien );
				}
			}
			return $adresses;
		}

		public function get_max_num_pages( $object_subtype = '' ) {
			return 1;
		}
	}
	wp_register_sitemap_provider( 'listes', new EL_Plan_Listes() );
}
add_action( 'wp_sitemaps_init', 'el_plan_listes' );
