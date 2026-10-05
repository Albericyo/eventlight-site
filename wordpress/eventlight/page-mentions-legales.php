<?php
/**
 * Template Name: Mentions légales
 *
 * À gauche, l'identité de l'éditeur (réglages du thème). À droite, le texte de la page :
 * les conditions générales de vente, modifiables dans l'éditeur de WordPress.
 */

defined( 'ABSPATH' ) || exit;

$el_site      = el_site();
$el_hebergeur = el_lignes( el_reglage( 'hebergeur' ) );
$el_directeur = el_reglage( 'directeur' );

get_header();
?>
<div class="wrap">
	<header class="page-head">
		<?php el_fil(); ?>
		<h1><?php the_title(); ?></h1>
	</header>

	<div class="split">
		<div class="split-head">
			<h2 class="sr">Éditeur du site</h2>
			<dl class="sheet">
				<div><dt>Éditeur</dt><dd>Event'Light</dd></div>
				<div><dt>Siège social</dt><dd><?php echo esc_html( $el_site['street'] ); ?><br><?php echo esc_html( $el_site['postalCode'] . ' ' . $el_site['city'] ); ?></dd></div>
				<?php if ( '' !== $el_site['siret'] ) : ?>
				<div><dt>SIRET</dt><dd><?php echo esc_html( $el_site['siret'] ); ?></dd></div>
				<?php endif; ?>
				<?php if ( '' !== $el_directeur ) : ?>
				<div><dt>Directeur de la publication</dt><dd><?php echo esc_html( $el_directeur ); ?></dd></div>
				<?php endif; ?>
				<div><dt>E-mail</dt><dd><a href="mailto:<?php echo esc_attr( antispambot( $el_site['email'] ) ); ?>"><?php echo esc_html( antispambot( $el_site['email'] ) ); ?></a></dd></div>
				<?php if ( '' !== $el_site['tva'] ) : ?>
				<div><dt>TVA</dt><dd><?php echo esc_html( ucfirst( str_replace( array( 'art. ', 'CGI' ), array( 'article ', 'Code général des impôts' ), preg_replace( '/^TVA\s+/u', '', $el_site['tva'] ) ) ) ); ?></dd></div>
				<?php endif; ?>
				<?php if ( $el_hebergeur ) : ?>
				<div><dt>Hébergement</dt><dd><?php echo implode( '<br>', array_map( 'make_clickable', array_map( 'esc_html', $el_hebergeur ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></dd></div>
				<?php endif; ?>
			</dl>
		</div>

		<div class="split-body prose">
			<?php
			while ( have_posts() ) {
				the_post();
				the_content();
			}
			?>
		</div>
	</div>
</div>
<?php
get_footer();
