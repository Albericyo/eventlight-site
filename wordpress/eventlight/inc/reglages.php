<?php
/**
 * Réglages du site : coordonnées, textes des pages, image du héros.
 *
 * Tout est enregistré dans une seule option, « el_reglages ». Les valeurs par défaut ci-dessous
 * sont celles du site d'origine : le thème s'affiche correctement avant le premier réglage.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Le schéma des réglages : onglets, champs, valeurs par défaut.
 * Types de champ : texte, zone, lignes (une par ligne), paires (« Titre | Texte » par ligne),
 * email, url, case (à cocher), realisation, realisations, scenes.
 */
function el_reglages_schema() {
	return array(
		'coordonnees'  => array(
			'titre'  => 'Coordonnées',
			'champs' => array(
				'email'       => array(
					'type'   => 'email',
					'label'  => 'E-mail affiché sur le site',
					'defaut' => 'eventlight80@gmail.com',
				),
				'contacts'    => array(
					'type'   => 'paires',
					'label'  => 'Personnes à joindre',
					'aide'   => 'Une personne par ligne : Nom | Téléphone. La première est celle que le site propose d\'appeler.',
					'defaut' => "Albéric Delabie | 07 81 53 36 00\nAntoine Capel | 06 46 82 08 31",
				),
				'rue'         => array(
					'type'   => 'texte',
					'label'  => 'Adresse',
					'defaut' => '53 Rue du Général Friant',
				),
				'code_postal' => array(
					'type'   => 'texte',
					'label'  => 'Code postal',
					'defaut' => '80000',
				),
				'ville'       => array(
					'type'   => 'texte',
					'label'  => 'Ville',
					'defaut' => 'Amiens',
				),
				'departement' => array(
					'type'   => 'texte',
					'label'  => 'Département',
					'defaut' => 'Somme',
				),
				'latitude'    => array(
					'type'   => 'texte',
					'label'  => 'Latitude',
					'aide'   => 'Sert au lien « Voir sur la carte » et aux moteurs de recherche.',
					'defaut' => '49.887753',
				),
				'longitude'   => array(
					'type'   => 'texte',
					'label'  => 'Longitude',
					'defaut' => '2.283393',
				),
				'siret'       => array(
					'type'   => 'texte',
					'label'  => 'SIRET',
					'defaut' => '899 373 930 00028',
				),
				'tva'         => array(
					'type'   => 'texte',
					'label'  => 'Mention de TVA',
					'defaut' => 'TVA non applicable, art. 293 B du CGI',
				),
				'livraison'   => array(
					'type'   => 'texte',
					'label'  => 'Phrase sur la livraison',
					'defaut' => 'Livraison possible à partir de 25 € (secteur Amiens)',
				),
				'secteur'     => array(
					'type'   => 'zone',
					'label'  => 'Secteur d\'intervention',
					'defaut' => 'Amiens et la Somme pour les prestations et la location. Le vidéo mapping nous a déjà menés à Lyon, Lille et Pignan.',
				),
				'slogan'      => array(
					'type'   => 'texte',
					'label'  => 'Phrase sous le logo, en pied de page',
					'defaut' => 'Créateurs de rêves.',
				),
				'facebook'    => array(
					'type'   => 'url',
					'label'  => 'Facebook',
					'defaut' => 'https://www.facebook.com/EventLight80/',
				),
				'instagram'   => array(
					'type'   => 'url',
					'label'  => 'Instagram',
					'defaut' => 'https://www.instagram.com/eventlight80/',
				),
				'youtube'     => array(
					'type'   => 'url',
					'label'  => 'YouTube',
					'defaut' => 'https://www.youtube.com/c/EventLight',
				),
			),
		),
		'accueil'      => array(
			'titre'  => 'Accueil',
			'champs' => array(
				'accueil_titre'             => array(
					'type'   => 'texte',
					'label'  => 'Grand titre',
					'defaut' => 'Son, lumière et vidéo mapping à Amiens',
				),
				'accueil_texte'             => array(
					'type'   => 'zone',
					'label'  => 'Texte sous le titre',
					'defaut' => 'Mariage, anniversaire, soirée d\'entreprise, remise de prix : on monte le son, la lumière et les effets, on reste pendant la soirée, puis on démonte. Le matériel se loue aussi à la journée.',
				),
				'scenes'                    => array(
					'type'   => 'scenes',
					'label'  => 'Images projetées dans le faisceau',
					'aide'   => 'Quatre images au plus. Le cadrage indique le point de l\'image à garder visible : « 50% 50% » pour le centre, « 0% 0% » pour le coin en haut à gauche.',
					'defaut' => array(),
				),
				'accueil_realisations'      => array(
					'type'   => 'realisations',
					'label'  => 'Réalisations mises en avant',
					'aide'   => 'La première est affichée en grand.',
					'nombre' => 5,
					'defaut' => array(),
				),
				'accueil_realisations_texte' => array(
					'type'   => 'zone',
					'label'  => 'Texte de la section Réalisations',
					'defaut' => 'Le vidéo mapping est notre spécialité : la mairie de Pignan pour ses 1000 ans, la cathédrale Saint-Jean à Lyon, le Palais des Beaux-Arts de Lille. Et tout au long de l\'année, des mariages, des galas, des soirées.',
				),
				'etapes'                    => array(
					'type'   => 'paires',
					'label'  => 'Comment ça se passe',
					'aide'   => 'Une étape par ligne : Titre | Texte.',
					'defaut' => "Vous décrivez l'événement | Par le formulaire, par téléphone ou par e-mail : la date, le lieu, le nombre d'invités.\nOn vous envoie un devis | Chiffré pour votre lieu et votre soirée, valable 30 jours.\nVous réservez la date | Le devis signé et un acompte de 30 à 50 % bloquent la date.\nOn s'occupe du reste | Montage, prestation, démontage. Le solde se règle le jour même.",
				),
				'accueil_seo_titre'         => array(
					'type'   => 'texte',
					'label'  => 'Titre pour Google',
					'defaut' => 'Location sono, lumière et vidéo mapping à Amiens | Event\'Light',
				),
				'accueil_seo_description'   => array(
					'type'   => 'zone',
					'label'  => 'Description pour Google',
					'defaut' => 'Event\'Light : location et installation sono, lumière, effets et structures à Amiens. Mariages, anniversaires, CE, sport. Spécialiste du vidéo mapping dans la Somme.',
				),
			),
		),
		'formules'     => array(
			'titre'  => 'Formules',
			'champs' => array(
				'formules_titre'           => array(
					'type'   => 'texte',
					'label'  => 'Titre de la page',
					'defaut' => 'Formules sono et lumière',
				),
				'formules_texte'           => array(
					'type'   => 'zone',
					'label'  => 'Introduction',
					'defaut' => 'Quatre formules, chacune déclinée en packs. On monte, on assure la prestation, on démonte : c\'est compris dans tous les prix.',
				),
				'note_prix'                => array(
					'type'   => 'zone',
					'label'  => 'Note sur les prix',
					'defaut' => 'Tarifs « à partir de » pour une prestation type. Le devis final dépend du lieu, de la jauge et de la durée.',
				),
				'inclus'                   => array(
					'type'   => 'lignes',
					'label'  => 'Compris dans tous les packs',
					'aide'   => 'Une ligne par élément.',
					'defaut' => "Montage / prestation / démontage\nCalage technique sur place",
				),
				'options'                  => array(
					'type'   => 'lignes',
					'label'  => 'En option, au devis',
					'aide'   => 'Une ligne par élément.',
					'defaut' => "Vidéo mapping (sur devis)\nLivraison hors secteur Amiens\nMatériel de location à la carte\nExtensions de durée ou multi-sites",
				),
				'personnalisation'         => array(
					'type'   => 'zone',
					'label'  => 'Phrase sur la personnalisation',
					'defaut' => 'Chaque événement se personnalise : mapping, effets supplémentaires, animateur, cérémonie. Dites-nous ce qu\'il vous faut dans la demande de devis.',
				),
				'formules_seo_titre'       => array(
					'type'   => 'texte',
					'label'  => 'Titre pour Google',
					'defaut' => 'Formules sono et lumière à Amiens | Event\'Light',
				),
				'formules_seo_description' => array(
					'type'   => 'zone',
					'label'  => 'Description pour Google',
					'defaut' => 'Packs mariage, anniversaire, CE et sport à Amiens : sono, lumière, étincelles froides, animateur. Montage et démontage inclus, devis personnalisé.',
				),
			),
		),
		'location'     => array(
			'titre'  => 'Location',
			'champs' => array(
				'location_titre'           => array(
					'type'   => 'texte',
					'label'  => 'Titre de la page',
					'defaut' => 'Location de matériel à Amiens',
				),
				'location_texte'           => array(
					'type'   => 'zone',
					'label'  => 'Introduction',
					'defaut' => 'Son, lumière, effets et structures, à la journée. Le tarif journalier vaut aussi pour le week-end.',
				),
				'location_seo_titre'       => array(
					'type'   => 'texte',
					'label'  => 'Titre pour Google',
					'defaut' => 'Location matériel son, lumière et structures à Amiens | Event\'Light',
				),
				'location_seo_description' => array(
					'type'   => 'zone',
					'label'  => 'Description pour Google',
					'defaut' => 'Catalogue de location à Amiens : lyres, PAR LED, sono FBT, étincelles froides, structures. Livraison dès 25 €. Tarifs journaliers valables aussi le week-end.',
				),
			),
		),
		'realisations' => array(
			'titre'  => 'Réalisations',
			'champs' => array(
				'realisations_titre'           => array(
					'type'   => 'texte',
					'label'  => 'Titre de la page',
					'defaut' => 'Réalisations',
				),
				'realisations_texte'           => array(
					'type'   => 'zone',
					'label'  => 'Introduction',
					'defaut' => 'Des mappings sur façade, des mariages, des galas et des soirées, conçus et réalisés par Event\'Light.',
				),
				'realisations_seo_titre'       => array(
					'type'   => 'texte',
					'label'  => 'Titre pour Google',
					'defaut' => 'Portfolio vidéo mapping et événements | Event\'Light Amiens',
				),
				'realisations_seo_description' => array(
					'type'   => 'zone',
					'label'  => 'Description pour Google',
					'defaut' => 'Réalisations Event\'Light : mappings (Pignan, cathédrale Saint-Jean, Palais des Beaux-Arts), mariages, galas et soirées à Amiens, Lyon et Lille.',
				),
			),
		),
		'mapping'      => array(
			'titre'  => 'Vidéo mapping',
			'champs' => array(
				'mapping_vedette'       => array(
					'type'   => 'realisation',
					'label'  => 'Réalisation montrée en tête de page',
					'aide'   => 'Sa vidéo est proposée en grand. Sans vidéo, rien ne s\'affiche à cet endroit.',
					'defaut' => 0,
				),
				'mapping_legende'       => array(
					'type'   => 'texte',
					'label'  => 'Légende de cette vidéo',
					'defaut' => 'Pignan, 1000 ans : projection sur la mairie, ancien château de Turenne, 2025.',
				),
				'mapping_methode_texte' => array(
					'type'   => 'zone',
					'label'  => 'Texte de la section « Comment on s\'y prend »',
					'defaut' => 'Le mapping ne fait partie d\'aucun pack : chaque façade demande son propre travail, chiffré au devis.',
				),
				'mapping_etapes'        => array(
					'type'   => 'paires',
					'label'  => 'Les étapes',
					'aide'   => 'Une étape par ligne : Titre | Texte.',
					'defaut' => "Relever la façade | Des photos sur place, puis la photogrammétrie : le bâtiment devient un modèle 3D fidèle.\nTracer le gabarit | Du modèle, on tire les matrices 2D et 3D. C'est le calque sur lequel toutes les images sont dessinées.\nCréer les images | Modélisation et animation, montage, effets, bande son. Le scénario s'écrit avec vous.\nProjeter | Calage des vidéoprojecteurs sur place, à plusieurs si la façade le demande.",
				),
				'mapping_fiche'         => array(
					'type'   => 'paires',
					'label'  => 'Les outils',
					'aide'   => 'Une ligne par rubrique : Rubrique | Détail.',
					'defaut' => "Relevé | Meshroom (photogrammétrie)\nCréation | Blender, After Effects, Premiere Pro, Photoshop\nRendu | Ferme de rendu Sheepit pour les exports Blender\nDiffusion | Resolume Arena, mono ou multi-projection\nTarif | Sur devis, selon la façade et la durée",
				),
				'mapping_deja_texte'    => array(
					'type'   => 'zone',
					'label'  => 'Texte de la section « Déjà projeté »',
					'defaut' => 'Des commandes, un concours à Lille, un défi en 48 heures à Lyon, et des projets menés pour le plaisir de s\'exercer.',
				),
			),
		),
		'devis'        => array(
			'titre'  => 'Devis et mentions',
			'champs' => array(
				'devis_email'   => array(
					'type'   => 'texte',
					'label'  => 'Adresse qui reçoit les demandes de devis',
					'aide'   => 'Plusieurs adresses possibles, séparées par des virgules. Vide : l\'e-mail affiché sur le site.',
					'defaut' => '',
				),
				'devis_accuse'  => array(
					'type'    => 'case',
					'label'   => 'Accusé de réception',
					'libelle' => 'Envoyer aussi un e-mail de confirmation à la personne qui fait la demande',
					'defaut'  => 0,
				),
				'devis_acompte' => array(
					'type'   => 'zone',
					'label'  => 'Phrase sur l\'acompte, à côté du formulaire',
					'defaut' => 'Un acompte de 30 à 50 % confirme la réservation. Le solde se règle le jour de la prestation.',
				),
				'directeur'     => array(
					'type'   => 'texte',
					'label'  => 'Directeur de la publication',
					'aide'   => 'Affiché dans les mentions légales. Vide : la ligne n\'apparaît pas.',
					'defaut' => '',
				),
				'hebergeur'     => array(
					'type'   => 'zone',
					'label'  => 'Hébergeur du site',
					'aide'   => 'Nom, adresse et site de l\'hébergeur, une information par ligne. Obligatoire dans les mentions légales. Vide : la ligne n\'apparaît pas.',
					'defaut' => '',
				),
			),
		),
	);
}

