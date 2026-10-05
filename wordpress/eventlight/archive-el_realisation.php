<?php
/**
 * Toutes les réalisations, avec un filtre par type.
 */

defined( 'ABSPATH' ) || exit;

$el_portfolio = el_portfolio();

get_header();
?>
<div class="wrap">
	<header class="page-head">
		<?php el_fil(); ?>
		<h1><?php echo esc_html( el_reglage( 'realisations_titre' ) ); ?></h1>
		<p class="lede"><?php echo esc_html( el_reglage( 'realisations_texte' ) ); ?></p>
	</header>

	<?php if ( count( el_types_de_realisation() ) > 1 ) : ?>
	<div class="filters" role="group" aria-label="Filtrer les réalisations">
		<button class="filter" type="button" data-filtre="tout" aria-pressed="true">Tout (<?php echo count( $el_portfolio ); ?>)</button>
		<?php foreach ( el_types_de_realisation() as $el_type => $el_nombre ) : ?>
		<button class="filter" type="button" data-filtre="<?php echo esc_attr( $el_type ); ?>" aria-pressed="false"><?php echo esc_html( $el_type ); ?> (<?php echo (int) $el_nombre; ?>)</button>
		<?php endforeach; ?>
	</div>
	<?php endif; ?>

	<div class="works">
		<?php array_map( 'el_work', $el_portfolio ); ?>
	</div>
</div>

<?php
el_band( 'Un projet du même genre', 'Une façade à habiller, une soirée à sonoriser : décrivez-nous l\'idée, on vous dit ce qu\'il faut et combien ça coûte.', el_url( '/devis/' ), 'Demander un devis' );

get_footer();
