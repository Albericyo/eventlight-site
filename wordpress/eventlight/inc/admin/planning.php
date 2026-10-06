<?php
/**
 * Le planning dans l'administration : calendrier, liste des demandes et réservations,
 * fiche d'un dossier, disponibilités du matériel.
 */

defined( 'ABSPATH' ) || exit;

/* ---------------------------------------------------------------------- menu */

function el_planning_menu() {
	$attente = el_demandes_a_traiter();
	$pastille = $attente ? ' <span class="awaiting-mod count-' . (int) $attente . '"><span class="pending-count">' . (int) $attente . '</span></span>' : '';
	add_menu_page( 'Planning', 'Planning' . $pastille, 'edit_posts', 'el-planning', 'el_page_calendrier', 'dashicons-calendar-alt', 21 );
	add_submenu_page( 'el-planning', 'Calendrier', 'Calendrier', 'edit_posts', 'el-planning', 'el_page_calendrier', 0 );
}
add_action( 'admin_menu', 'el_planning_menu', 9 );

function el_planning_menu_ajout() {
	add_submenu_page( 'el-planning', 'Ajouter une réservation', 'Ajouter une réservation', 'edit_posts', 'post-new.php?post_type=el_dossier' );
}
add_action( 'admin_menu', 'el_planning_menu_ajout', 11 );

