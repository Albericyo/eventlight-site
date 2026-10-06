<?php
/**
 * Composants réutilisés d'une page à l'autre : logo, fiche du catalogue, réalisation,
 * rangée de formule, bandeau d'appel, fil d'Ariane.
 */

defined( 'ABSPATH' ) || exit;

/** Un dessin du logo, à inclure tel quel dans la page : « ligne-fort », « logo-courant », « signe-trait »… */
function el_logo( $nom ) {
	static $cache = array();
	$nom = preg_replace( '/[^a-z-]/', '', (string) $nom );
	if ( ! isset( $cache[ $nom ] ) ) {
		$fichier       = get_theme_file_path( 'logo/' . $nom . '.svg' );
		$cache[ $nom ] = is_readable( $fichier ) ? trim( (string) file_get_contents( $fichier ) ) : '';
	}
	return $cache[ $nom ];
}

/** Le signe au trait : puce des boutons, interrupteur, écran vide. */
function el_signe() {
	return el_logo( 'signe-trait' );
}

/** Le visuel d'un produit sans photo : son dessin s'il en a un, sinon celui de sa catégorie. */
function el_picto_produit( $produit, $titre = '' ) {
	return el_picto( el_a_picto( $produit['slug'] ) ? $produit['slug'] : $produit['categoriePicto'], $titre );
}

/**
 * Une référence du catalogue, en vitrine.
 *
 * @param array $p      Voir el_produit().
 * @param int   $niveau Rang du titre : 3 par défaut, 2 quand la liste suit directement le h1.
 */
