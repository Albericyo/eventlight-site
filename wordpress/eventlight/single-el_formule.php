<?php
/**
 * Une formule : ses packs, ce qui est compris, des réalisations du même genre, le matériel associé.
 */

defined( 'ABSPATH' ) || exit;

$el_site    = el_site();
$el_formule = el_contexte()['formule'];
$el_packs   = $el_formule['packs'];

// Les réalisations dont le type correspond à cette formule.
$el_vus = array();
foreach ( el_portfolio() as $el_projet ) {
	if ( in_array( $el_projet['type'], $el_formule['typesPortfolio'], true ) ) {
		$el_vus[] = $el_projet;
	}
}
$el_materiel = array_slice( el_produits_pour_formule( $el_formule['id'] ), 0, 6 );
$el_devis    = el_url( '/devis/' ) . '?type=' . rawurlencode( $el_formule['devisType'] );

get_header();
?>
<div class="wrap">
	<header class="page-head">
		<?php el_fil(); ?>
		<h1><?php echo esc_html( $el_formule['h1'] ); ?></h1>
		<p class="lede"><?php echo esc_html( $el_formule['description'] ); ?></p>
	</header>

	<div class="packs">
		<?php foreach ( $el_packs as $el_i => $el_pack ) : ?>
			<?php $el_precedent = $el_i > 0 ? $el_packs[ $el_i - 1 ]['items'] : array(); ?>
		<article class="pack<?php echo $el_pack['featured'] ? ' lit' : ''; ?>">
			<div class="pack-head">
				<h2><?php echo esc_html( $el_pack['nom'] ); ?></h2>
				<?php if ( $el_pack['featured'] ) : ?>
				<span class="tag tag-trait">Recommandé</span>
				<?php endif; ?>
			</div>
			<p class="price"><small>à partir de</small><b><?php echo esc_html( $el_pack['prix'] ); ?></b></p>
			<?php echo el_plan_de_feu( el_couches( $el_pack['items'] ), array( 'titre' => $el_pack['nom'] . ' : schéma de l\'installation' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<ul class="checks">
				<?php foreach ( $el_pack['items'] as $el_ligne ) : ?>
				<li<?php echo ( $el_precedent && ! in_array( $el_ligne, $el_precedent, true ) ) ? ' class="plus"' : ''; ?>><?php echo esc_html( $el_ligne ); ?></li>
				<?php endforeach; ?>
			</ul>
			<a class="btn<?php echo $el_pack['featured'] ? ' btn-plein' : ''; ?>" href="<?php echo esc_url( $el_devis . '&pack=' . rawurlencode( $el_pack['nom'] ) ); ?>">Demander ce pack</a>
		</article>
		<?php endforeach; ?>
	</div>
	<?php if ( count( $el_packs ) > 1 ) : ?>
	<p class="note" style="margin-top: var(--e-4)">Les lignes en gras sont celles qui s'ajoutent au pack précédent. <?php echo esc_html( $el_site['tva'] ); ?>.</p>
	<?php endif; ?>
</div>

<section class="section">
	<div class="wrap split">
		<div class="split-head">
			<h2>Compris, en option</h2>
			<p><?php echo esc_html( el_reglage( 'personnalisation' ) ); ?></p>
		</div>
		<div class="split-body cols-2">
			<div class="stack">
				<h3>Dans chaque pack</h3>
				<ul class="checks">
					<?php foreach ( el_lignes( el_reglage( 'inclus' ) ) as $el_ligne ) : ?>
					<li class="plus"><?php echo esc_html( $el_ligne ); ?></li>
					<?php endforeach; ?>
				</ul>
			</div>
			<div class="stack">
				<h3>En option, au devis</h3>
				<ul class="checks">
					<?php foreach ( $el_formule['optionsHorsPack'] as $el_ligne ) : ?>
					<li><?php echo esc_html( $el_ligne ); ?></li>
					<?php endforeach; ?>
				</ul>
			</div>
		</div>
	</div>
</section>

<?php if ( $el_vus ) : ?>
<section class="section">
	<div class="wrap split">
		<div class="split-head">
			<h2>Déjà fait</h2>
		</div>
		<div class="split-body">
			<div class="works">
				<?php array_map( 'el_work', $el_vus ); ?>
			</div>
		</div>
	</div>
</section>
<?php endif; ?>

<?php if ( $el_materiel ) : ?>
<section class="section">
	<div class="wrap split">
		<div class="split-head">
			<h2>Le matériel associé</h2>
			<p>Ces références se louent aussi seules, à la journée.</p>
			<p><a class="btn" href="<?php echo esc_url( el_url( '/location/' ) ); ?>">Tout le catalogue</a></p>
		</div>
		<div class="split-body">
			<ul class="shelf">
				<?php array_map( 'el_item', $el_materiel ); ?>
			</ul>
		</div>
	</div>
</section>
<?php endif; ?>

<?php
el_band( 'Vérifier une date', 'Dites-nous la date, le lieu et le nombre d\'invités. Le devis est gratuit et valable 30 jours.', $el_devis, 'Demander un devis' );

get_footer();