/** Noms des mois et des jours, en français quelle que soit la langue de WordPress. */
function el_mois_noms() {
	return array( 1 => 'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre' );
}
function el_jours_noms() {
	return array( 1 => 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche' );
}

/** « samedi 17 octobre 2026 ». */
function el_date_longue( $date ) {
	if ( ! el_est_date( $date ) ) {
		return '';
	}
	$ts    = strtotime( $date . ' 12:00:00 UTC' );
	$mois  = el_mois_noms();
	$jours = el_jours_noms();
	return $jours[ (int) gmdate( 'N', $ts ) ] . ' ' . ( 1 === (int) gmdate( 'j', $ts ) ? '1er' : (int) gmdate( 'j', $ts ) ) . ' ' . $mois[ (int) gmdate( 'n', $ts ) ] . ' ' . gmdate( 'Y', $ts );
}

/** Une étiquette de statut. */
function el_statut_html( $statut ) {
	$statuts = el_statuts();
	return '<span class="el-statut el-statut-' . esc_attr( $statut ) . '">' . esc_html( isset( $statuts[ $statut ] ) ? $statuts[ $statut ] : $statut ) . '</span>';
}

/* ---------------------------------------------------------------------- calendrier */

function el_page_calendrier() {
	if ( ! current_user_can( 'edit_posts' ) ) {
		return;
	}
	// phpcs:disable WordPress.Security.NonceVerification.Recommended
	$aujourdhui = wp_date( 'Y-m-d' );
	$jour       = isset( $_GET['jour'] ) && el_est_date( sanitize_text_field( wp_unslash( $_GET['jour'] ) ) ) ? sanitize_text_field( wp_unslash( $_GET['jour'] ) ) : '';
	$mois       = isset( $_GET['mois'] ) && preg_match( '/^\d{4}-(0[1-9]|1[0-2])$/', sanitize_text_field( wp_unslash( $_GET['mois'] ) ) ) ? sanitize_text_field( wp_unslash( $_GET['mois'] ) ) : substr( '' !== $jour ? $jour : $aujourdhui, 0, 7 );
	// phpcs:enable
	if ( '' === $jour ) {
		$jour = substr( $aujourdhui, 0, 7 ) === $mois ? $aujourdhui : $mois . '-01';
	}

	$ts_premier = strtotime( $mois . '-01 12:00:00 UTC' );
	$dernier    = $mois . '-' . gmdate( 't', $ts_premier );
	$debut      = gmdate( 'Y-m-d', $ts_premier - ( (int) gmdate( 'N', $ts_premier ) - 1 ) * DAY_IN_SECONDS );
	$ts_dernier = strtotime( $dernier . ' 12:00:00 UTC' );
	$fin        = gmdate( 'Y-m-d', $ts_dernier + ( 7 - (int) gmdate( 'N', $ts_dernier ) ) * DAY_IN_SECONDS );
	$precedent  = gmdate( 'Y-m', $ts_premier - 5 * DAY_IN_SECONDS );
	$suivant    = gmdate( 'Y-m', $ts_dernier + 5 * DAY_IN_SECONDS );
	$noms_mois  = el_mois_noms();
	$base       = admin_url( 'admin.php?page=el-planning' );

	// Les dossiers de la grille, rangés par jour, et les jours où le stock est dépassé.
	$dossiers  = el_dossiers_entre( $debut, $fin );
	$par_jour  = array();
	$sorties   = array();
	$fermes    = el_statuts_fermes();
	foreach ( $dossiers as $d ) {
		foreach ( el_jours( max( $d['debut'], $debut ), min( $d['fin'], $fin ) ) as $j ) {
			$par_jour[ $j ][] = $d;
			if ( in_array( $d['statut'], $fermes, true ) ) {
				foreach ( $d['lignes'] as $l ) {
					$sorties[ $j ][ $l['produit'] ] = ( isset( $sorties[ $j ][ $l['produit'] ] ) ? $sorties[ $j ][ $l['produit'] ] : 0 ) + $l['q'];
				}
			}
		}
	}
	$stocks   = el_stocks();
	$depasses = array();
	foreach ( $sorties as $j => $produits ) {
		foreach ( $produits as $pid => $q ) {
			if ( isset( $stocks[ $pid ] ) && $q > $stocks[ $pid ] ) {
				$depasses[ $j ] = true;
			}
		}
	}
	$attente = el_demandes_a_traiter();
	$genres  = el_genres();
	?>
<div class="wrap el-planning">
	<h1 class="wp-heading-inline">Planning</h1>
	<a class="page-title-action" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=el_dossier&el_debut=' . $jour ) ); ?>">Ajouter une réservation</a>
	<hr class="wp-header-end">

	<?php if ( $attente ) : ?>
	<div class="notice notice-warning inline"><p><?php echo 1 === $attente ? 'Une demande attend une réponse.' : (int) $attente . ' demandes attendent une réponse.'; ?> <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=el_dossier&el_statut=demande' ) ); ?>">Les voir</a></p></div>
	<?php endif; ?>

	<div class="el-cal-nav">
		<a class="button" href="<?php echo esc_url( $base . '&mois=' . $precedent ); ?>" aria-label="Mois précédent">&larr; <?php echo esc_html( $noms_mois[ (int) substr( $precedent, 5, 2 ) ] ); ?></a>
		<h2><?php echo esc_html( $noms_mois[ (int) substr( $mois, 5, 2 ) ] . ' ' . substr( $mois, 0, 4 ) ); ?></h2>
		<a class="button" href="<?php echo esc_url( $base . '&mois=' . $suivant ); ?>" aria-label="Mois suivant"><?php echo esc_html( $noms_mois[ (int) substr( $suivant, 5, 2 ) ] ); ?> &rarr;</a>
		<?php if ( substr( $aujourdhui, 0, 7 ) !== $mois ) : ?>
		<a class="button" href="<?php echo esc_url( $base ); ?>">Aujourd'hui</a>
		<?php endif; ?>
	</div>

	<table class="el-cal">
		<thead>
			<tr>
				<?php foreach ( el_jours_noms() as $nom ) : ?>
				<th scope="col"><?php echo esc_html( $nom ); ?></th>
				<?php endforeach; ?>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( array_chunk( el_jours( $debut, $fin ), 7 ) as $semaine ) : ?>
			<tr>
				<?php foreach ( $semaine as $j ) : ?>
					<?php
					$classes = array();
					if ( substr( $j, 0, 7 ) !== $mois ) {
						$classes[] = 'hors-mois';
					}
					if ( $j === $aujourdhui ) {
						$classes[] = 'aujourdhui';
					}
					if ( $j === $jour ) {
						$classes[] = 'choisi';
					}
					?>
				<td class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
					<a class="el-cal-jour" href="<?php echo esc_url( $base . '&mois=' . substr( $j, 0, 7 ) . '&jour=' . $j . '#jour' ); ?>" aria-label="<?php echo esc_attr( el_date_longue( $j ) ); ?>"><?php echo (int) substr( $j, 8, 2 ); ?></a>
					<?php if ( isset( $depasses[ $j ] ) ) : ?>
					<span class="el-cal-alerte" title="Stock dépassé ce jour-là">Stock dépassé</span>
					<?php endif; ?>
					<?php foreach ( isset( $par_jour[ $j ] ) ? $par_jour[ $j ] : array() as $d ) : ?>
					<a class="el-chip el-chip-<?php echo esc_attr( $d['statut'] ); ?> el-chip-<?php echo esc_attr( $d['genre'] ); ?><?php echo $d['debut'] < $j ? ' suite' : ''; ?>" href="<?php echo esc_url( get_edit_post_link( $d['id'] ) ); ?>" title="<?php echo esc_attr( $genres[ $d['genre'] ] . ', ' . el_statuts()[ $d['statut'] ] . ', ' . el_periode_fr( $d['debut'], $d['fin'] ) ); ?>"><?php echo esc_html( $d['titre'] ); ?></a>
					<?php endforeach; ?>
				</td>
				<?php endforeach; ?>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>

	<p class="el-legende">
		<span class="el-chip el-chip-confirmee el-chip-prestation">Événement confirmé</span>
		<span class="el-chip el-chip-confirmee el-chip-location">Location confirmée</span>
		<span class="el-chip el-chip-devis el-chip-prestation">En attente : demande reçue ou devis envoyé</span>
		<span class="el-chip el-chip-terminee el-chip-prestation">Terminé</span>
	</p>

	<h2 id="jour">Le <?php echo esc_html( el_date_longue( $jour ) ); ?></h2>
	<?php el_planning_jour( $jour, isset( $par_jour[ $jour ] ) ? $par_jour[ $jour ] : array() ); ?>

	<h2>Suivre le planning depuis un téléphone</h2>
	<p>Cette adresse s'ajoute à Google Agenda (« Autres agendas », puis « À partir de l'URL »), au calendrier de l'iPhone ou à Outlook. Les réservations s'y mettent à jour toutes seules, avec le client et le matériel. Gardez-la pour vous : quiconque la connaît peut lire le planning.</p>
	<p><input type="text" class="large-text code" readonly value="<?php echo esc_attr( el_agenda_adresse() ); ?>" onclick="this.select()" aria-label="Adresse de l'agenda"></p>
	<?php if ( current_user_can( 'manage_options' ) ) : ?>
	<p><a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=el_agenda_renouveler' ), 'el_agenda_renouveler' ) ); ?>">Changer l'adresse</a> <span class="description">À faire si elle a été partagée par erreur. L'ancienne cesse de fonctionner.</span></p>
	<?php endif; ?>
</div>
	<?php
}

