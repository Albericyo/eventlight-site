<?php
/**
 * Écran « Pièces de structure » : ce qu'on possède, et de quoi chaque produit est fait.
 * Et la mise en place, une seule fois, des produits de structure du catalogue.
 */

defined( 'ABSPATH' ) || exit;

function el_pieces_menu() {
	add_submenu_page( 'edit.php?post_type=el_produit', 'Pièces de structure', 'Pièces de structure', 'edit_posts', 'el-pieces', 'el_page_pieces' );
}
add_action( 'admin_menu', 'el_pieces_menu' );

/** Les produits proposés dans la table des recettes : les structures, et tout produit qui a déjà une recette. */
function el_pieces_produits() {
	$recettes = el_recettes();
	$liste    = array();
	foreach ( el_produits_par_categorie() as $groupe => $produits ) {
		foreach ( $produits as $p ) {
			if ( 'Structures' === $groupe || isset( $recettes[ $p['id'] ] ) ) {
				$liste[] = $p;
			}
		}
	}
	return $liste;
}

function el_page_pieces() {
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_die( 'Accès refusé.' );
	}
	$catalogue = el_pieces_catalogue();
	$stocks    = el_pieces_stocks();
	$produits  = el_pieces_produits();
	$recettes  = el_recettes();
	$jour      = isset( $_GET['jour'] ) && el_est_date( sanitize_text_field( wp_unslash( $_GET['jour'] ) ) ) ? sanitize_text_field( wp_unslash( $_GET['jour'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$dispos    = '' !== $jour ? el_pieces_dispos( $jour, $jour ) : array();
	?>
<div class="wrap el-pieces">
	<h1>Pièces de structure</h1>
	<?php if ( isset( $_GET['maj'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
	<div class="notice notice-success is-dismissible"><p>Enregistré.</p></div>
	<?php endif; ?>
	<p>Un totem, c'est une embase et un truss. Un pont de 4 m, ce sont deux treuils et deux truss de 2 m. On compte donc les pièces : dès qu'un truss de 2 m est pris dans un totem, il n'est plus dans le pont. Le stock de chaque produit de structure est calculé à partir de là, et le devis, le planning et les disponibilités par dates en tiennent compte.</p>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="el_pieces">
		<?php wp_nonce_field( 'el_pieces' ); ?>

		<h2>Ce qu'on possède</h2>
		<table class="widefat striped" style="max-width:640px">
			<thead><tr><th>Pièce</th><th class="num">En stock</th><?php if ( $dispos ) : ?><th class="num">Libres le <?php echo esc_html( el_date_fr( $jour ) ); ?></th><?php endif; ?></tr></thead>
			<tbody>
			<?php foreach ( $catalogue as $piece => $nom ) : ?>
				<tr>
					<td><label for="el-piece-<?php echo esc_attr( $piece ); ?>"><?php echo esc_html( $nom ); ?></label></td>
					<td class="num"><input type="number" class="small-text" min="0" id="el-piece-<?php echo esc_attr( $piece ); ?>" name="el_pieces_stock[<?php echo esc_attr( $piece ); ?>]" value="<?php echo isset( $stocks[ $piece ] ) ? (int) $stocks[ $piece ] : ''; ?>"></td>
					<?php if ( $dispos ) : ?><td class="num"><?php echo isset( $dispos[ $piece ] ) ? (int) $dispos[ $piece ]['dispo'] : ''; ?></td><?php endif; ?>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>

		<h2>De quoi sont faits les produits</h2>
		<p class="description">Pour chaque exemplaire du produit, combien de chaque pièce. Laissez vide pour un produit qui n'est pas fait de pièces : son stock se saisit alors dans « Stock ».</p>
		<table class="widefat striped">
			<thead>
				<tr>
					<th>Produit</th>
					<?php foreach ( $catalogue as $nom ) : ?><th class="num"><?php echo esc_html( $nom ); ?></th><?php endforeach; ?>
					<th class="num">On peut en monter</th>
				</tr>
			</thead>
			<tbody>
			<?php foreach ( $produits as $p ) : ?>
				<tr>
					<td><a href="<?php echo esc_url( get_edit_post_link( $p['id'] ) ); ?>"><?php echo esc_html( $p['nom'] ); ?></a></td>
					<?php foreach ( $catalogue as $piece => $nom ) : ?>
					<td class="num"><input type="number" class="small-text" min="0" aria-label="<?php echo esc_attr( $p['nom'] . ' : ' . $nom ); ?>" name="el_pieces_recette[<?php echo (int) $p['id']; ?>][<?php echo esc_attr( $piece ); ?>]" value="<?php echo isset( $recettes[ $p['id'] ][ $piece ] ) ? (int) $recettes[ $p['id'] ][ $piece ] : ''; ?>"></td>
					<?php endforeach; ?>
					<td class="num"><?php echo isset( $recettes[ $p['id'] ] ) ? (int) el_recette_capacite( $recettes[ $p['id'] ], $stocks ) : ''; ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<?php submit_button( 'Enregistrer' ); ?>
	</form>

	<h2>Voir ce qui est libre un jour donné</h2>
	<form method="get">
		<input type="hidden" name="post_type" value="el_produit">
		<input type="hidden" name="page" value="el-pieces">
		<label>Jour <input type="date" name="jour" value="<?php echo esc_attr( $jour ); ?>"></label>
		<?php submit_button( 'Voir', 'secondary', '', false ); ?>
	</form>
</div>
	<?php
}

function el_pieces_enregistrer() {
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_die( 'Action non autorisée.' );
	}
	check_admin_referer( 'el_pieces' );
	$catalogue = el_pieces_catalogue();

	$recu   = isset( $_POST['el_pieces_stock'] ) && is_array( $_POST['el_pieces_stock'] ) ? wp_unslash( $_POST['el_pieces_stock'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- nettoyé ci-dessous.
	$stocks = array();
	foreach ( $catalogue as $piece => $nom ) {
		$v = isset( $recu[ $piece ] ) && is_scalar( $recu[ $piece ] ) ? trim( (string) $recu[ $piece ] ) : '';
		if ( '' !== $v && is_numeric( $v ) ) {
			$stocks[ $piece ] = max( 0, (int) $v );
		}
	}
	update_option( 'el_pieces', $stocks );

	$recu = isset( $_POST['el_pieces_recette'] ) && is_array( $_POST['el_pieces_recette'] ) ? wp_unslash( $_POST['el_pieces_recette'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- nettoyé ci-dessous.
	foreach ( $recu as $id => $pieces ) {
		$id = (int) $id;
		if ( ! $id || 'el_produit' !== get_post_type( $id ) || ! current_user_can( 'edit_post', $id ) || ! is_array( $pieces ) ) {
			continue;
		}
		$recette = array();
		foreach ( $catalogue as $piece => $nom ) {
			$q = isset( $pieces[ $piece ] ) && is_scalar( $pieces[ $piece ] ) ? (int) $pieces[ $piece ] : 0;
			if ( $q > 0 ) {
				$recette[ $piece ] = min( $q, 99 );
			}
		}
		if ( $recette ) {
			update_post_meta( $id, '_el_pieces', $recette );
		} else {
			delete_post_meta( $id, '_el_pieces' );
		}
	}
	wp_safe_redirect( admin_url( 'edit.php?post_type=el_produit&page=el-pieces&maj=1' ) );
	exit;
}
add_action( 'admin_post_el_pieces', 'el_pieces_enregistrer' );

/* ---------------------------------------------------------------------- mise en place, une fois */

/**
 * Sépare les produits de structure « 1,50 m ou 2 m » en deux produits, et leur donne leurs pièces.
 * Ne touche à rien si les pièces ont déjà été saisies : ce qui a été réglé à la main prime.
 */
function el_pieces_mise_en_place() {
	if ( get_option( 'el_pieces_mise_en_place' ) || ! post_type_exists( 'el_produit' ) ) {
		return;
	}
	update_option( 'el_pieces_mise_en_place', 1 );

	if ( false === get_option( 'el_pieces', false ) ) {
		update_option(
			'el_pieces',
			array(
				'truss-2m'   => 2,
				'truss-1-5m' => 2,
				'embase'     => 4,
				'treuil'     => 2,
				'monotube'   => 2,
			)
		);
	}

	$trouver = function ( $slug ) {
		$p = get_page_by_path( $slug, OBJECT, 'el_produit' );
		return $p && 'publish' === $p->post_status ? $p : null;
	};
	// Un produit existant devient la version 1,50 m ; son jumeau en 2 m est créé à partir de lui.
	$divisions = array(
		'totem-4-points'        => array(
			'nom_court' => 'Totem - 4 points',
			'autre'     => 'totem-4-points-2-m',
			'piece_a'   => 'truss-1-5m',
			'piece_b'   => 'truss-2m',
			'reste'     => array( 'embase' => 1 ),
		),
		'global-truss-4-points' => array(
			'nom_court' => 'Global Truss - 4 points',
			'autre'     => 'global-truss-4-points-2-m',
			'piece_a'   => 'truss-1-5m',
			'piece_b'   => 'truss-2m',
			'reste'     => array(),
		),
	);
	foreach ( $divisions as $slug => $c ) {
		$p = $trouver( $slug );
		if ( ! $p || el_recette( $p->ID ) || $trouver( $c['autre'] ) ) {
			continue;
		}
		$metas = get_post_meta( $p->ID );
		$neuf  = wp_insert_post(
			array(
				'post_type'   => 'el_produit',
				'post_status' => 'publish',
				'post_title'  => $c['nom_court'] . ', 2 m',
				'post_name'   => $c['autre'],
				'menu_order'  => $p->menu_order,
			)
		);
		if ( $neuf && ! is_wp_error( $neuf ) ) {
			foreach ( $metas as $cle => $valeurs ) {
				if ( in_array( $cle, array( '_el_stock', '_el_pieces', '_edit_lock', '_edit_last' ), true ) ) {
					continue;
				}
				update_post_meta( $neuf, $cle, maybe_unserialize( $valeurs[0] ) );
			}
			$termes = wp_get_object_terms( $p->ID, 'el_categorie', array( 'fields' => 'ids' ) );
			if ( ! is_wp_error( $termes ) && $termes ) {
				wp_set_object_terms( $neuf, $termes, 'el_categorie' );
			}
			$description = (string) get_post_meta( $neuf, '_el_description', true );
			update_post_meta( $neuf, '_el_description', preg_replace( '/1,50 m ou 2 m/', '2 m', $description ) );
			update_post_meta( $neuf, '_el_pieces', array_merge( array( $c['piece_b'] => 1 ), $c['reste'] ) );
		}
		wp_update_post(
			array(
				'ID'         => $p->ID,
				'post_title' => $c['nom_court'] . ', 1,50 m',
			)
		);
		$description = (string) get_post_meta( $p->ID, '_el_description', true );
		update_post_meta( $p->ID, '_el_description', preg_replace( '/1,50 m ou 2 m/', '1,50 m', $description ) );
		update_post_meta( $p->ID, '_el_pieces', array_merge( array( $c['piece_a'] => 1 ), $c['reste'] ) );
		delete_post_meta( $p->ID, '_el_stock' );
	}

	// Le pont : le pack F24200 se monte avec deux treuils et deux truss de 2 m.
	$pack = $trouver( 'pack-structure-f24200' );
	if ( $pack && ! el_recette( $pack->ID ) ) {
		update_post_meta(
			$pack->ID,
			'_el_pieces',
			array(
				'treuil'   => 2,
				'truss-2m' => 2,
			)
		);
		delete_post_meta( $pack->ID, '_el_stock' );
	}
	$mono = $trouver( 'plugger-monotube' );
	if ( $mono && ! el_recette( $mono->ID ) ) {
		update_post_meta( $mono->ID, '_el_pieces', array( 'monotube' => 1 ) );
		delete_post_meta( $mono->ID, '_el_stock' );
	}
}
add_action( 'admin_init', 'el_pieces_mise_en_place' );
