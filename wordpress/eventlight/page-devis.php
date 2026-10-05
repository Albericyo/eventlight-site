<?php
/**
 * Template Name: Demande de devis
 *
 * Le formulaire de devis. La sélection de matériel faite dans le catalogue s'y retrouve.
 */

defined( 'ABSPATH' ) || exit;

$el_site   = el_site();
$el_erreur = el_devis_erreur();
// Sans page « merci », la confirmation s'affiche ici.
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$el_envoyee = isset( $_GET['demande'] ) && 'envoyee' === $_GET['demande'];

get_header();

if ( $el_envoyee ) {
	el_merci();
	get_footer();
	return;
}
?>
<div class="wrap">
	<header class="page-head">
		<?php el_fil(); ?>
		<h1><?php the_title(); ?></h1>
		<?php el_lede_page( 'Décrivez votre événement. On revient vers vous avec ce qu\'il faut prévoir et un tarif. Le devis est gratuit, valable 30 jours.' ); ?>
	</header>

	<div class="split">
		<div class="split-head">
			<h2 class="sr">Nous joindre directement</h2>
			<dl class="sheet">
				<?php foreach ( $el_site['contacts'] as $el_c ) : ?>
				<div><dt><?php echo esc_html( $el_c['nom'] ); ?></dt><dd><a href="<?php echo esc_url( $el_c['telHref'] ); ?>"><?php echo esc_html( $el_c['tel'] ); ?></a></dd></div>
				<?php endforeach; ?>
				<div><dt>E-mail</dt><dd><a href="mailto:<?php echo esc_attr( antispambot( $el_site['email'] ) ); ?>"><?php echo esc_html( antispambot( $el_site['email'] ) ); ?></a></dd></div>
			</dl>
			<?php if ( '' !== el_reglage( 'devis_acompte' ) ) : ?>
			<p><?php echo esc_html( el_reglage( 'devis_acompte' ) ); ?></p>
			<?php endif; ?>
		</div>

		<form class="split-body form" name="devis" method="POST" action="<?php echo esc_url( el_url( '/devis/' ) ); ?>" data-devis>
			<input type="hidden" name="el_action" value="devis">
			<p class="hp"><label>Ne pas remplir : <input type="text" name="bot-field" tabindex="-1" autocomplete="off"></label></p>

			<?php if ( '' !== $el_erreur ) : ?>
			<p class="field-full form-alerte" role="alert"><?php echo esc_html( $el_erreur ); ?></p>
			<?php endif; ?>

			<div class="field-full selection" id="selection" data-selection hidden>
				<h2 style="font: 600 1.125rem/1.3 var(--texte)">Votre sélection de matériel</h2>
				<ul></ul>
				<p class="selection-total"><span>Estimation, hors livraison</span><span data-selection-total></span></p>
				<p class="note">Le tarif journalier vaut aussi pour le week-end. <button class="link-btn" type="button" data-selection-vider>Vider la sélection</button></p>
				<textarea name="materiel" id="devis-materiel" hidden></textarea>
				<input type="hidden" name="selection" id="devis-selection" value="">
			</div>

			<div class="field">
				<label for="devis-nom">Nom et prénom</label>
				<input type="text" id="devis-nom" name="nom" required autocomplete="name" value="<?php echo esc_attr( el_devis_valeur( 'nom' ) ); ?>">
			</div>
			<div class="field">
				<label for="devis-email">E-mail</label>
				<input type="email" id="devis-email" name="email" required autocomplete="email" value="<?php echo esc_attr( el_devis_valeur( 'email' ) ); ?>">
			</div>
			<div class="field">
				<label for="devis-tel">Téléphone</label>
				<input type="tel" id="devis-tel" name="telephone" autocomplete="tel" value="<?php echo esc_attr( el_devis_valeur( 'telephone' ) ); ?>">
				<small>Facultatif, mais c'est souvent plus rapide.</small>
			</div>
			<div class="field">
				<label for="devis-type">Type d'événement</label>
				<select id="devis-type" name="type_evenement"<?php echo '' !== el_devis_valeur( 'type_evenement' ) ? ' data-touche="1"' : ''; ?>>
					<?php foreach ( el_types_evenement() as $el_type ) : ?>
					<option value="<?php echo esc_attr( $el_type ); ?>"<?php selected( el_devis_valeur( 'type_evenement' ), $el_type ); ?>><?php echo esc_html( $el_type ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="field">
				<label for="devis-lieu">Lieu</label>
				<input type="text" id="devis-lieu" name="lieu" placeholder="Ville ou salle" autocomplete="address-level2" value="<?php echo esc_attr( el_devis_valeur( 'lieu' ) ); ?>">
			</div>
			<div class="field">
				<label for="devis-date">Date de l'événement</label>
				<input type="date" id="devis-date" name="date_evenement" value="<?php echo esc_attr( el_devis_valeur( 'date_evenement' ) ); ?>">
			</div>
			<div class="field">
				<label for="devis-invites">Nombre d'invités</label>
				<input type="number" id="devis-invites" name="invites" min="1" inputmode="numeric" placeholder="Environ" value="<?php echo esc_attr( el_devis_valeur( 'invites' ) ); ?>">
			</div>
			<div class="field field-full">
				<label for="devis-message">Votre demande</label>
				<textarea id="devis-message" name="message" rows="6" required placeholder="La salle, les horaires, ce que vous avez en tête : son, lumière, effets, mapping…"><?php echo esc_textarea( el_devis_valeur( 'message' ) ); ?></textarea>
			</div>
			<div class="field-full actions">
				<button type="submit" class="btn btn-plein">Envoyer ma demande <?php echo el_signe(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
			</div>
			<p class="field-full note">Vos coordonnées servent uniquement à vous répondre. Elles ne sont ni vendues ni cédées. <a href="<?php echo esc_url( el_url( '/mentions-legales/#donnees' ) ); ?>">En savoir plus</a></p>
		</form>
	</div>
</div>
<?php
get_footer();
