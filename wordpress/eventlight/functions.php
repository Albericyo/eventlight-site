<?php
/**
 * Event'Light, le thème.
 *
 * Chaque fichier de inc/ s'occupe d'une chose :
 *   textes        typographie française, lecture des prix
 *   plan-de-feu   schéma d'une installation, dessiné d'après les lignes d'un pack
 *   pictos        pictogrammes du catalogue
 *   reglages      coordonnées et textes des pages (valeurs par défaut comprises)
 *   types         formules, produits, réalisations, questions : types de contenu et adresses
 *   donnees       lecture du contenu pour les gabarits
 *   medias        images adaptatives, type de fond
 *   composants    morceaux de page réutilisés
 *   entete        balises de l'en-tête, styles, script, navigation
 *   pieces        pièces de structure et recettes des produits
 *   planning      demandes de devis, réservations, stock et disponibilités
 *   devis         réception du formulaire de devis, e-mails
 *   redirections  anciennes adresses du site
 *   admin/        écrans de l'administration
 */

defined( 'ABSPATH' ) || exit;

$el_fichiers = array( 'textes', 'plan-de-feu', 'pictos', 'reglages', 'types', 'donnees', 'medias', 'composants', 'entete', 'pieces', 'planning', 'devis', 'redirections' );
foreach ( $el_fichiers as $el_fichier ) {
	require_once get_theme_file_path( 'inc/' . $el_fichier . '.php' );
}

if ( is_admin() ) {
	foreach ( array( 'champs', 'contenus', 'reglages', 'planning', 'pieces', 'dupliquer', 'import' ) as $el_fichier ) {
		require_once get_theme_file_path( 'inc/admin/' . $el_fichier . '.php' );
	}
}
unset( $el_fichiers, $el_fichier );
