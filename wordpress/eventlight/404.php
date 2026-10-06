<?php
/**
 * Page introuvable.
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<div class="wrap">
	<header class="page-head">
		<h1>Noir complet : cette page n'existe pas</h1>
		<p class="lede">Le lien est peut-être ancien. Voici où trouver ce que vous cherchiez.</p>
		<div class="actions">
			<a class="btn btn-plein" href="<?php echo esc_url( el_url( '/' ) ); ?>">Accueil</a>
			<a class="btn" href="<?php echo esc_url( el_url( '/nos-formules/' ) ); ?>">Les formules</a>
			<a class="btn" href="<?php echo esc_url( el_url( '/location/' ) ); ?>">Le catalogue de location</a>
			<a class="btn" href="<?php echo esc_url( el_url( '/devis/' ) ); ?>">Demander un devis</a>
		</div>
	</header>
</div>
<?php
get_footer();
