<?php
/**
 * Bas de page : formules, catégories du catalogue, contacts, mentions.
 */

defined( 'ABSPATH' ) || exit;

$el_site = el_site();
?>
</main>

<footer class="foot">
	<div class="wrap foot-grid">
		<div class="foot-brand">
			<a href="<?php echo esc_url( el_url( '/' ) ); ?>" aria-label="Event'Light, accueil"><?php echo el_logo( 'logo-courant' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
			<p class="foot-tagline"><?php echo esc_html( $el_site['tagline'] ); ?></p>
		</div>
		<div class="foot-cols">
			<div>
				<h2>Formules</h2>
				<ul>
					<?php foreach ( el_formules() as $el_f ) : ?>
					<li><a href="<?php echo esc_url( $el_f['url'] ); ?>"><?php echo esc_html( $el_f['titre'] ); ?></a></li>
					<?php endforeach; ?>
					<li><a href="<?php echo esc_url( el_url( '/video-mapping/' ) ); ?>">Vidéo mapping</a></li>
				</ul>
			</div>
			<div>
				<h2>Location à <?php echo esc_html( $el_site['city'] ); ?></h2>
				<ul>
					<?php foreach ( el_categories() as $el_c ) : ?>
					<li><a href="<?php echo esc_url( $el_c['url'] ); ?>"><?php echo esc_html( $el_c['nom'] ); ?></a></li>
					<?php endforeach; ?>
					<li><a href="<?php echo esc_url( el_url( '/location/' ) ); ?>">Tout le catalogue</a></li>
				</ul>
			</div>
			<div>
				<h2>Contact</h2>
				<ul>
					<?php foreach ( $el_site['contacts'] as $el_c ) : ?>
					<li><?php echo esc_html( $el_c['nom'] ); ?><br><a href="<?php echo esc_url( $el_c['telHref'] ); ?>"><?php echo esc_html( $el_c['tel'] ); ?></a></li>
					<?php endforeach; ?>
					<li><a href="mailto:<?php echo esc_attr( antispambot( $el_site['email'] ) ); ?>"><?php echo esc_html( antispambot( $el_site['email'] ) ); ?></a></li>
					<?php
					$el_reseaux = array();
					foreach ( array( 'facebook' => 'Facebook', 'instagram' => 'Instagram', 'youtube' => 'YouTube' ) as $el_cle => $el_nom ) {
						if ( '' !== $el_site[ $el_cle ] ) {
							$el_reseaux[] = '<a href="' . esc_url( $el_site[ $el_cle ] ) . '" rel="noopener">' . $el_nom . '</a>';
						}
					}
					if ( $el_reseaux ) :
						?>
					<li><?php echo implode( ', ', $el_reseaux ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></li>
					<?php endif; ?>
				</ul>
			</div>
		</div>
	</div>
	<div class="wrap foot-legal">
		<p>© <?php echo esc_html( wp_date( 'Y' ) ); ?> Event'Light, <?php echo esc_html( $el_site['address'] ); ?>. SIRET <?php echo esc_html( $el_site['siret'] ); ?>. Prix TTC, <?php echo esc_html( $el_site['tva'] ); ?>. Les tarifs de location à la journée valent aussi pour le week-end. <a href="<?php echo esc_url( el_url( '/mentions-legales/' ) ); ?>">Mentions légales et CGV</a>, <a href="<?php echo esc_url( el_url( '/charte/' ) ); ?>">logo et charte graphique</a></p>
		<button class="switch" type="button" data-salle aria-pressed="false">Éteindre la salle</button>
	</div>
</footer>

<p class="toast" id="toast" role="status" hidden></p>
<?php wp_footer(); ?>
</body>
</html>
