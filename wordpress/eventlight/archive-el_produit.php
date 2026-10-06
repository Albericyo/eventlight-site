<?php
/**
 * Le catalogue de location, catégorie par catégorie.
 */

defined( 'ABSPATH' ) || exit;

$el_site       = el_site();
$el_categories = el_categories();
$el_sans       = el_produits_de_categorie( 0 );

get_header();
?>
<div class="wrap">
	<header class="page-head">
		<?php el_fil(); ?>
		<h1><?php echo esc_html( el_reglage( 'location_titre' ) ); ?></h1>
		<p class="lede"><?php echo esc_html( el_reglage( 'location_texte' ) ); ?></p>
		<p class="note"><?php echo esc_html( $el_site['livraison'] ); ?>. Tous les câbles nécessaires peuvent être fournis. Prix TTC, <?php echo esc_html( $el_site['tva'] ); ?>.</p>
		<?php if ( $el_categories ) : ?>
		<nav class="tags" aria-label="Catégories de location">
			<?php foreach ( $el_categories as $el_c ) : ?>
			<a class="tag" href="#<?php echo esc_attr( $el_c['slug'] ); ?>"><?php echo esc_html( $el_c['nom'] ); ?></a>
			<?php endforeach; ?>
		</nav>
		<?php endif; ?>
	</header>

	<?php foreach ( $el_categories as $el_c ) : ?>
		<?php
		$el_liste = el_produits_de_categorie( $el_c['id'] );
		if ( ! $el_liste ) {
			continue;
		}
		?>
	<section class="cat-block split" id="<?php echo esc_attr( $el_c['slug'] ); ?>">
		<div class="split-head">
			<h2><a href="<?php echo esc_url( $el_c['url'] ); ?>"><?php echo esc_html( $el_c['nom'] ); ?></a></h2>
			<p><?php echo esc_html( $el_c['intro'] ); ?></p>
		</div>
		<div class="split-body">
			<ul class="shelf">
				<?php array_map( 'el_item', $el_liste ); ?>
			</ul>
		</div>
	</section>
	<?php endforeach; ?>

	<?php if ( $el_sans ) : ?>
	<section class="cat-block split" id="autres">
		<div class="split-head">
			<h2>Autres références</h2>
		</div>
		<div class="split-body">
			<ul class="shelf">
				<?php array_map( 'el_item', $el_sans ); ?>
			</ul>
		</div>
	</section>
	<?php endif; ?>
</div>

<?php
el_band(
	'Ce qui n\'est pas au catalogue',
	'Une référence qui n\'est pas au catalogue, une livraison, un technicien pour l\'installation : dites-le dans la demande, on vous répond avec un devis.',
	el_url( '/devis/' ) . '?type=' . rawurlencode( 'Location de matériel' ),
	'Demander un devis de location'
);

get_footer();