/** Les valeurs par défaut, à plat. */
function el_reglages_defaut() {
	static $defaut = null;
	if ( null === $defaut ) {
		$defaut = array();
		foreach ( el_reglages_schema() as $onglet ) {
			foreach ( $onglet['champs'] as $cle => $champ ) {
				$defaut[ $cle ] = $champ['defaut'];
			}
		}
	}
	return $defaut;
}

/** Tous les réglages : ce qui est enregistré, complété par les valeurs par défaut. */
function el_reglages() {
	$enregistres = get_option( 'el_reglages', array() );
	if ( ! is_array( $enregistres ) ) {
		$enregistres = array();
	}
	return array_merge( el_reglages_defaut(), $enregistres );
}

/** Un réglage. */
function el_reglage( $cle ) {
	$reglages = el_reglages();
	return isset( $reglages[ $cle ] ) ? $reglages[ $cle ] : '';
}

/** Les coordonnées, mises en forme pour les gabarits. */
function el_site() {
	static $site = null;
	if ( null !== $site ) {
		return $site;
	}
	$r        = el_reglages();
	$contacts = array();
	foreach ( el_paires( $r['contacts'] ) as $paire ) {
		$iso        = el_tel_iso( $paire[1] );
		$contacts[] = array(
			'nom'     => $paire[0],
			'tel'     => $paire[1],
			'telHref' => 'tel:' . $iso,
			'iso'     => $iso,
		);
	}
	$site = array(
		'name'       => 'Event\'Light',
		'url'        => untrailingslashit( home_url() ),
		'tagline'    => $r['slogan'],
		'email'      => $r['email'],
		'siret'      => $r['siret'],
		'siretRaw'   => preg_replace( '/\D/', '', (string) $r['siret'] ),
		'street'     => $r['rue'],
		'postalCode' => $r['code_postal'],
		'city'       => $r['ville'],
		'department' => $r['departement'],
		'address'    => trim( $r['rue'] . ', ' . $r['code_postal'] . ' ' . $r['ville'], ', ' ),
		'latitude'   => $r['latitude'],
		'longitude'  => $r['longitude'],
		'tva'        => $r['tva'],
		'livraison'  => $r['livraison'],
		'secteur'    => $r['secteur'],
		'facebook'   => $r['facebook'],
		'instagram'  => $r['instagram'],
		'youtube'    => $r['youtube'],
		'contacts'   => $contacts,
	);
	return $site;
}

/** Les types d'événement proposés dans le formulaire de devis. */
function el_types_evenement() {
	return array( 'Mariage', 'Anniversaire', 'Comité d\'entreprise', 'Manifestation sportive', 'Location de matériel', 'Vidéo mapping', 'Autre' );
}

/** Une adresse du site, à partir de son chemin : el_url( '/devis/' ). */
function el_url( $chemin = '/' ) {
	return home_url( $chemin );
}
