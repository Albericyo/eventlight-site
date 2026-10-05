<?php
/**
 * Haut de page : en-tête du document, logo, navigation, bouton de devis.
 */

defined( 'ABSPATH' ) || exit;

$el_racine = untrailingslashit( (string) wp_parse_url( home_url(), PHP_URL_PATH ) );
?>
<!DOCTYPE html>
<html lang="fr"<?php echo '' !== $el_racine ? ' data-racine="' . esc_attr( $el_racine ) . '"' : ''; ?>>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?> data-page="<?php echo esc_attr( el_chemin_courant() ); ?>">
<?php wp_body_open(); ?>
<a class="skip" href="#contenu">Aller au contenu</a>

<header class="top">
	<div class="wrap top-in">
		<a class="brand" href="<?php echo esc_url( el_url( '/' ) ); ?>" aria-label="Event'Light, retour à l'accueil"><?php echo el_logo( 'ligne-fort' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>

		<nav class="nav" id="nav" aria-label="Navigation principale">
			<ul>
				<?php foreach ( el_navigation() as $el_entree ) : ?>
				<li><a href="<?php echo esc_url( $el_entree['url'] ); ?>"<?php echo '' !== $el_entree['courant'] ? ' aria-current="' . esc_attr( $el_entree['courant'] ) . '"' : ''; ?>><?php echo esc_html( $el_entree['titre'] ); ?></a></li>
				<?php endforeach; ?>
			</ul>
		</nav>

		<div class="top-actions">
			<button class="switch" type="button" data-salle aria-pressed="false">Éteindre la salle</button>
			<a class="btn btn-s sel-link" href="<?php echo esc_url( el_url( '/devis/#selection' ) ); ?>" data-selection-link hidden><span class="long">Ma sélection</span><span class="short" aria-hidden="true">Sél.</span> (<span data-selection-count>0</span>)</a>
			<a class="btn btn-plein btn-s btn-devis" href="<?php echo esc_url( el_url( '/devis/' ) ); ?>"><span class="long">Demander un devis</span><span class="short" aria-hidden="true">Devis</span></a>
			<button class="btn btn-s burger" type="button" aria-expanded="false" aria-controls="nav" data-burger>Menu</button>
		</div>
	</div>
</header>

<main id="contenu">