/** Le détail d'une journée : ce qui sort, ce qui reste. */
function el_planning_jour( $jour, $dossiers ) {
	$genres = el_genres();
	if ( $dossiers ) {
		echo '<table class="widefat striped el-jour-dossiers"><thead><tr><th>Dossier</th><th>Statut</th><th>Dates</th><th>Client</th><th>Matériel</th></tr></thead><tbody>';
		foreach ( $dossiers as $d ) {
			$lignes = array();
			foreach ( $d['lignes'] as $l ) {
				$lignes[] = (int) $l['q'] . ' x ' . esc_html( get_the_title( $l['produit'] ) );
			}
			echo '<tr><td><a href="' . esc_url( get_edit_post_link( $d['id'] ) ) . '"><strong>' . esc_html( $d['titre'] ) . '</strong></a><br>' . esc_html( $genres[ $d['genre'] ] ) . ( '' !== $d['lieu'] ? ', ' . esc_html( $d['lieu'] ) : '' ) . '</td>';
			echo '<td>' . el_statut_html( $d['statut'] ) . '</td>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo '<td>' . esc_html( el_periode_fr( $d['debut'], $d['fin'] ) ) . '</td>';
			echo '<td>' . esc_html( $d['client'] ) . ( '' !== $d['tel'] ? '<br><a href="tel:' . esc_attr( el_tel_iso( $d['tel'] ) ) . '">' . esc_html( $d['tel'] ) . '</a>' : '' ) . '</td>';
			echo '<td>' . ( $lignes ? implode( '<br>', $lignes ) : '<span class="el-discret">aucune ligne</span>' ) . '</td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		echo '</tbody></table>';
	} else {
		echo '<p>Rien de prévu ce jour-là.</p>';
	}

	$dispos = el_disponibilites( $jour, $jour );
	if ( ! $dispos ) {
		echo '<p class="description">Aucun produit n\'a encore de quantité en stock. Renseignez-la dans <a href="' . esc_url( admin_url( 'edit.php?post_type=el_produit&page=el-stock' ) ) . '">Location, Stock</a> pour voir ici ce qui reste disponible.</p>';
		return;
	}
	echo '<h3>Matériel disponible ce jour-là</h3>';
	echo '<table class="widefat striped el-dispos"><thead><tr><th>Produit</th><th class="num">Stock</th><th class="num">Sortis</th><th class="num">Disponibles</th><th class="num">Demandés, en attente</th></tr></thead><tbody>';
	foreach ( el_produits_par_categorie() as $produits ) {
		foreach ( $produits as $p ) {
			if ( ! isset( $dispos[ $p['id'] ] ) ) {
				continue;
			}
			$x      = $dispos[ $p['id'] ];
			$classe = $x['dispo'] < 0 ? 'el-manque' : ( $x['attente'] > $x['dispo'] ? 'el-tendu' : '' );
			echo '<tr class="' . esc_attr( $classe ) . '"><td><a href="' . esc_url( get_edit_post_link( $p['id'] ) ) . '">' . esc_html( $p['nom'] ) . '</a> <span class="el-discret">' . esc_html( $p['categorie'] ) . '</span></td>';
			echo '<td class="num">' . (int) $x['stock'] . '</td><td class="num">' . (int) $x['ferme'] . '</td>';
			echo '<td class="num"><strong>' . (int) $x['dispo'] . '</strong>' . ( $x['dispo'] < 0 ? ' <span class="el-alerte">il en manque ' . (int) abs( $x['dispo'] ) . '</span>' : '' ) . '</td>';
			echo '<td class="num">' . ( $x['attente'] ? (int) $x['attente'] : '' ) . '</td></tr>';
		}
	}
	echo '</tbody></table>';
}

function el_agenda_renouveler() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Action non autorisée.' );
	}
	check_admin_referer( 'el_agenda_renouveler' );
	delete_option( 'el_agenda_jeton' );
	wp_safe_redirect( admin_url( 'admin.php?page=el-planning' ) );
	exit;
}
add_action( 'admin_post_el_agenda_renouveler', 'el_agenda_renouveler' );

