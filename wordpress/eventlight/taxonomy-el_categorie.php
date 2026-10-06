<?php
/**
 * Une catégorie du catalogue.
 */

defined( 'ABSPATH' ) || exit;

$el_site      = el_site();
$el_terme     = get_queried_object();
$el_categorie = el_categorie_par( 'id', $el_terme->term_id );
$el_autres    = array();
foreach ( el_categories() as $el_c ) {
	if ( $el_c['id'] !== $el_categorie['id'] ) {
		$el_autres[] = $el_c;
	}
}

get_header();
?>
<div class="wrap">
	<header class="page-head">
		<?php el_fil(); ?>
		<h1><?php echo esc_html( $el_categorie['h1'] ); ?></h1>
		<?php if ( '' !== $el_categorie['intro'] ) : ?>
		<p class="lede"><?php echo esc_html( $el_categorie['intro'] ); ?></p>
		<?php endif; ?>
		<p class="note"><?php echo esc_html( $el_site['livraison'] ); ?>. Tarif journalier identique le week-end.</p>
	</header>

	<ul class="shelf">
		<?php
		foreach ( el_produits_de_categorie( $el_categorie['id'] ) as $el_p ) {
			el_item( $el_p, 2 );
		}
		?>
	</ul>
</div>

<?php if ( $el_autres ) : ?>
<section class="section">
	<div class="wrap split">
		<div class="split-head">
			<h2>Le reste du catalogue</h2>
		</div>
		<div class="split-body">
			<ul class="cats">
				<?php array_map( 'el_cat', $el_autres ); ?>
			</ul>
		</div>
	</div>
</section>
<?php endif; ?>

<?php
el_band(
	'Réserver ce matériel',
	'Ajoutez ce qu\'il vous faut à votre sélection, puis envoyez la demande avec vos dates.',
	el_url( '/devis/' ) . '?type=' . rawurlencode( 'Location de matériel' ),
	'Demander un devis de location'
);

get_footer();
