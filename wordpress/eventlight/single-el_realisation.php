<?php
/**
 * Une réalisation : vidéo ou photo en tête, fiche technique, galerie, réalisations voisines.
 */

defined( 'ABSPATH' ) || exit;

$el_projet     = el_realisation( get_queried_object() );
$el_photos     = el_galerie( $el_projet['id'] );
$el_nombre     = count( $el_photos );
$el_couverture = el_couverture_realisation( $el_projet['id'] );

// Réalisation précédente et suivante, dans l'ordre de la liste.
$el_liste = el_portfolio();
$el_rang  = -1;
foreach ( $el_liste as $el_i => $el_p ) {
	if ( $el_p['id'] === $el_projet['id'] ) {
		$el_rang = $el_i;
	}
}
$el_total = count( $el_liste );
$el_avant = $el_rang >= 0 && $el_total > 1 ? $el_liste[ ( $el_rang - 1 + $el_total ) % $el_total ] : null;
$el_apres = $el_rang >= 0 && $el_total > 2 ? $el_liste[ ( $el_rang + 1 ) % $el_total ] : null;

get_header();
?>
<div class="wrap">
	<header class="page-head">
		<?php el_fil(); ?>
		<h1><?php echo esc_html( el_titre_court( $el_projet['titre'] ) ); ?></h1>
		<?php if ( '' !== $el_projet['sousTitre'] ) : ?>
		<p class="lede"><?php echo esc_html( el_virgules( $el_projet['sousTitre'] ) ); ?></p>
		<?php endif; ?>
		<div class="tags"><?php echo '' !== $el_projet['type'] ? '<span class="tag">' . esc_html( $el_projet['type'] ) . '</span>' : ''; ?><?php echo ( '' !== $el_projet['annee'] && $el_projet['annee'] !== $el_projet['sousTitre'] ) ? '<span class="tag tag-trait">' . esc_html( $el_projet['annee'] ) . '</span>' : ''; ?></div>
	</header>

	<?php if ( '' !== $el_projet['youtube'] ) : ?>
		<?php el_video( $el_projet['youtube'], 'Vidéo : ' . $el_projet['titre'], $el_couverture, 'Lire la vidéo', true ); ?>
	<?php elseif ( $el_photos ) : ?>
	<div class="screen" style="aspect-ratio: 16 / 9">
		<?php echo el_img( $el_photos[0], $el_projet['titre'] . ', ' . $el_projet['annee'], '(min-width: 86rem) 80rem, 100vw', 'eager' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</div>
	<?php endif; ?>
</div>

<section class="section-tight">
	<div class="wrap split">
		<div class="split-head">
			<h2 class="sr">Fiche technique</h2>
			<dl class="sheet">
				<?php if ( '' !== $el_projet['production'] ) : ?>
				<div><dt>Production</dt><dd><?php echo esc_html( $el_projet['production'] ); ?></dd></div>
				<?php endif; ?>
				<?php foreach ( $el_projet['credits'] as $el_ligne ) : ?>
					<?php $el_credit = el_credit( $el_ligne ); ?>
				<div><dt><?php echo esc_html( '' !== $el_credit['role'] ? $el_credit['role'] : 'Crédit' ); ?></dt><dd><?php echo esc_html( $el_credit['nom'] ); ?></dd></div>
				<?php endforeach; ?>
				<?php if ( '' !== $el_projet['logiciel'] ) : ?>
				<div><dt>Logiciels</dt><dd><?php echo esc_html( $el_projet['logiciel'] ); ?></dd></div>
				<?php endif; ?>
				<?php if ( '' !== $el_projet['technologie'] ) : ?>
				<div><dt>Matériel</dt><dd><?php echo esc_html( $el_projet['technologie'] ); ?></dd></div>
				<?php endif; ?>
			</dl>
			<?php if ( '' !== $el_projet['youtube'] ) : ?>
			<p class="note">La vidéo est hébergée par YouTube : elle ne se charge qu'au clic.</p>
			<?php endif; ?>
		</div>
		<div class="split-body stack-l">
			<p class="lede" style="max-width: 60ch"><?php echo nl2br( esc_html( $el_projet['texte'] ) ); ?></p>

			<?php if ( $el_nombre > 1 ) : ?>
			<div class="gallery">
				<?php foreach ( $el_photos as $el_i => $el_m ) : ?>
					<?php $el_alt = $el_projet['titre'] . ', image ' . ( $el_i + 1 ) . ' sur ' . $el_nombre; ?>
				<a href="<?php echo esc_url( el_media_grande( $el_m ) ); ?>" data-zoom data-alt="<?php echo esc_attr( $el_alt ); ?>">
					<span class="screen"><?php echo el_img( $el_m, $el_alt, '(min-width: 62em) 20rem, 50vw' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				</a>
				<?php endforeach; ?>
			</div>
			<?php endif; ?>
		</div>
	</div>
</section>

<section class="section-tight">
	<div class="wrap">
		<div class="works">
			<?php
			if ( $el_avant ) {
				el_work( $el_avant );
			}
			if ( $el_apres ) {
				el_work( $el_apres );
			}
			?>
			<div class="work" style="align-content: end">
				<a class="btn" href="<?php echo esc_url( el_url( '/portfolio/' ) ); ?>">Toutes les réalisations</a>
			</div>
		</div>
	</div>
</section>

<?php
el_band(
	'Un projet du même genre',
	'Décrivez-nous le lieu et l\'occasion. On vous dit ce qu\'il faut prévoir et on chiffre.',
	'Mapping' === $el_projet['type'] ? el_url( '/devis/' ) . '?type=' . rawurlencode( 'Vidéo mapping' ) : el_url( '/devis/' ),
	'Demander un devis'
);
?>

<dialog class="lightbox" id="lightbox" aria-label="Image en plein écran">
	<figure>
		<img src="data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==" alt="">
		<figcaption class="lightbox-bar">
			<span data-zoom-compte></span>
			<span class="actions">
				<button class="btn btn-s" type="button" data-zoom-pas="-1">Précédente</button>
				<button class="btn btn-s" type="button" data-zoom-pas="1">Suivante</button>
				<button class="btn btn-s" type="button" data-zoom-fermer>Fermer</button>
			</span>
		</figcaption>
	</figure>
</dialog>

<?php
get_footer();