/* ---------------------------------------------------------------------- stock de tout le catalogue */

function el_stock_menu() {
	add_submenu_page( 'edit.php?post_type=el_produit', 'Stock', 'Stock', 'edit_posts', 'el-stock', 'el_page_stock' );
}
add_action( 'admin_menu', 'el_stock_menu' );

/** Toutes les quantités en stock sur un seul écran, pour les saisir d'un coup. */
function el_page_stock() {
	if ( ! current_user_can( 'edit_posts' ) ) {
		return;
	}
	$groupes = el_produits_par_categorie();
	?>
<div class="wrap el-stock">
	<h1>Stock</h1>
	<?php // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
	<?php if ( isset( $_GET['maj'] ) ) : ?>
	<div class="notice notice-success is-dismissible"><p>Stock enregistré.</p></div>
	<?php endif; ?>
	<p class="el-import-intro">La quantité de chaque produit que vous pouvez sortir le même jour. Comptez dans l'unité du tarif : pour un produit loué à la paire, le nombre de paires. Un produit laissé vide n'est pas suivi dans le planning.</p>
	<?php if ( ! $groupes ) : ?>
	<p>Aucun produit pour le moment.</p>
	<?php else : ?>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="el_stock">
		<?php wp_nonce_field( 'el_stock' ); ?>
		<table class="widefat striped el-stock-table">
			<thead><tr><th>Produit</th><th>Tarif</th><th>En stock</th></tr></thead>
			<tbody>
				<?php foreach ( $groupes as $nom => $produits ) : ?>
				<tr class="el-stock-groupe"><th colspan="3" scope="colgroup"><?php echo esc_html( $nom ); ?></th></tr>
					<?php foreach ( $produits as $p ) : ?>
				<tr>
					<td><label for="el-stock-<?php echo (int) $p['id']; ?>"><?php echo esc_html( $p['nom'] ); ?></label></td>
					<td><?php echo esc_html( $p['prix'] ); ?></td>
					<td><input type="number" class="small-text" min="0" id="el-stock-<?php echo (int) $p['id']; ?>" name="el_stock[<?php echo (int) $p['id']; ?>]" value="<?php echo esc_attr( (string) get_post_meta( $p['id'], '_el_stock', true ) ); ?>"></td>
				</tr>
					<?php endforeach; ?>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php submit_button( 'Enregistrer le stock' ); ?>
	</form>
	<?php endif; ?>
</div>
	<?php
}