function el_item( $p, $niveau = 3 ) {
	$couverture = el_couverture_produit( $p['id'] );
	$prix       = el_prix_detail( $p['prix'] );
	$niveau     = (int) $niveau;
	?>
<li class="item">
	<a class="vitrine" href="<?php echo esc_url( $p['url'] ); ?>" tabindex="-1" aria-hidden="true">
		<?php
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $couverture ? el_img( $couverture, '', '(min-width: 60em) 18rem, (min-width: 34em) 45vw, 100vw' ) : el_picto_produit( $p );
		?>
		<span class="tag"><?php echo esc_html( $prix['montant'] ); ?>&nbsp;<?php echo esc_html( $prix['unite'] ); ?><?php echo '' !== $prix['note'] ? ', ' . esc_html( $prix['note'] ) : ''; ?></span>
	</a>
	<div>
		<h<?php echo $niveau; ?> class="item-nom"><a href="<?php echo esc_url( $p['url'] ); ?>"><?php echo esc_html( $p['nom'] ); ?></a></h<?php echo $niveau; ?>>
		<p class="clamp-3"><?php echo esc_html( $p['description'] ); ?></p>
	</div>
	<div class="item-foot">
		<button class="btn btn-s add" type="button" data-add="<?php echo esc_attr( $p['slug'] ); ?>" data-nom="<?php echo esc_attr( $p['nom'] ); ?>" data-prix="<?php echo esc_attr( $p['prix'] ); ?>"<?php echo el_attr_stock( $p['id'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> aria-label="Ajouter <?php echo esc_attr( $p['nom'] ); ?> à ma sélection">Ajouter</button>
	</div>
</li>
	<?php
}

/**
 * Une réalisation, sur son écran.
 *
 * @param array $projet Voir el_realisation().
 * @param bool  $large  Affichage en grand (première réalisation de l'accueil).
 */
function el_work( $projet, $large = false ) {
	$couverture = el_couverture_realisation( $projet['id'] );
	$tailles    = '(min-width: 62em) ' . ( $large ? '56vw' : '28vw' ) . ', (min-width: 36em) 50vw, 100vw';
	?>
<a class="work<?php echo $large ? ' work-wide' : ''; ?>" href="<?php echo esc_url( $projet['url'] ); ?>" data-type="<?php echo esc_attr( $projet['type'] ); ?>">
	<span class="screen<?php echo $couverture ? '' : ' screen-empty'; ?>">
		<?php
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $couverture ? el_img( $couverture, '', $tailles ) : el_signe();
		?>
		<span class="tag"><?php echo esc_html( $projet['type'] ); ?></span>
	</span>
	<span class="work-cap"><b><?php echo esc_html( el_titre_court( $projet['titre'] ) ); ?></b><span><?php echo esc_html( $projet['annee'] ); ?></span></span>
</a>
	<?php
}

/**
 * Bandeau d'appel en bas de page.
 *
 * @param string $titre      Titre.
 * @param string $texte_html Texte, déjà échappé : il peut contenir un lien.
 * @param string $lien       Adresse du bouton.
 * @param string $libelle    Libellé du bouton.
 */
function el_band( $titre, $texte_html, $lien, $libelle ) {
	?>
<section class="section">
	<div class="wrap">
		<div class="band">
			<div>
				<h2><?php echo esc_html( $titre ); ?></h2>
				<p><?php echo wp_kses_post( $texte_html ); ?></p>
			</div>
			<a class="btn btn-plein" href="<?php echo esc_url( $lien ); ?>"><?php echo esc_html( $libelle ); ?> <?php echo el_signe(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
		</div>
	</div>
</section>
	<?php
}

/** Une rangée de formule : nom, accroche, schéma du pack recommandé, premier prix. */
function el_row( $f ) {
	$recommande = el_pack_recommande( $f );
	?>
<li>
	<a class="row" href="<?php echo esc_url( $f['url'] ); ?>">
		<span class="row-name"><?php echo esc_html( $f['titre'] ); ?></span>
		<span class="row-text"><?php echo esc_html( $f['accroche'] ); ?></span>
		<span class="row-plan"><?php echo el_plan_de_feu( $recommande ? el_couches( $recommande['items'] ) : array() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
		<?php if ( $recommande ) : ?>
		<span class="price"><small>à partir de</small><b><?php echo esc_html( $f['packs'][0]['prix'] ); ?></b></span>
		<?php endif; ?>
	</a>
</li>
	<?php
}

/** Une catégorie du catalogue : son dessin, son nom, le nombre de références et le premier prix. */
function el_cat( $c ) {
	$liste  = el_produits_de_categorie( $c['id'] );
	$nombre = count( $liste );
	?>
<li>
	<a class="cat" href="<?php echo esc_url( $c['url'] ); ?>">
		<span class="vitrine"><?php echo el_picto( $c['picto'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
		<b><?php echo esc_html( $c['nom'] ); ?></b>
		<span><?php echo (int) $nombre; ?> <?php echo 1 === $nombre ? 'référence' : 'références'; ?><?php echo $nombre ? ', dès ' . esc_html( el_prix_min( $liste ) ) . '&nbsp;€ par jour' : ''; ?></span>
	</a>
</li>
	<?php
}

/** Le fil d'Ariane de la page en cours. */
function el_fil() {
	$fil = el_contexte()['fil'];
	if ( ! $fil ) {
		return;
	}
	$dernier = count( $fil ) - 1;
	?>
<nav aria-label="Fil d'Ariane">
	<ol class="crumbs">
		<li><a href="<?php echo esc_url( el_url( '/' ) ); ?>">Accueil</a></li>
		<?php foreach ( $fil as $i => $etape ) : ?>
			<?php if ( '' !== $etape['url'] && $i !== $dernier ) : ?>
		<li><a href="<?php echo esc_url( $etape['url'] ); ?>"><?php echo esc_html( $etape['nom'] ); ?></a></li>
			<?php else : ?>
		<li aria-current="page"><?php echo esc_html( $etape['nom'] ); ?></li>
			<?php endif; ?>
		<?php endforeach; ?>
	</ol>
</nav>
	<?php
}

/**
 * Lecteur vidéo : une image, un bouton. La vidéo YouTube ne se charge qu'au clic.
 *
 * @param string     $youtube     Identifiant de la vidéo.
 * @param string     $titre       Titre donné au lecteur.
 * @param array|null $affiche     Image d'attente (voir el_media()).
 * @param string     $libelle     Libellé du bouton.
 * @param bool       $prioritaire Vrai en tête de page : l'image est chargée tout de suite et montrée
 *                                selon son fond. Faux plus bas : chargement différé, image plein cadre.
 */
function el_video( $youtube, $titre, $affiche, $libelle, $prioritaire = false ) {
	?>
<div class="video" data-video="<?php echo esc_attr( $youtube ); ?>" data-titre="<?php echo esc_attr( $titre ); ?>">
	<?php if ( $affiche && $prioritaire ) : ?>
	<img src="<?php echo esc_url( el_media_grande( $affiche ) ); ?>" width="<?php echo (int) $affiche['w']; ?>" height="<?php echo (int) $affiche['h']; ?>" alt="" decoding="async" fetchpriority="high" data-fond="<?php echo esc_attr( $affiche['fond'] ); ?>">
	<?php elseif ( $affiche ) : ?>
	<img src="<?php echo esc_url( el_media_grande( $affiche ) ); ?>" width="<?php echo (int) $affiche['w']; ?>" height="<?php echo (int) $affiche['h']; ?>" alt="" loading="lazy" decoding="async">
	<?php else : ?>
		<?php echo el_signe(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	<?php endif; ?>
	<a class="video-play" href="<?php echo esc_url( 'https://www.youtube.com/watch?v=' . $youtube ); ?>" rel="noopener"><?php echo esc_html( $libelle ); ?></a>
</div>
	<?php
}

/** Le nom de la première personne à joindre, avec son numéro en lien : pour les phrases « appelez… ». */
function el_appel_html() {
	$site = el_site();
	if ( empty( $site['contacts'] ) ) {
		return '';
	}
	$c = $site['contacts'][0];
	return esc_html( $c['nom'] ) . ' au <a href="' . esc_url( $c['telHref'] ) . '">' . esc_html( $c['tel'] ) . '</a>';
}

/**
 * L'introduction d'une page, écrite dans l'éditeur de WordPress : un paragraphe par élément.
 *
 * @param string $defaut Texte utilisé quand la page est vide.
 * @return string[]
 */
function el_intro_page( $defaut = '' ) {
	$post       = get_queried_object();
	$paragraphes = array();
	if ( $post instanceof WP_Post && '' !== trim( $post->post_content ) ) {
		$html = apply_filters( 'the_content', $post->post_content ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		foreach ( preg_split( '~</p>|<br\s*/?>\s*<br\s*/?>~i', $html ) as $morceau ) {
			$texte = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( $morceau ) ) );
			if ( '' !== $texte ) {
				$paragraphes[] = $texte;
			}
		}
	}
	if ( ! $paragraphes && '' !== $defaut ) {
		$paragraphes[] = $defaut;
	}
	return $paragraphes;
}

/** Affiche l'introduction d'une page. */
function el_lede_page( $defaut = '' ) {
	foreach ( el_intro_page( $defaut ) as $texte ) {
		echo '<p class="lede">' . esc_html( $texte ) . "</p>\n";
	}
}

/** La confirmation affichée une fois la demande de devis partie. */
function el_merci() {
	$appel = el_appel_html();
	$post  = get_queried_object();
	$titre = ( $post instanceof WP_Post && 'merci' === $post->post_name ) ? get_the_title( $post ) : 'Demande envoyée';
	?>
<div class="wrap" data-demande-envoyee>
	<header class="page-head">
		<h1><?php echo esc_html( $titre ); ?></h1>
		<p class="lede">Merci. On vous répond rapidement, par e-mail ou par téléphone.<?php echo '' !== $appel ? ' Pour une date proche, appelez directement ' . $appel . '.' : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
		<div class="actions">
			<a class="btn" href="<?php echo esc_url( el_url( '/portfolio/' ) ); ?>">Voir les réalisations</a>
			<a class="btn" href="<?php echo esc_url( el_url( '/' ) ); ?>">Retour à l'accueil</a>
		</div>
	</header>
</div>
	<?php
}
