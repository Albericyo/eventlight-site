<?php
/**
 * Template Name: Vidéo mapping
 *
 * La page du vidéo mapping : une réalisation en tête, la méthode, les projections déjà faites.
 * Les textes se règlent dans Event'Light > Réglages > Vidéo mapping.
 */

defined( 'ABSPATH' ) || exit;

// La réalisation en tête de page : celle des réglages, sinon le premier mapping qui a une vidéo.
$el_vedette = el_reglage( 'mapping_vedette' ) ? el_realisation_par_id( el_reglage( 'mapping_vedette' ) ) : null;
if ( ! $el_vedette ) {
	foreach ( el_portfolio() as $el_p ) {
		if ( ! $el_vedette && 'Mapping' === $el_p['type'] && '' !== $el_p['youtube'] ) {
			$el_vedette = $el_p;
		}
	}
}
$el_photos = $el_vedette ? el_galerie( $el_vedette['id'] ) : array();

// Les réalisations du même type que celle mise en tête.
$el_type     = $el_vedette ? $el_vedette['type'] : 'Mapping';
$el_mappings = array();
foreach ( el_portfolio() as $el_p ) {
	if ( $el_p['type'] === $el_type ) {
		$el_mappings[] = $el_p;
	}
}
$el_etapes = el_paires( el_reglage( 'mapping_etapes' ) );
$el_fiche  = el_paires( el_reglage( 'mapping_fiche' ) );

get_header();
?>
<div class="wrap">
	<header class="page-head">
		<?php el_fil(); ?>
		<h1><?php the_title(); ?></h1>
		<?php el_lede_page( 'Une projection dessinée pour un bâtiment précis : chaque fenêtre, chaque corniche devient un élément de l\'image. Event\'Light conçoit et réalise ces projections pour une commune, une entreprise ou une fête de famille.' ); ?>
	</header>

	<?php if ( $el_vedette && '' !== $el_vedette['youtube'] ) : ?>
	<figure class="stack" style="gap: var(--e-3)">
		<?php el_video( $el_vedette['youtube'], 'Vidéo : ' . $el_vedette['titre'], $el_photos ? $el_photos[0] : null, 'Lire la vidéo', true ); ?>
		<figcaption class="note"><?php echo esc_html( el_reglage( 'mapping_legende' ) ); ?> <a href="<?php echo esc_url( $el_vedette['url'] ); ?>">Voir le projet</a></figcaption>
	</figure>
	<?php endif; ?>
</div>

<?php if ( $el_etapes || $el_fiche ) : ?>
<section class="section">
	<div class="wrap split">
		<div class="split-head">
			<h2>Comment on s'y prend</h2>
			<p><?php echo esc_html( el_reglage( 'mapping_methode_texte' ) ); ?></p>
		</div>
		<div class="split-body stack-l">
			<?php if ( $el_etapes ) : ?>
			<ol class="steps">
				<?php foreach ( $el_etapes as $el_e ) : ?>
				<li>
					<h3><?php echo esc_html( $el_e[0] ); ?></h3>
					<p><?php echo esc_html( $el_e[1] ); ?></p>
				</li>
				<?php endforeach; ?>
			</ol>
			<?php endif; ?>
			<?php if ( $el_fiche ) : ?>
			<dl class="sheet">
				<?php foreach ( $el_fiche as $el_e ) : ?>
				<div><dt><?php echo esc_html( $el_e[0] ); ?></dt><dd><?php echo esc_html( $el_e[1] ); ?></dd></div>
				<?php endforeach; ?>
			</dl>
			<?php endif; ?>
		</div>
	</div>
</section>
<?php endif; ?>

<?php if ( $el_mappings ) : ?>
<section class="section">
	<div class="wrap split">
		<div class="split-head">
			<h2>Déjà projeté</h2>
			<p><?php echo esc_html( el_reglage( 'mapping_deja_texte' ) ); ?></p>
		</div>
		<div class="split-body">
			<div class="works">
				<?php array_map( 'el_work', $el_mappings ); ?>
			</div>
		</div>
	</div>
</section>
<?php endif; ?>

<?php
el_band( 'Une façade à habiller', 'Envoyez-nous une photo du bâtiment et l\'occasion. On vous dit ce qui est faisable et à quel prix.', el_url( '/devis/' ) . '?type=' . rawurlencode( 'Vidéo mapping' ), 'Demander un devis mapping' );

get_footer();
