<?php
/**
 * Les formules : une rangée par formule, puis ce que couvre un pack.
 */

defined( 'ABSPATH' ) || exit;

$el_site = el_site();

get_header();
?>
<div class="wrap">
	<header class="page-head">
		<?php el_fil(); ?>
		<h1><?php echo esc_html( el_reglage( 'formules_titre' ) ); ?></h1>
		<p class="lede"><?php echo esc_html( el_reglage( 'formules_texte' ) ); ?></p>
	</header>

	<ul class="rows">
		<?php array_map( 'el_row', el_formules() ); ?>
	</ul>
</div>

<section class="section">
	<div class="wrap split">
		<div class="split-head">
			<h2>Ce que couvre un pack</h2>
			<p><?php echo esc_html( el_reglage( 'note_prix' ) ); ?></p>
			<p class="note"><?php echo esc_html( $el_site['tva'] ); ?>.</p>
		</div>
		<div class="split-body cols-2">
			<div class="stack">
				<h3>Dans tous les packs</h3>
				<ul class="checks">
					<?php foreach ( el_lignes( el_reglage( 'inclus' ) ) as $el_ligne ) : ?>
					<li class="plus"><?php echo esc_html( $el_ligne ); ?></li>
					<?php endforeach; ?>
				</ul>
			</div>
			<div class="stack">
				<h3>En option, au devis</h3>
				<ul class="checks">
					<?php foreach ( el_lignes( el_reglage( 'options' ) ) as $el_ligne ) : ?>
					<li><?php echo esc_html( $el_ligne ); ?></li>
					<?php endforeach; ?>
				</ul>
			</div>
		</div>
	</div>
</section>

<?php
el_band( 'Votre événement ne rentre dans aucune case', esc_html( el_reglage( 'personnalisation' ) ), el_url( '/devis/' ), 'Demander un devis' );

get_footer();
