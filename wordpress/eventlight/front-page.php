<?php
/**
 * Page d'accueil.
 */

defined( 'ABSPATH' ) || exit;

$el_site     = el_site();
$el_formules = el_formules();

// Les images projetées dans le faisceau.
$el_scenes = array();
foreach ( (array) el_reglage( 'scenes' ) as $el_i => $el_s ) {
	$el_m = is_array( $el_s ) && ! empty( $el_s['image'] ) ? el_media( $el_s['image'] ) : null;
	if ( ! $el_m ) {
		continue;
	}
	$el_r        = ! empty( $el_s['realisation'] ) ? el_realisation_par_id( $el_s['realisation'] ) : null;
	$el_scenes[] = array(
		'id'      => 'scene-' . ( $el_i + 1 ),
		'label'   => isset( $el_s['label'] ) ? $el_s['label'] : '',
		'media'   => $el_m,
		'url'     => $el_r ? $el_r['url'] : el_url( '/portfolio/' ),
		'cadrage' => preg_replace( '/[^0-9a-z%. -]/i', '', isset( $el_s['cadrage'] ) && '' !== $el_s['cadrage'] ? $el_s['cadrage'] : '50% 50%' ),
		'legende' => isset( $el_s['legende'] ) ? $el_s['legende'] : '',
	);
}

/** Les boutons qui changent l'image projetée. */
$el_boutons = function () use ( $el_scenes ) {
	foreach ( $el_scenes as $el_i => $el_s ) {
		?>
		<li><button class="scene-btn" type="button" data-scene-btn="<?php echo esc_attr( $el_s['id'] ); ?>" data-legende="<?php echo esc_attr( $el_s['legende'] ); ?>" data-url="<?php echo esc_url( $el_s['url'] ); ?>" aria-pressed="<?php echo 0 === $el_i ? 'true' : 'false'; ?>"><?php echo esc_html( $el_s['label'] ); ?></button></li>
		<?php
	}
};

// Le composeur : son état de départ est celui du premier type d'événement.
$el_composeur = $el_formules ? el_composeur() : null;
$el_depart    = null;
if ( $el_composeur && ! empty( $el_composeur['eventTypes'] ) ) {
	$el_type    = $el_composeur['eventTypes'][0];
	$el_formule = null;
	foreach ( $el_formules as $el_f ) {
		if ( isset( $el_type['formuleSlug'] ) && $el_f['slug'] === $el_type['formuleSlug'] ) {
			$el_formule = $el_f;
		}
	}
	$el_pack = null;
	if ( $el_formule ) {
		$el_pack = el_pack_recommande( $el_formule );
		foreach ( $el_formule['packs'] as $el_p ) {
			if ( isset( $el_type['recommendedPack'] ) && $el_p['nom'] === $el_type['recommendedPack'] ) {
				$el_pack = $el_p;
			}
		}
	}
	$el_pistes = isset( $el_type['defaultTracks'] ) ? (array) $el_type['defaultTracks'] : array();
	$el_depart = array(
		'type'    => $el_type,
		'formule' => $el_formule,
		'pack'    => $el_pack,
		'pistes'  => $el_pistes,
		'devis'   => el_url( '/devis/' ) . '?type=' . rawurlencode( isset( $el_type['devisType'] ) ? $el_type['devisType'] : $el_type['label'] ) . ( $el_pistes ? '&pistes=' . implode( ',', $el_pistes ) : '' ),
	);
}

// Les réalisations mises en avant : celles choisies dans les réglages, sinon les cinq premières.
$el_vedettes = array();
foreach ( (array) el_reglage( 'accueil_realisations' ) as $el_id ) {
	$el_r = $el_id ? el_realisation_par_id( $el_id ) : null;
	if ( $el_r ) {
		$el_vedettes[] = $el_r;
	}
}
if ( ! $el_vedettes ) {
	$el_vedettes = array_slice( el_portfolio(), 0, 5 );
}

get_header();
?>

