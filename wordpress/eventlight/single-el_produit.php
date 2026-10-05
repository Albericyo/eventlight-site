<?php
/**
 * Fiche d'un produit du catalogue.
 */

defined( 'ABSPATH' ) || exit;

$el_site       = el_site();
$el_produit    = el_contexte()['produit'];
$el_photos     = el_galerie( $el_produit['id'] );
$el_principale = el_couverture_produit( $el_produit['id'] );
$el_prix       = el_prix_detail( $el_produit['prix'] );
$el_nombre     = count( $el_photos );

// Image d'attente de la vidéo : de préférence une photo en situation.
$el_affiche = null;
foreach ( array( 'sombre', 'image' ) as $el_fond ) {
	foreach ( $el_photos as $el_m ) {
		if ( ! $el_affiche && $el_m['fond'] === $el_fond ) {
			$el_affiche = $el_m;
		}
	}
}

// Trois autres références de la même catégorie.
$el_voisins = array();
foreach ( el_produits_de_categorie( $el_produit['categorieId'] ) as $el_p ) {
	if ( $el_p['id'] !== $el_produit['id'] && count( $el_voisins ) < 3 ) {
		$el_voisins[] = $el_p;
	}
}

get_header();
?>
<div class="wrap">
	<div class="page-head">
		<?php el_fil(); ?>
	</div>

	<div class="product">
		<div class="product-media">
			<div class="vitrine" data-photo>
				<?php
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo $el_principale ? el_img( $el_principale, $el_produit['nom'] . ', en location à ' . $el_site['city'] . ' chez Event\'Light', '(min-width: 56em) 45vw, 100vw', 'eager' ) : el_picto_produit( $el_produit, $el_produit['nom'] );
				?>
			</div>
			<?php if ( $el_nombre > 1 ) : ?>
			<div class="thumbs">
				<?php foreach ( $el_photos as $el_i => $el_m ) : ?>
				<button type="button" data-vignette="<?php echo esc_url( el_media_grande( $el_m ) ); ?>" data-fond="<?php echo esc_attr( $el_m['fond'] ); ?>" aria-pressed="<?php echo $el_m['id'] === $el_principale['id'] ? 'true' : 'false'; ?>" aria-label="Photo <?php echo (int) $el_i + 1; ?> sur <?php echo (int) $el_nombre; ?>">
					<span class="vitrine"><img src="<?php echo esc_url( el_media_petite( $el_m ) ); ?>" width="<?php echo (int) $el_m['w']; ?>" height="<?php echo (int) $el_m['h']; ?>" alt="" loading="lazy" decoding="async" data-fond="<?php echo esc_attr( $el_m['fond'] ); ?>"></span>
				</button>
				<?php endforeach; ?>
			</div>
			<?php endif; ?>
		</div>

		<div class="product-body">
			<?php if ( '' !== $el_produit['categorie'] ) : ?>
			<div class="tags"><a class="tag" href="<?php echo esc_url( $el_produit['categorieUrl'] ); ?>"><?php echo esc_html( $el_produit['categorie'] ); ?></a></div>
			<?php endif; ?>
			<h1>Location <?php echo esc_html( $el_produit['nom'] ); ?> à <?php echo esc_html( $el_site['city'] ); ?></h1>
			<p class="price"><b><?php echo esc_html( $el_prix['montant'] ); ?>&nbsp;<?php echo esc_html( $el_prix['unite'] ); ?></b><?php echo '' !== $el_prix['note'] ? '<small>' . esc_html( $el_prix['note'] ) . '</small>' : ''; ?><small>Même tarif pour le week-end</small></p>
			<p class="lede"><?php echo esc_html( $el_produit['description'] ); ?></p>
			<div class="actions">
				<button class="btn btn-plein add" type="button" data-add="<?php echo esc_attr( $el_produit['slug'] ); ?>" data-nom="<?php echo esc_attr( $el_produit['nom'] ); ?>" data-prix="<?php echo esc_attr( $el_produit['prix'] ); ?>" data-label-in="Dans ma sélection">Ajouter à ma sélection</button>
				<a class="btn" href="<?php echo esc_url( el_url( '/devis/' ) . '?type=' . rawurlencode( 'Location de matériel' ) ); ?>">Demander un devis</a>
			</div>
			<?php if ( $el_produit['details'] ) : ?>
			<div class="stack">
				<h2 class="sr">Caractéristiques</h2>
				<ul class="checks">
					<?php foreach ( $el_produit['details'] as $el_ligne ) : ?>
					<li><?php echo esc_html( $el_ligne ); ?></li>
					<?php endforeach; ?>
				</ul>
			</div>
			<?php endif; ?>
			<?php if ( $el_produit['usages'] ) : ?>
			<div class="stack">
				<h2 style="font: 600 1rem/1.3 var(--texte)">On le sort souvent pour</h2>
				<div class="tags">
					<?php foreach ( $el_produit['usages'] as $el_u ) : ?>
					<a class="tag" href="<?php echo esc_url( $el_u['url'] ); ?>"><?php echo esc_html( $el_u['titre'] ); ?></a>
					<?php endforeach; ?>
				</div>
			</div>
			<?php endif; ?>
			<p class="note"><?php echo esc_html( $el_site['livraison'] ); ?>. Tous les câbles nécessaires peuvent être fournis.</p>
		</div>
	</div>
</div>

<?php if ( '' !== $el_produit['youtube'] ) : ?>
<section class="section">
	<div class="wrap split">
		<div class="split-head">
			<h2>En démonstration</h2>
			<p>La vidéo est hébergée par YouTube : elle ne se charge qu'au clic.</p>
		</div>
		<div class="split-body">
			<?php el_video( $el_produit['youtube'], 'Démonstration, ' . $el_produit['nom'], $el_affiche, 'Lire la démonstration' ); ?>
		</div>
	</div>
</section>
<?php endif; ?>

<?php if ( $el_voisins ) : ?>
<section class="section">
	<div class="wrap split">
		<div class="split-head">
			<h2>Dans la même catégorie</h2>
			<p><a class="btn" href="<?php echo esc_url( el_url( '/location/' ) ); ?>">Tout le catalogue</a></p>
		</div>
		<div class="split-body">
			<ul class="shelf">
				<?php array_map( 'el_item', $el_voisins ); ?>
			</ul>
		</div>
	</div>
</section>
<?php endif; ?>

<?php
get_footer();
