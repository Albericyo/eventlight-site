<?php
/**
 * Le formulaire de devis : réception, enregistrement dans le planning, e-mails.
 *
 * Chaque demande devient un dossier « Demande reçue » (voir planning.php) et part par e-mail
 * à l'adresse choisie dans les réglages. Si l'e-mail ne part pas, la demande reste visible
 * dans l'administration.
 */

defined( 'ABSPATH' ) || exit;

/** Le message d'erreur à afficher au-dessus du formulaire. Avec un argument, le fixe. */
function el_devis_erreur( $message = null ) {
	static $erreur = '';
	if ( null !== $message ) {
		$erreur = (string) $message;
	}
	return $erreur;
}

/** Les valeurs saisies, pour les remettre dans le formulaire après une erreur. Avec un tableau, les fixe. */
function el_devis_valeur( $champ, $valeurs = null ) {
	static $saisie = array();
	if ( is_array( $valeurs ) ) {
		$saisie = $valeurs;
	}
	return isset( $saisie[ $champ ] ) ? (string) $saisie[ $champ ] : '';
}

/** Lit et nettoie ce que le visiteur a envoyé. */
function el_devis_lire() {
	// phpcs:disable WordPress.Security.NonceVerification.Missing -- formulaire public, sans session.
	$texte = function ( $cle, $max ) {
		$v = isset( $_POST[ $cle ] ) && is_string( $_POST[ $cle ] ) ? sanitize_text_field( wp_unslash( $_POST[ $cle ] ) ) : '';
		return function_exists( 'mb_substr' ) ? mb_substr( $v, 0, $max ) : substr( $v, 0, $max );
	};
	$zone  = function ( $cle, $max ) {
		$v = isset( $_POST[ $cle ] ) && is_string( $_POST[ $cle ] ) ? sanitize_textarea_field( wp_unslash( $_POST[ $cle ] ) ) : '';
		return function_exists( 'mb_substr' ) ? mb_substr( $v, 0, $max ) : substr( $v, 0, $max );
	};

	$type = $texte( 'type_evenement', 60 );
	if ( ! in_array( $type, el_types_evenement(), true ) ) {
		$type = 'Autre';
	}
	$date = $texte( 'date_evenement', 10 );

	// La sélection de matériel, envoyée par le script du site : [{ slug, q }].
	$lignes = array();
	$brut   = isset( $_POST['selection'] ) && is_string( $_POST['selection'] ) ? json_decode( wp_unslash( $_POST['selection'] ), true ) : null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	if ( is_array( $brut ) ) {
		foreach ( array_slice( $brut, 0, 60 ) as $l ) {
			$slug    = isset( $l['slug'] ) ? sanitize_title( (string) $l['slug'] ) : '';
			$q       = isset( $l['q'] ) ? (int) $l['q'] : 0;
			$produit = '' !== $slug ? get_page_by_path( $slug, OBJECT, 'el_produit' ) : null;
			if ( $produit && $q > 0 ) {
				$lignes[] = array(
					'produit' => $produit->ID,
					'q'       => min( $q, 99 ),
				);
			}
		}
	}
	$invites = isset( $_POST['invites'] ) ? absint( wp_unslash( $_POST['invites'] ) ) : 0;

	return array(
		'nom'            => $texte( 'nom', 120 ),
		'email'          => isset( $_POST['email'] ) && is_string( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '',
		'telephone'      => $texte( 'telephone', 40 ),
		'type_evenement' => $type,
		'lieu'           => $texte( 'lieu', 160 ),
		'date_evenement' => el_est_date( $date ) ? $date : '',
		'invites'        => $invites ? (string) $invites : '',
		'message'        => $zone( 'message', 5000 ),
		'materiel'       => $zone( 'materiel', 3000 ),
		'lignes'         => $lignes,
	);
	// phpcs:enable
}

/** L'adresse de la page de confirmation. */
function el_devis_merci() {
	$page = get_page_by_path( 'devis/merci' );
	if ( $page && 'publish' === $page->post_status ) {
		return get_permalink( $page );
	}
	return add_query_arg( 'demande', 'envoyee', el_url( '/devis/' ) );
}

/** Reçoit le formulaire de devis. */
function el_devis_recevoir() {
	// phpcs:disable WordPress.Security.NonceVerification.Missing
	if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || 'POST' !== $_SERVER['REQUEST_METHOD'] || ! isset( $_POST['el_action'] ) || 'devis' !== $_POST['el_action'] ) {
		return;
	}
	// Le champ caché n'est rempli que par les robots : on leur répond comme si tout allait bien.
	if ( isset( $_POST['bot-field'] ) && ( ! is_string( $_POST['bot-field'] ) || '' !== trim( $_POST['bot-field'] ) ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		wp_safe_redirect( el_devis_merci(), 303 );
		exit;
	}
	// phpcs:enable

	$v = el_devis_lire();
	el_devis_valeur( '', $v );

	if ( '' === $v['nom'] || '' === trim( $v['message'] ) ) {
		el_devis_erreur( 'Il manque votre nom ou votre demande. Complétez le formulaire, puis renvoyez-le.' );
		return;
	}
	if ( ! is_email( $v['email'] ) ) {
		el_devis_erreur( 'L\'adresse e-mail ne semble pas valable. Corrigez-la, puis renvoyez le formulaire.' );
		return;
	}

	// Pas plus de cinq demandes en dix minutes depuis la même connexion.
	$adresse = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	$cle     = 'el_devis_' . md5( $adresse . wp_salt() );
	$nombre  = (int) get_transient( $cle );
	if ( $nombre >= 5 ) {
		el_devis_erreur( 'Plusieurs demandes viennent de partir depuis votre connexion. Réessayez dans quelques minutes, ou appelez-nous.' );
		return;
	}
	set_transient( $cle, $nombre + 1, 10 * MINUTE_IN_SECONDS );

	$type  = $v['type_evenement'];
	$titre = $v['nom'] . ( 'Autre' !== $type ? ', ' . ( function_exists( 'mb_strtolower' ) ? mb_strtolower( $type ) : strtolower( $type ) ) : '' );
	$id    = el_dossier_enregistrer(
		array(
			'titre'          => $titre,
			'statut'         => 'demande',
			'genre'          => 'Location de matériel' === $type ? 'location' : 'prestation',
			'debut'          => $v['date_evenement'],
			'fin'            => $v['date_evenement'],
			'client'         => $v['nom'],
			'email'          => $v['email'],
			'tel'            => $v['telephone'],
			'lieu'           => $v['lieu'],
			'invites'        => $v['invites'],
			'type_evenement' => $type,
			'message'        => $v['message'],
			'materiel_texte' => $v['materiel'],
			'lignes'         => $v['lignes'],
			'origine'        => 'site',
		)
	);
	if ( ! $id ) {
		el_devis_erreur( 'La demande n\'a pas pu être enregistrée. Réessayez dans un instant, ou appelez-nous.' );
		return;
	}

	$parti = el_devis_notifier( $id );
	update_post_meta( $id, '_el_mail', $parti ? 'ok' : 'echec' );
	if ( el_reglage( 'devis_accuse' ) ) {
		el_devis_accuser( $id );
	}

	wp_safe_redirect( el_devis_merci(), 303 );
	exit;
}
add_action( 'init', 'el_devis_recevoir', 30 );

/** Les adresses qui reçoivent les demandes. */
function el_devis_destinataires() {
	$adresses = array();
	foreach ( preg_split( '/[\s,;]+/', (string) el_reglage( 'devis_email' ), -1, PREG_SPLIT_NO_EMPTY ) as $a ) {
		if ( is_email( $a ) ) {
			$adresses[] = $a;
		}
	}
	if ( ! $adresses && is_email( el_reglage( 'email' ) ) ) {
		$adresses[] = el_reglage( 'email' );
	}
	if ( ! $adresses ) {
		$adresses[] = get_option( 'admin_email' );
	}
	return $adresses;
}

/** Le récapitulatif d'une demande, en texte simple. */
function el_devis_recapitulatif( $d ) {
	$lignes = array(
		'Nom : ' . $d['client'],
		'E-mail : ' . $d['email'],
	);
	if ( '' !== $d['tel'] ) {
		$lignes[] = 'Téléphone : ' . $d['tel'];
	}
	$lignes[] = 'Type d\'événement : ' . $d['type_evenement'];
	if ( '' !== $d['lieu'] ) {
		$lignes[] = 'Lieu : ' . $d['lieu'];
	}
	if ( '' !== $d['debut'] ) {
		$lignes[] = 'Date : ' . el_date_fr( $d['debut'] );
	}
	if ( '' !== $d['invites'] ) {
		$lignes[] = 'Invités : ' . $d['invites'];
	}
	$lignes[] = '';
	$lignes[] = 'Demande :';
	$lignes[] = $d['message'];
	if ( '' !== $d['materiel_texte'] ) {
		$lignes[] = '';
		$lignes[] = 'Matériel sélectionné :';
		$lignes[] = $d['materiel_texte'];
	}
	return implode( "\n", $lignes );
}

/** Prévient par e-mail qu'une demande vient d'arriver. Renvoie vrai si l'e-mail est parti. */
function el_devis_notifier( $id ) {
	$d = el_dossier( $id );
	if ( ! $d ) {
		return false;
	}
	$sujet = 'Demande de devis : ' . $d['type_evenement'] . ', ' . $d['client'] . ( '' !== $d['debut'] ? ', ' . el_periode_fr( $d['debut'], $d['fin'] ) : '' );
	$corps = el_devis_recapitulatif( $d );

	// Ce qui est disponible à la date demandée, pour répondre vite.
	if ( '' !== $d['debut'] && $d['lignes'] ) {
		$dispos = el_disponibilites( $d['debut'], $d['fin'], $d['id'] );
		$etat   = array();
		foreach ( $d['lignes'] as $l ) {
			if ( ! isset( $dispos[ $l['produit'] ] ) ) {
				continue;
			}
			$reste  = max( 0, $dispos[ $l['produit'] ]['dispo'] );
			$etat[] = $l['q'] . ' x ' . get_the_title( $l['produit'] ) . ' : ' . $reste . ' sur ' . $dispos[ $l['produit'] ]['stock'] . ( 1 === $reste ? ' disponible' : ' disponibles' ) . ( $l['q'] > $reste ? ' (il en manque ' . ( $l['q'] - $reste ) . ')' : '' );
		}
		if ( $etat ) {
			$corps .= "\n\nDisponibilité " . el_periode_fr( $d['debut'], $d['fin'] ) . " :\n" . implode( "\n", $etat );
		}
	}
	$corps .= "\n\n--\nDemande envoyée depuis le formulaire de devis de " . home_url( '/' );
	$corps .= "\nLa retrouver dans WordPress : " . admin_url( 'post.php?post=' . $d['id'] . '&action=edit' );

	$entetes = array();
	if ( is_email( $d['email'] ) ) {
		$entetes[] = 'Reply-To: ' . str_replace( array( "\r", "\n", '"', '<', '>' ), '', $d['client'] ) . ' <' . $d['email'] . '>';
	}
	return (bool) wp_mail( el_devis_destinataires(), $sujet, $corps, $entetes );
}

/** Confirme au demandeur que sa demande est bien arrivée. */
function el_devis_accuser( $id ) {
	$d = el_dossier( $id );
	if ( ! $d || ! is_email( $d['email'] ) ) {
		return false;
	}
	$site  = el_site();
	$corps = "Bonjour,\n\nVotre demande de devis est bien arrivée. On vous répond rapidement, par e-mail ou par téléphone.";
	if ( $site['contacts'] ) {
		$corps .= ' Pour une date proche, appelez directement ' . $site['contacts'][0]['nom'] . ' au ' . $site['contacts'][0]['tel'] . '.';
	}
	$corps .= "\n\nCe que vous nous avez envoyé :\n\n" . el_devis_recapitulatif( $d );
	$corps .= "\n\n--\nEvent'Light\n" . $site['address'] . "\n" . home_url( '/' );

	$entetes   = array();
	$reponse_a = el_devis_destinataires();
	if ( $reponse_a ) {
		$entetes[] = 'Reply-To: Event\'Light <' . $reponse_a[0] . '>';
	}
	return (bool) wp_mail( $d['email'], 'Votre demande de devis, Event\'Light', $corps, $entetes );
}

/** Les e-mails du site partent au nom d'Event'Light plutôt que de « WordPress ». */
function el_mail_expediteur( $nom ) {
	return 'WordPress' === $nom ? 'Event\'Light' : $nom;
}
add_filter( 'wp_mail_from_name', 'el_mail_expediteur' );
