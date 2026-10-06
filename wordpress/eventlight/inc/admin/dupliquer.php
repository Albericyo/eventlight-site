<?php
/**
 * Dupliquer un produit, une formule, une réalisation ou une réservation.
 *
 * Un lien « Dupliquer » dans la liste et dans la fiche. La copie garde tous les champs,
 * les photos et les catégories ; elle s'ouvre aussitôt, prête à être retouchée.
 * Produits, formules et réalisations repartent en brouillon : rien n'apparaît sur le site
 * avant que vous ne publiiez. Une réservation repart en « Demande reçue ».
 */

defined( 'ABSPATH' ) || exit;

/** Les contenus qu'on peut dupliquer. */
function el_dupliquer_types() {
	return array( 'el_produit', 'el_formule', 'el_realisation', 'el_dossier' );
}

/** L'adresse qui duplique un contenu. */
function el_dupliquer_lien( $id ) {
	return wp_nonce_url( admin_url( 'admin-post.php?action=el_dupliquer&contenu=' . (int) $id ), 'el_dupliquer_' . (int) $id );
}

/** Le lien dans les actions d'une ligne de la liste. */
function el_dupliquer_action_ligne( $actions, $post ) {
	if ( in_array( $post->post_type, el_dupliquer_types(), true ) && current_user_can( 'edit_post', $post->ID ) ) {
		$actions['el-dupliquer'] = '<a href="' . esc_url( el_dupliquer_lien( $post->ID ) ) . '">Dupliquer</a>';
	}
	return $actions;
}
add_filter( 'post_row_actions', 'el_dupliquer_action_ligne', 20, 2 );

/** Le lien dans la fiche, près du bouton Mettre à jour. */
function el_dupliquer_dans_la_fiche( $post ) {
	if ( ! $post || 'auto-draft' === $post->post_status || ! in_array( $post->post_type, el_dupliquer_types(), true ) || ! current_user_can( 'edit_post', $post->ID ) ) {
		return;
	}
	echo '<div class="misc-pub-section el-dupliquer"><a href="' . esc_url( el_dupliquer_lien( $post->ID ) ) . '">Dupliquer</a> <span class="description">(une copie, à retoucher)</span></div>';
}
add_action( 'post_submitbox_misc_actions', 'el_dupliquer_dans_la_fiche' );

/**
 * Fait la copie.
 *
 * @return int Identifiant de la copie, 0 en cas d'échec.
 */
function el_dupliquer_contenu( $id ) {
	$source = get_post( $id );
	if ( ! $source || ! in_array( $source->post_type, el_dupliquer_types(), true ) ) {
		return 0;
	}
	$dossier = 'el_dossier' === $source->post_type;
	$copie   = wp_insert_post(
		array(
			'post_type'    => $source->post_type,
			'post_status'  => $dossier ? 'publish' : 'draft',
			'post_title'   => $source->post_title . ' (copie)',
			'post_content' => $source->post_content,
			'post_excerpt' => $source->post_excerpt,
			'menu_order'   => $source->menu_order,
			'post_parent'  => $source->post_parent,
		),
		true
	);
	if ( is_wp_error( $copie ) || ! $copie ) {
		return 0;
	}
	foreach ( get_post_meta( $id ) as $cle => $valeurs ) {
		if ( in_array( $cle, array( '_edit_lock', '_edit_last', '_wp_old_slug' ), true ) ) {
			continue;
		}
		foreach ( $valeurs as $valeur ) {
			add_post_meta( $copie, $cle, wp_slash( maybe_unserialize( $valeur ) ) );
		}
	}
	if ( $dossier ) {
		update_post_meta( $copie, '_el_statut', 'demande' );
	}
	foreach ( get_object_taxonomies( $source->post_type ) as $taxonomie ) {
		$termes = wp_get_object_terms( $id, $taxonomie, array( 'fields' => 'ids' ) );
		if ( ! is_wp_error( $termes ) && $termes ) {
			wp_set_object_terms( $copie, $termes, $taxonomie );
		}
	}
	return (int) $copie;
}

function el_dupliquer_traiter() {
	$id = isset( $_GET['contenu'] ) ? absint( $_GET['contenu'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- vérifié juste après.
	check_admin_referer( 'el_dupliquer_' . $id );
	if ( ! $id || ! current_user_can( 'edit_post', $id ) ) {
		wp_die( 'Action non autorisée.' );
	}
	$copie = el_dupliquer_contenu( $id );
	if ( ! $copie ) {
		wp_die( 'La copie n\'a pas pu être faite.' );
	}
	wp_safe_redirect( admin_url( 'post.php?post=' . $copie . '&action=edit' ) );
	exit;
}
add_action( 'admin_post_el_dupliquer', 'el_dupliquer_traiter' );
