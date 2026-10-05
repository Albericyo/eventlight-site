<?php
/**
 * Template Name: Contact
 *
 * Téléphones, e-mail, adresse. Tout vient des réglages du thème.
 */

defined( 'ABSPATH' ) || exit;

$el_site = el_site();

get_header();
?>
<div class="wrap">
	<header class="page-head">
		<?php el_fil(); ?>
		<h1><?php the_title(); ?></h1>
		<?php el_lede_page( 'Une question, une date à vérifier, un devis : appelez ou écrivez, on est à ' . $el_site['city'] . '.' ); ?>
	</header>

	<div class="people">
		<?php foreach ( $el_site['contacts'] as $el_c ) : ?>
		<div class="person">
			<h2><?php echo esc_html( $el_c['nom'] ); ?></h2>
			<a class="big" href="<?php echo esc_url( $el_c['telHref'] ); ?>"><?php echo esc_html( $el_c['tel'] ); ?></a>
		</div>
		<?php endforeach; ?>
		<div class="person">
			<h2>E-mail</h2>
			<a href="mailto:<?php echo esc_attr( antispambot( $el_site['email'] ) ); ?>" style="font-size: 1.25rem; font-weight: 500"><?php echo esc_html( antispambot( $el_site['email'] ) ); ?></a>
			<?php
			// « Facebook, Instagram, YouTube : EventLight80, @eventlight80, la chaîne. »
			$el_noms  = array();
			$el_liens = array();
			$el_fin   = function ( $url ) {
				return basename( untrailingslashit( (string) wp_parse_url( $url, PHP_URL_PATH ) ) );
			};
			if ( '' !== $el_site['facebook'] ) {
				$el_noms[]  = 'Facebook';
				$el_liens[] = '<a href="' . esc_url( $el_site['facebook'] ) . '" rel="noopener">' . esc_html( '' !== $el_fin( $el_site['facebook'] ) ? $el_fin( $el_site['facebook'] ) : 'la page' ) . '</a>';
			}
			if ( '' !== $el_site['instagram'] ) {
				$el_noms[]  = 'Instagram';
				$el_liens[] = '<a href="' . esc_url( $el_site['instagram'] ) . '" rel="noopener">' . esc_html( '' !== $el_fin( $el_site['instagram'] ) ? '@' . $el_fin( $el_site['instagram'] ) : 'le compte' ) . '</a>';
			}
			if ( '' !== $el_site['youtube'] ) {
				$el_noms[]  = 'YouTube';
				$el_liens[] = '<a href="' . esc_url( $el_site['youtube'] ) . '" rel="noopener">la chaîne</a>';
			}
			if ( $el_liens ) :
				?>
			<p class="note"><?php echo esc_html( implode( ', ', $el_noms ) ); ?> : <?php echo implode( ', ', $el_liens ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>.</p>
			<?php endif; ?>
		</div>
	</div>
</div>

<section class="section">
	<div class="wrap split">
		<div class="split-head">
			<h2>Où nous trouver</h2>
		</div>
		<div class="split-body stack">
			<dl class="sheet">
				<div><dt>Adresse</dt><dd><?php echo esc_html( $el_site['street'] ); ?><br><?php echo esc_html( $el_site['postalCode'] . ' ' . $el_site['city'] ); ?></dd></div>
				<?php if ( '' !== $el_site['secteur'] ) : ?>
				<div><dt>Secteur</dt><dd><?php echo esc_html( $el_site['secteur'] ); ?></dd></div>
				<?php endif; ?>
				<?php if ( '' !== $el_site['livraison'] ) : ?>
				<div><dt>Livraison</dt><dd><?php echo esc_html( $el_site['livraison'] ); ?></dd></div>
				<?php endif; ?>
			</dl>
			<div class="actions">
				<?php if ( '' !== $el_site['latitude'] && '' !== $el_site['longitude'] ) : ?>
				<a class="btn" href="<?php echo esc_url( 'https://www.openstreetmap.org/?mlat=' . rawurlencode( $el_site['latitude'] ) . '&mlon=' . rawurlencode( $el_site['longitude'] ) . '#map=17/' . rawurlencode( $el_site['latitude'] ) . '/' . rawurlencode( $el_site['longitude'] ) ); ?>" rel="noopener">Voir sur la carte</a>
				<?php endif; ?>
				<a class="btn" href="<?php echo esc_url( 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode( $el_site['address'] ) ); ?>" rel="noopener">Itinéraire Google Maps</a>
			</div>
		</div>
	</div>
</section>

<?php
el_band( 'Le plus simple, c\'est un devis', 'Décrivez l\'événement une fois, on vous répond avec ce qu\'il faut prévoir et le tarif.', el_url( '/devis/' ), 'Demander un devis' );

get_footer();