function el_stock_enregistrer() {
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_die( 'Action non autorisée.' );
	}
	check_admin_referer( 'el_stock' );
	$recu = isset( $_POST['el_stock'] ) && is_array( $_POST['el_stock'] ) ? wp_unslash( $_POST['el_stock'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- nettoyé ci-dessous.
	foreach ( $recu as $id => $valeur ) {
		$id = absint( $id );
		if ( ! $id || 'el_produit' !== get_post_type( $id ) || ! current_user_can( 'edit_post', $id ) ) {
			continue;
		}
		$valeur = is_scalar( $valeur ) ? trim( (string) $valeur ) : '';
		update_post_meta( $id, '_el_stock', ( '' !== $valeur && is_numeric( $valeur ) ) ? (string) max( 0, (int) $valeur ) : '' );
	}
	wp_safe_redirect( admin_url( 'edit.php?post_type=el_produit&page=el-stock&maj=1' ) );
	exit;
}
add_action( 'admin_post_el_stock', 'el_stock_enregistrer' );

/* ---------------------------------------------------------------------- fiche d'un dossier */

function el_dossier_boites() {
	remove_meta_box( 'submitdiv', 'el_dossier', 'side' );
	add_meta_box( 'el-dossier-enregistrer', 'Enregistrer', 'el_dossier_boite_enregistrer', 'el_dossier', 'side', 'high' );
	add_meta_box( 'el-dossier', 'Le dossier', 'el_dossier_boite', 'el_dossier', 'normal', 'high' );
	add_meta_box( 'el-dossier-materiel', 'Matériel', 'el_dossier_boite_materiel', 'el_dossier', 'normal', 'high' );
	add_meta_box( 'el-dossier-notes', 'Notes', 'el_dossier_boite_notes', 'el_dossier', 'normal', 'default' );
	add_meta_box( 'el-dossier-client', 'Client', 'el_dossier_boite_client', 'el_dossier', 'side', 'default' );
}
add_action( 'add_meta_boxes_el_dossier', 'el_dossier_boites' );

/** Le dossier affiché dans la fiche, avec la date proposée par le calendrier pour un nouveau dossier. */
function el_dossier_de_la_fiche( $post ) {
	$d = el_dossier( $post );
	if ( 'auto-draft' === $post->post_status ) {
		$d['statut'] = 'confirmee';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$propose = isset( $_GET['el_debut'] ) ? sanitize_text_field( wp_unslash( $_GET['el_debut'] ) ) : '';
		if ( el_est_date( $propose ) ) {
			$d['debut'] = $propose;
			$d['fin']   = $propose;
		}
	}
	return $d;
}

function el_dossier_boite_enregistrer( $post ) {
	$nouveau = 'auto-draft' === $post->post_status;
	wp_nonce_field( 'el_dossier', 'el_dossier_jeton' );
	echo '<div class="el-enregistrer">';
	if ( ! $nouveau ) {
		echo '<p>' . ( 'site' === el_meta( $post->ID, 'origine' ) ? 'Demande reçue par le site le ' : 'Dossier créé le ' ) . esc_html( wp_date( 'd/m/Y \à H\hi', strtotime( $post->post_date_gmt . ' UTC' ) ) ) . '.</p>';
		if ( 'echec' === el_meta( $post->ID, 'mail' ) ) {
			echo '<p class="el-alerte">L\'e-mail de notification n\'est pas parti. Vérifiez l\'envoi des e-mails de votre hébergement.</p>';
		}
	}
	echo '<div id="publishing-action"><span class="spinner"></span>';
	echo '<input type="hidden" name="original_publish" id="original_publish" value="Enregistrer">';
	submit_button( 'Enregistrer', 'primary large', 'publish', false, array( 'id' => 'publish' ) );
	echo '</div>';
	if ( ! $nouveau && current_user_can( 'delete_post', $post->ID ) ) {
		echo '<p><a class="submitdelete deletion" href="' . esc_url( get_delete_post_link( $post->ID ) ) . '">Mettre à la corbeille</a></p>';
	}
	echo '</div>';
}

function el_dossier_boite( $post ) {
	$d      = el_dossier_de_la_fiche( $post );
	$champs = array(
		'statut'         => array(
			'type'  => 'choix',
			'label' => 'Statut',
			'choix' => el_statuts(),
			'aide'  => 'Le matériel sort du stock quand le dossier est confirmé.',
		),
		'genre'          => array(
			'type'  => 'choix',
			'label' => 'Nature',
			'choix' => el_genres(),
		),
		'type_evenement' => array(
			'type'    => 'texte',
			'label'   => 'Type d\'événement',
			'court'   => true,
			'exemple' => 'Mariage',
		),
		'debut'          => array(
			'type'  => 'date',
			'label' => 'Du',
			'aide'  => 'Premier jour où le matériel est sorti.',
		),
		'fin'            => array(
			'type'  => 'date',
			'label' => 'Au',
			'aide'  => 'Dernier jour, retour compris. Vide : une seule journée.',
		),
		'lieu'           => array(
			'type'  => 'texte',
			'label' => 'Lieu',
			'court' => true,
		),
		'invites'        => array(
			'type'  => 'texte',
			'label' => 'Nombre d\'invités',
			'court' => true,
		),
		'montant'        => array(
			'type'    => 'texte',
			'label'   => 'Montant du devis',
			'court'   => true,
			'exemple' => '799 €',
		),
		'acompte'        => array(
			'type'    => 'texte',
			'label'   => 'Acompte reçu',
			'court'   => true,
			'exemple' => '240 € le 12/03',
		),
	);
	echo '<div class="el-grille">';
	el_champs_afficher(
		$champs,
		function ( $cle ) use ( $d ) {
			return $d[ $cle ];
		},
		'el_dossier'
	);
	echo '</div>';

	if ( '' !== $d['message'] || '' !== $d['materiel_texte'] ) {
		echo '<div class="el-recu"><h3>Ce que le client a écrit</h3>';
		echo '<p>' . nl2br( esc_html( $d['message'] ) ) . '</p>';
		if ( '' !== $d['materiel_texte'] ) {
			echo '<h3>Sa sélection dans le catalogue</h3><p>' . nl2br( esc_html( $d['materiel_texte'] ) ) . '</p>';
		}
		echo '</div>';
	}
}

function el_dossier_boite_client( $post ) {
	$d      = el_dossier( $post );
	$champs = array(
		'client' => array(
			'type'  => 'texte',
			'label' => 'Nom',
		),
		'tel'    => array(
			'type'  => 'tel',
			'label' => 'Téléphone',
		),
		'email'  => array(
			'type'  => 'email',
			'label' => 'E-mail',
		),
	);
	el_champs_afficher(
		$champs,
		function ( $cle ) use ( $d ) {
			return $d[ $cle ];
		},
		'el_dossier'
	);
	$liens = array();
	if ( '' !== $d['tel'] ) {
		$liens[] = '<a class="button" href="tel:' . esc_attr( el_tel_iso( $d['tel'] ) ) . '">Appeler</a>';
	}
	if ( is_email( $d['email'] ) ) {
		$liens[] = '<a class="button" href="mailto:' . esc_attr( $d['email'] ) . '?subject=' . rawurlencode( 'Votre demande de devis, Event\'Light' ) . '">Écrire</a>';
	}
	if ( $liens ) {
		echo '<p class="el-client-liens">' . implode( ' ', $liens ) . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}

function el_dossier_boite_materiel( $post ) {
	$d       = el_dossier_de_la_fiche( $post );
	$manques = el_depassements( $d );
	if ( $manques ) {
		$ferme = in_array( $d['statut'], el_statuts_fermes(), true );
		echo '<div class="notice notice-' . ( $ferme ? 'error' : 'warning' ) . ' inline"><p><strong>' . ( $ferme ? 'Stock dépassé ' : 'Si vous confirmez ce dossier, il manquera du matériel ' ) . esc_html( el_periode_fr( $d['debut'], $d['fin'] ) ) . '.</strong><br>';
		foreach ( $manques as $m ) {
			echo esc_html( $m['nom'] . ' : ' . $m['demande'] . ( $m['demande'] > 1 ? ' demandés, ' : ' demandé, ' ) . $m['dispo'] . ( $m['dispo'] > 1 ? ' disponibles' : ' disponible' ) ) . '<br>';
		}
		echo '</p></div>';
	}
	echo '<p class="description el-dispo-periode" data-sauf="' . (int) $post->ID . '">' . ( '' !== $d['debut'] ? 'Disponibilités ' . esc_html( el_periode_fr( $d['debut'], $d['fin'] ) ) . '.' : 'Indiquez les dates du dossier pour voir ce qui est disponible.' ) . '</p>';
	echo el_champ_materiel( 'el_dossier[lignes]', $d['lignes'], true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

	// Le matériel renseigné sur les packs des formules, à reporter d'un clic.
	$packs = array();
	foreach ( el_formules() as $f ) {
		foreach ( $f['packs'] as $p ) {
			if ( $p['materiel'] ) {
				$packs[] = array(
					'nom'    => $f['titre'] . ', ' . $p['nom'],
					'lignes' => $p['materiel'],
				);
			}
		}
	}
	if ( $packs ) {
		echo '<p class="el-pack-report"><label for="el-pack-choix">Reprendre le matériel d\'un pack</label> <select id="el-pack-choix">';
		foreach ( $packs as $i => $p ) {
			echo '<option value="' . (int) $i . '">' . esc_html( $p['nom'] ) . '</option>';
		}
		echo '</select> <button type="button" class="button el-pack-ajouter">Ajouter ce matériel</button></p>';
		echo '<script type="application/json" id="el-packs-materiel">' . wp_json_encode( $packs, JSON_HEX_TAG | JSON_HEX_AMP ) . '</script>';
	} else {
		echo '<p class="description">Pour reporter d\'un clic le matériel d\'un pack, renseignez le « matériel sorti » de chaque pack sur la fiche des formules.</p>';
	}
}

function el_dossier_boite_notes( $post ) {
	echo '<label class="screen-reader-text" for="el-notes">Notes</label>';
	echo '<textarea class="large-text" rows="5" id="el-notes" name="el_dossier[notes]" placeholder="Horaires, accès, personne sur place, ce qui a été convenu au téléphone…">' . esc_textarea( el_meta( $post->ID, 'notes' ) ) . '</textarea>';
}

function el_dossier_enregistrer_fiche( $post_id, $post ) {
	if ( ! isset( $_POST['el_dossier_jeton'] ) || ! wp_verify_nonce( sanitize_key( $_POST['el_dossier_jeton'] ), 'el_dossier' ) ) {
		return;
	}
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	$recu    = isset( $_POST['el_dossier'] ) && is_array( $_POST['el_dossier'] ) ? wp_unslash( $_POST['el_dossier'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- nettoyé ci-dessous.
	$texte   = function ( $cle ) use ( $recu ) {
		return isset( $recu[ $cle ] ) && is_string( $recu[ $cle ] ) ? sanitize_text_field( $recu[ $cle ] ) : '';
	};
	$statuts = el_statuts();
	$genres  = el_genres();
	$donnees = array(
		'id'             => $post_id,
		'statut'         => isset( $statuts[ $texte( 'statut' ) ] ) ? $texte( 'statut' ) : 'demande',
		'genre'          => isset( $genres[ $texte( 'genre' ) ] ) ? $texte( 'genre' ) : 'prestation',
		'debut'          => $texte( 'debut' ),
		'fin'            => $texte( 'fin' ),
		'client'         => $texte( 'client' ),
		'email'          => isset( $recu['email'] ) && is_string( $recu['email'] ) ? sanitize_email( $recu['email'] ) : '',
		'tel'            => $texte( 'tel' ),
		'lieu'           => $texte( 'lieu' ),
		'invites'        => $texte( 'invites' ),
		'type_evenement' => $texte( 'type_evenement' ),
		'montant'        => $texte( 'montant' ),
		'acompte'        => $texte( 'acompte' ),
		'notes'          => isset( $recu['notes'] ) && is_string( $recu['notes'] ) ? sanitize_textarea_field( $recu['notes'] ) : '',
		'lignes'         => isset( $recu['lignes'] ) ? $recu['lignes'] : array(),
	);
	if ( '' === el_meta( $post_id, 'origine' ) ) {
		$donnees['origine'] = 'manuel';
	}
	el_dossier_enregistrer( $donnees );

	// Un dossier sans nom prend celui du client.
	if ( '' === trim( $post->post_title ) ) {
		$titre = '' !== $donnees['client'] ? $donnees['client'] : 'Réservation' . ( el_est_date( $donnees['debut'] ) ? ' du ' . el_date_fr( $donnees['debut'] ) : '' );
		remove_action( 'save_post_el_dossier', 'el_dossier_enregistrer_fiche', 10 );
		wp_update_post(
			array(
				'ID'         => $post_id,
				'post_title' => wp_slash( $titre ),
			)
		);
		add_action( 'save_post_el_dossier', 'el_dossier_enregistrer_fiche', 10, 2 );
	}
}
add_action( 'save_post_el_dossier', 'el_dossier_enregistrer_fiche', 10, 2 );

/** Les messages affichés après l'enregistrement d'un contenu du thème. */
function el_messages( $messages ) {
	$voir = function ( $post ) {
		return is_post_type_viewable( $post->post_type ) ? ' <a href="' . esc_url( get_permalink( $post ) ) . '">Voir sur le site</a>' : '';
	};
	$post = get_post();
	if ( ! $post ) {
		return $messages;
	}
	foreach ( array( 'el_formule', 'el_produit', 'el_realisation', 'el_question', 'el_dossier' ) as $type ) {
		$messages[ $type ] = array(
			0  => '',
			1  => 'Modifications enregistrées.' . $voir( $post ),
			2  => 'Champ mis à jour.',
			3  => 'Champ supprimé.',
			4  => 'Modifications enregistrées.',
			5  => false,
			6  => 'el_dossier' === $type ? 'Dossier enregistré.' : 'C\'est en ligne.' . $voir( $post ),
			7  => 'Enregistré.',
			8  => 'Enregistré.',
			9  => 'Publication programmée.',
			10 => 'Brouillon enregistré.',
		);
	}
	return $messages;
}
add_filter( 'post_updated_messages', 'el_messages' );

/* ---------------------------------------------------------------------- disponibilités en direct */

function el_ajax_dispo() {
	check_ajax_referer( 'el_admin', 'jeton' );
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_send_json_error();
	}
	$debut = isset( $_POST['debut'] ) ? sanitize_text_field( wp_unslash( $_POST['debut'] ) ) : '';
	$fin   = isset( $_POST['fin'] ) ? sanitize_text_field( wp_unslash( $_POST['fin'] ) ) : '';
	$sauf  = isset( $_POST['sauf'] ) ? absint( $_POST['sauf'] ) : 0;
	if ( ! el_est_date( $debut ) ) {
		wp_send_json_success( array( 'periode' => '' ) );
	}
	if ( ! el_est_date( $fin ) || $fin < $debut ) {
		$fin = $debut;
	}
	wp_send_json_success(
		array(
			'periode' => el_periode_fr( $debut, $fin ),
			'dispos'  => el_disponibilites( $debut, $fin, $sauf ),
		)
	);
}
add_action( 'wp_ajax_el_dispo', 'el_ajax_dispo' );

/* ---------------------------------------------------------------------- liste des dossiers */

function el_dossier_colonnes( $colonnes ) {
	return array(
		'cb'          => $colonnes['cb'],
		'title'       => 'Dossier',
		'el_statut'   => 'Statut',
		'el_dates'    => 'Dates',
		'el_client'   => 'Client',
		'el_materiel' => 'Matériel',
		'el_montant'  => 'Montant',
		'date'        => 'Reçu le',
	);
}
add_filter( 'manage_el_dossier_posts_columns', 'el_dossier_colonnes' );

function el_dossier_colonne( $colonne, $post_id ) {
	$d = el_dossier( $post_id );
	if ( ! $d ) {
		return;
	}
	switch ( $colonne ) {
		case 'el_statut':
			echo el_statut_html( $d['statut'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			if ( in_array( $d['statut'], el_statuts_fermes(), true ) && el_depassements( $d ) ) {
				echo '<br><span class="el-alerte">Stock dépassé</span>';
			}
			break;
		case 'el_dates':
			$genres = el_genres();
			echo esc_html( '' !== $d['debut'] ? el_periode_fr( $d['debut'], $d['fin'] ) : 'Sans date' ) . '<br><span class="el-discret">' . esc_html( $genres[ $d['genre'] ] . ( '' !== $d['lieu'] ? ', ' . $d['lieu'] : '' ) ) . '</span>';
			break;
		case 'el_client':
			echo esc_html( $d['client'] );
			if ( '' !== $d['tel'] ) {
				echo '<br><a href="tel:' . esc_attr( el_tel_iso( $d['tel'] ) ) . '">' . esc_html( $d['tel'] ) . '</a>';
			}
			if ( '' !== $d['email'] ) {
				echo '<br><a href="mailto:' . esc_attr( $d['email'] ) . '">' . esc_html( $d['email'] ) . '</a>';
			}
			break;
		case 'el_materiel':
			foreach ( $d['lignes'] as $l ) {
				echo (int) $l['q'] . ' x ' . esc_html( get_the_title( $l['produit'] ) ) . '<br>';
			}
			break;
		case 'el_montant':
			echo esc_html( $d['montant'] );
			break;
	}
}
add_action( 'manage_el_dossier_posts_custom_column', 'el_dossier_colonne', 10, 2 );

function el_dossier_colonnes_triables( $colonnes ) {
	$colonnes['el_dates'] = 'el_debut';
	return $colonnes;
}
add_filter( 'manage_edit-el_dossier_sortable_columns', 'el_dossier_colonnes_triables' );

/** Les filtres par statut, au-dessus de la liste. */
function el_dossier_vues( $vues ) {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$actuel = isset( $_GET['el_statut'] ) ? sanitize_key( $_GET['el_statut'] ) : '';
	$sortie = array();
	if ( isset( $vues['all'] ) ) {
		$sortie['all'] = '' !== $actuel ? str_replace( 'class="current"', '', $vues['all'] ) : $vues['all'];
	}
	foreach ( el_statuts() as $statut => $nom ) {
		$nombre = count(
			get_posts(
				array(
					'post_type'   => 'el_dossier',
					'post_status' => 'publish',
					'numberposts' => -1,
					'fields'      => 'ids',
					'meta_key'    => '_el_statut', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
					'meta_value'  => $statut, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				)
			)
		);
		if ( $nombre ) {
			$sortie[ 'el-' . $statut ] = '<a href="' . esc_url( admin_url( 'edit.php?post_type=el_dossier&el_statut=' . $statut ) ) . '"' . ( $actuel === $statut ? ' class="current" aria-current="page"' : '' ) . '>' . esc_html( $nom ) . ' <span class="count">(' . (int) $nombre . ')</span></a>';
		}
	}
	if ( isset( $vues['trash'] ) ) {
		$sortie['trash'] = $vues['trash'];
	}
	return $sortie;
}
add_filter( 'views_edit-el_dossier', 'el_dossier_vues' );

function el_dossier_requete_liste( $query ) {
	if ( ! is_admin() || ! $query->is_main_query() || 'el_dossier' !== $query->get( 'post_type' ) ) {
		return;
	}
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$statut = isset( $_GET['el_statut'] ) ? sanitize_key( $_GET['el_statut'] ) : '';
	if ( isset( el_statuts()[ $statut ] ) ) {
		$query->set(
			'meta_query',
			array(
				array(
					'key'   => '_el_statut',
					'value' => $statut,
				),
			)
		);
	}
	if ( 'el_debut' === $query->get( 'orderby' ) ) {
		$query->set( 'meta_key', '_el_debut' );
		$query->set( 'orderby', 'meta_value' );
	}
}
add_action( 'pre_get_posts', 'el_dossier_requete_liste' );

/** Changer le statut d'un dossier depuis la liste, sans ouvrir la fiche. */
function el_dossier_actions_rapides( $actions, $post ) {
	if ( 'el_dossier' !== $post->post_type || 'trash' === $post->post_status ) {
		return $actions;
	}
	unset( $actions['inline hide-if-no-js'], $actions['view'] );
	$statut   = el_meta( $post->ID, 'statut' );
	$suivants = array(
		'demande'   => array( 'devis' => 'Devis envoyé', 'confirmee' => 'Confirmer', 'annulee' => 'Sans suite' ),
		'devis'     => array( 'confirmee' => 'Confirmer', 'annulee' => 'Sans suite' ),
		'confirmee' => array( 'terminee' => 'Terminer', 'annulee' => 'Annuler' ),
	);
	$rapides  = array();
	foreach ( isset( $suivants[ $statut ] ) ? $suivants[ $statut ] : array() as $cible => $libelle ) {
		$rapides[ 'el-' . $cible ] = '<a href="' . esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=el_dossier_statut&dossier=' . $post->ID . '&statut=' . $cible ), 'el_dossier_statut_' . $post->ID ) ) . '">' . esc_html( $libelle ) . '</a>';
	}
	return array_merge( $rapides, $actions );
}
add_filter( 'post_row_actions', 'el_dossier_actions_rapides', 10, 2 );

function el_dossier_changer_statut() {
	$id     = isset( $_GET['dossier'] ) ? absint( $_GET['dossier'] ) : 0;
	$statut = isset( $_GET['statut'] ) ? sanitize_key( $_GET['statut'] ) : '';
	check_admin_referer( 'el_dossier_statut_' . $id );
	if ( ! $id || ! current_user_can( 'edit_post', $id ) || ! isset( el_statuts()[ $statut ] ) || 'el_dossier' !== get_post_type( $id ) ) {
		wp_die( 'Action non autorisée.' );
	}
	update_post_meta( $id, '_el_statut', $statut );
	wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url( 'edit.php?post_type=el_dossier' ) );
	exit;
}
add_action( 'admin_post_el_dossier_statut', 'el_dossier_changer_statut' );