<section class="hero">
	<div class="wrap">
		<div class="hero-stage" data-hero>
			<h1 class="hero-title"><?php echo esc_html( el_reglage( 'accueil_titre' ) ); ?></h1>

			<div class="hero-mark">
				<div class="hero-beam">
					<?php foreach ( $el_scenes as $el_i => $el_s ) : ?>
						<?php
						$el_jeu = array();
						foreach ( $el_s['media']['sources'] as $el_l => $el_adresse ) {
							$el_jeu[] = esc_url( $el_adresse ) . ' ' . $el_l . 'w';
						}
						?>
					<img class="hero-shot<?php echo 0 === $el_i ? ' is-on' : ''; ?>" data-scene="<?php echo esc_attr( $el_s['id'] ); ?>"
						src="<?php echo esc_url( el_media_grande( $el_s['media'] ) ); ?>"
						srcset="<?php echo implode( ', ', $el_jeu ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>"
						sizes="(min-width: 86rem) 68rem, 84vw"
						width="<?php echo (int) $el_s['media']['w']; ?>" height="<?php echo (int) $el_s['media']['h']; ?>" alt="<?php echo esc_attr( $el_s['legende'] ); ?>"
						style="object-position: <?php echo esc_attr( $el_s['cadrage'] ); ?>"
						<?php echo 0 === $el_i ? 'fetchpriority="high"' : 'loading="lazy"'; ?> decoding="async">
					<?php endforeach; ?>
				</div>
				<?php echo el_signe(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php if ( $el_scenes ) : ?>
				<div class="hero-box">
					<ul aria-label="Changer l'image projetée">
						<?php $el_boutons(); ?>
					</ul>
				</div>
				<?php endif; ?>
			</div>

			<?php if ( $el_scenes ) : ?>
			<p class="hero-cap">Dans le faisceau : <a href="<?php echo esc_url( $el_scenes[0]['url'] ); ?>" data-hero-cap><?php echo esc_html( $el_scenes[0]['legende'] ); ?></a></p>

			<ul class="hero-scenes-m" aria-label="Changer l'image projetée">
				<?php $el_boutons(); ?>
			</ul>
			<?php endif; ?>

			<div class="hero-lead">
				<p><?php echo esc_html( el_reglage( 'accueil_texte' ) ); ?></p>
				<div class="actions">
					<a class="btn btn-plein" href="<?php echo esc_url( el_url( '/devis/' ) ); ?>">Demander un devis <?php echo el_signe(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
					<a class="btn" href="<?php echo esc_url( el_url( '/location/' ) ); ?>">Voir le catalogue de location</a>
				</div>
			</div>
		</div>
	</div>
</section>

<?php if ( $el_formules ) : ?>
<section class="section" id="formules">
	<div class="wrap split">
		<div class="split-head">
			<h2>Les formules</h2>
			<p>Montage, prestation et démontage compris. <?php echo esc_html( el_reglage( 'note_prix' ) ); ?></p>
			<p class="note"><?php echo esc_html( $el_site['tva'] ); ?>.</p>
		</div>
		<div class="split-body">
			<ul class="rows">
				<?php array_map( 'el_row', $el_formules ); ?>
			</ul>
		</div>
	</div>
</section>
<?php endif; ?>

<?php if ( $el_depart ) : ?>
<section class="section" id="composer">
	<div class="wrap split">
		<div class="split-head">
			<h2>Composez votre soirée</h2>
			<p>Choisissez le type d'événement, allumez ce qu'il vous faut. Le dessin se met à jour et on vous indique le pack de départ.</p>
		</div>
		<div class="split-body">
			<div class="composer" data-composer>
				<div class="composer-controls">
					<fieldset>
						<legend>Type d'événement</legend>
						<div class="chips">
							<?php foreach ( $el_composeur['eventTypes'] as $el_i => $el_t ) : ?>
							<label class="chip">
								<input type="radio" name="composer-type" id="composer-type-<?php echo esc_attr( $el_t['id'] ); ?>" value="<?php echo esc_attr( $el_t['id'] ); ?>"<?php echo 0 === $el_i ? ' checked' : ''; ?>>
								<span><?php echo esc_html( $el_t['label'] ); ?></span>
							</label>
							<?php endforeach; ?>
						</div>
					</fieldset>
					<fieldset>
						<legend>À allumer</legend>
						<div class="toggles">
							<?php foreach ( $el_composeur['tracks'] as $el_piste ) : ?>
							<label class="toggle">
								<input type="checkbox" data-piste id="composer-track-<?php echo esc_attr( $el_piste['id'] ); ?>" value="<?php echo esc_attr( $el_piste['id'] ); ?>"<?php echo in_array( $el_piste['id'], $el_depart['pistes'], true ) ? ' checked' : ''; ?>>
								<?php echo el_signe(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								<span class="toggle-label"><?php echo esc_html( $el_piste['label'] ); ?></span>
							</label>
							<?php endforeach; ?>
						</div>
					</fieldset>
				</div>

				<div class="composer-out lit">
					<div data-composer-plan><?php echo el_plan_de_feu( $el_depart['pack'] ? el_couches( $el_depart['pack']['items'] ) : array(), array( 'complet' => true ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
					<div class="stack" aria-live="polite">
						<h3 data-composer-title><?php echo esc_html( $el_depart['formule'] ? 'Formule ' . $el_depart['formule']['titre'] : $el_depart['type']['label'] ); ?></h3>
						<p class="composer-pack" data-composer-pack><?php echo esc_html( $el_depart['pack'] ? $el_depart['pack']['nom'] . ', à partir de ' . $el_depart['pack']['prix'] : 'Prestation sur devis' ); ?></p>
						<p data-composer-hint><?php echo esc_html( isset( $el_depart['type']['blurb'] ) ? $el_depart['type']['blurb'] : '' ); ?></p>
						<ul class="checks" data-composer-addons></ul>
					</div>
					<div class="actions">
						<a class="btn btn-plein" data-composer-devis href="<?php echo esc_url( $el_depart['devis'] ); ?>">Continuer vers le devis <?php echo el_signe(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
						<a class="btn" data-composer-secondary href="<?php echo esc_url( $el_depart['formule'] ? $el_depart['formule']['url'] : el_url( '/nos-formules/' ) ); ?>">Voir la formule</a>
					</div>
				</div>
			</div>
			<script type="application/json" id="composer-data"><?php echo wp_json_encode( $el_composeur, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP ); ?></script>
		</div>
	</div>
</section>
<?php endif; ?>

<?php if ( el_categories() ) : ?>
<section class="section" id="location">
	<div class="wrap split">
		<div class="split-head">
			<h2>Le matériel se loue aussi</h2>
			<p>Tarif à la journée, identique pour le week-end. <?php echo esc_html( $el_site['livraison'] ); ?>. Les câbles sont fournis sur demande.</p>
			<p><a class="btn" href="<?php echo esc_url( el_url( '/location/' ) ); ?>">Ouvrir le catalogue</a></p>
		</div>
		<div class="split-body">
			<ul class="cats">
				<?php array_map( 'el_cat', el_categories() ); ?>
			</ul>
		</div>
	</div>
</section>
<?php endif; ?>

<?php if ( $el_vedettes ) : ?>
<section class="section" id="realisations">
	<div class="wrap split">
		<div class="split-head">
			<h2>Réalisations</h2>
			<p><?php echo esc_html( el_reglage( 'accueil_realisations_texte' ) ); ?></p>
			<div class="actions">
				<a class="btn" href="<?php echo esc_url( el_url( '/portfolio/' ) ); ?>">Toutes les réalisations</a>
				<a class="btn" href="<?php echo esc_url( el_url( '/video-mapping/' ) ); ?>">Le vidéo mapping</a>
			</div>
		</div>
		<div class="split-body">
			<div class="works">
				<?php
				foreach ( $el_vedettes as $el_i => $el_r ) {
					el_work( $el_r, 0 === $el_i );
				}
				?>
			</div>
		</div>
	</div>
</section>
<?php endif; ?>

<?php $el_etapes = el_paires( el_reglage( 'etapes' ) ); ?>
<?php if ( $el_etapes ) : ?>
<section class="section" id="deroule">
	<div class="wrap split">
		<div class="split-head">
			<h2>Comment ça se passe</h2>
		</div>
		<div class="split-body">
			<ol class="steps">
				<?php foreach ( $el_etapes as $el_e ) : ?>
				<li>
					<h3><?php echo esc_html( $el_e[0] ); ?></h3>
					<p><?php echo esc_html( $el_e[1] ); ?></p>
				</li>
				<?php endforeach; ?>
			</ol>
		</div>
	</div>
</section>
<?php endif; ?>

<?php if ( el_faq() ) : ?>
<section class="section" id="questions">
	<div class="wrap split">
		<div class="split-head">
			<h2>Questions fréquentes</h2>
		</div>
		<div class="split-body">
			<div class="faq">
				<?php foreach ( el_faq() as $el_q ) : ?>
				<details>
					<summary><?php echo esc_html( $el_q['q'] ); ?></summary>
					<p><?php echo nl2br( esc_html( $el_q['a'] ) ); ?></p>
				</details>
				<?php endforeach; ?>
			</div>
		</div>
	</div>
</section>
<?php endif; ?>

<?php
$el_appel = el_appel_html();
el_band(
	'Une date en tête ?',
	'Dites-nous où, quand et pour combien de personnes. On revient vers vous avec un devis.' . ( '' !== $el_appel ? ' Vous pouvez aussi appeler ' . $el_appel . '.' : '' ),
	el_url( '/devis/' ),
	'Demander un devis'
);

get_footer();
