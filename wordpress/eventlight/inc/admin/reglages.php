<?php
/**
 * Le menu « Event'Light » de l'administration et la page des réglages.
 */

defined( 'ABSPATH' ) || exit;

/** L'icône du menu : le signe du logo. */
function el_icone_menu() {
	$svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path fill="black" fill-rule="evenodd" d="M1 7h6.5v6H1zm1 1v4h4.5V8z"/><path fill="black" d="M4.3 10 19 4.5v11z"/></svg>';
	return 'data:image/svg+xml;base64,' . base64_encode( $svg ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
}

function el_menu() {
	add_menu_page( 'Event\'Light', 'Event\'Light', 'manage_options', 'eventlight', 'el_page_reglages', el_icone_menu(), 26 );
	add_submenu_page( 'eventlight', 'Réglages du site', 'Réglages', 'manage_options', 'eventlight', 'el_page_reglages', 0 );
	add_submenu_page( 'eventlight', 'Importer le contenu', 'Importer le contenu', 'manage_options', 'eventlight-import', 'el_page_import' );
	add_submenu_page( 'eventlight', 'Charte graphique', 'Charte graphique', 'manage_options', 'eventlight-charte', 'el_page_charte' );
}
add_action( 'admin_menu', 'el_menu', 9 );

function el_page_reglages() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$schema = el_reglages_schema();
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$onglet = isset( $_GET['onglet'] ) ? sanitize_key( $_GET['onglet'] ) : '';
	if ( ! isset( $schema[ $onglet ] ) ) {
		$onglet = (string) key( $schema );
	}
	$reglages = el_reglages();
	?>
<div class="wrap el-reglages">
	<h1>Event'Light</h1>
	<?php // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
	<?php if ( isset( $_GET['maj'] ) ) : ?>
	<div class="notice notice-success is-dismissible"><p>Réglages enregistrés.</p></div>
	<?php endif; ?>

	<nav class="nav-tab-wrapper">
		<?php foreach ( $schema as $cle => $o ) : ?>
		<a class="nav-tab<?php echo $cle === $onglet ? ' nav-tab-active' : ''; ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=eventlight&onglet=' . $cle ) ); ?>"><?php echo esc_html( $o['titre'] ); ?></a>
		<?php endforeach; ?>
	</nav>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="el_reglages">
		<input type="hidden" name="onglet" value="<?php echo esc_attr( $onglet ); ?>">
		<?php wp_nonce_field( 'el_reglages' ); ?>
		<table class="form-table" role="presentation">
			<?php foreach ( $schema[ $onglet ]['champs'] as $cle => $champ ) : ?>
				<?php $id = 'el-' . str_replace( '_', '-', $cle ); ?>
			<tr>
				<th scope="row">
					<?php if ( in_array( $champ['type'], array( 'case', 'scenes', 'realisations' ), true ) ) : ?>
						<?php echo esc_html( $champ['label'] ); ?>
					<?php else : ?>
					<label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $champ['label'] ); ?></label>
					<?php endif; ?>
				</th>
				<td>
					<?php echo el_champ_html( 'el_reglages[' . $cle . ']', $id, $champ, $reglages[ $cle ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php if ( ! empty( $champ['aide'] ) ) : ?>
					<p class="description"><?php echo esc_html( $champ['aide'] ); ?></p>
					<?php endif; ?>
				</td>
			</tr>
			<?php endforeach; ?>
		</table>
		<?php submit_button( 'Enregistrer' ); ?>
	</form>
</div>
	<?php
}

function el_reglages_enregistrer() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Vous n\'avez pas le droit de modifier ces réglages.' );
	}
	check_admin_referer( 'el_reglages' );
	$schema = el_reglages_schema();
	$onglet = isset( $_POST['onglet'] ) ? sanitize_key( $_POST['onglet'] ) : '';
	if ( isset( $schema[ $onglet ] ) ) {
		$recu        = isset( $_POST['el_reglages'] ) && is_array( $_POST['el_reglages'] ) ? wp_unslash( $_POST['el_reglages'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- nettoyé champ par champ.
		$enregistres = get_option( 'el_reglages', array() );
		$enregistres = is_array( $enregistres ) ? $enregistres : array();
		foreach ( $schema[ $onglet ]['champs'] as $cle => $champ ) {
			$enregistres[ $cle ] = el_champ_nettoyer( $champ, isset( $recu[ $cle ] ) ? $recu[ $cle ] : null );
		}
		update_option( 'el_reglages', $enregistres );
	}
	wp_safe_redirect( admin_url( 'admin.php?page=eventlight&onglet=' . $onglet . '&maj=1' ) );
	exit;
}
add_action( 'admin_post_el_reglages', 'el_reglages_enregistrer' );

/** Après l'activation, un rappel tant que le contenu n'a pas été importé. */
function el_rappel_import() {
	$ecran = get_current_screen();
	if ( ! current_user_can( 'manage_options' ) || get_option( 'el_import_fait' ) || ( $ecran && false !== strpos( $ecran->id, 'eventlight-import' ) ) ) {
		return;
	}
	if ( wp_count_posts( 'el_produit' )->publish > 0 || ! file_exists( get_theme_file_path( 'import/contenu.json' ) ) ) {
		return;
	}
	echo '<div class="notice notice-info"><p>Le thème Event\'Light est en place, mais le site est encore vide. <a href="' . esc_url( admin_url( 'admin.php?page=eventlight-import' ) ) . '">Importer les formules, le catalogue et les réalisations</a></p></div>';
}
add_action( 'admin_notices', 'el_rappel_import' );

/** Event'Light > Charte graphique : le document interne, affiché dans un cadre pour garder sa mise en page. */
function el_page_charte() {
	$adresse = add_query_arg( 'el-charte', '1', home_url( '/' ) );
	?>
	<div class="wrap">
		<h1>Charte graphique</h1>
		<p>Document interne : le signe, les logos, les couleurs, la typographie et les règles d'usage. Il n'est pas visible sur le site.
			<a href="<?php echo esc_url( $adresse ); ?>" target="_blank" rel="noopener">Ouvrir en plein écran</a></p>
		<iframe src="<?php echo esc_url( $adresse ); ?>" title="Charte graphique d'Event'Light" style="display:block;width:100%;height:calc(100vh - 190px);min-height:520px;border:1px solid #c3c4c7;background:#fff"></iframe>
	</div>
	<?php
}
