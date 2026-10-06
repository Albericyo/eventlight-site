<?php
/**
 * En-tête des pages : titre, description, balises de partage, données structurées,
 * feuille de style et script, nettoyage de ce que WordPress ajoute par défaut.
 */

defined( 'ABSPATH' ) || exit;

function el_supports() {
	add_theme_support( 'title-tag' );
	add_theme_support( 'html5', array( 'script', 'style', 'search-form', 'gallery', 'caption' ) );
	register_nav_menus( array( 'principal' => 'Navigation principale' ) );
}
add_action( 'after_setup_theme', 'el_supports' );

/** Vrai si une extension de référencement s'occupe déjà des balises de la page. */
function el_seo_externe() {
	return defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) || defined( 'SEOPRESS_VERSION' ) || defined( 'AIOSEO_VERSION' ) || defined( 'THE_SEO_FRAMEWORK_VERSION' );
}

/** Nom court d'une page du thème dans le fil d'Ariane. */
function el_noms_courts() {
	return array(
		'devis'            => 'Devis',
		'contact'          => 'Contact',
		'video-mapping'    => 'Vidéo mapping',
		'mentions-legales' => 'Mentions légales / CGV',
	);
}

/**
 * Ce que l'on sait de la page en cours : titre et description pour les moteurs de recherche,
 * fil d'Ariane, adresse de référence, produit ou formule affichés.
 */
function el_contexte() {
	static $c = null;
	if ( null !== $c ) {
		return $c;
	}
	$c = array(
		'titre'       => '',
		'description' => '',
		'canonique'   => '',
		'fil'         => array(),
		'noindex'     => false,
		'produit'     => null,
		'formule'     => null,
		'accueil'     => false,
	);
	$etape = function ( $nom, $url = '' ) {
		return array(
			'nom' => $nom,
			'url' => $url,
		);
	};

	if ( is_404() ) {
		$c['titre']       = 'Page introuvable | Event\'Light Amiens';
		$c['description'] = 'Cette page n\'existe pas. Retrouvez la location sono et lumière Event\'Light à Amiens.';
		$c['noindex']     = true;
	} elseif ( is_front_page() ) {
		$c['accueil']     = true;
		$c['titre']       = el_reglage( 'accueil_seo_titre' );
		$c['description'] = el_reglage( 'accueil_seo_description' );
		$c['canonique']   = home_url( '/' );
	} elseif ( is_post_type_archive( 'el_formule' ) ) {
		$c['titre']       = el_reglage( 'formules_seo_titre' );
		$c['description'] = el_reglage( 'formules_seo_description' );
		$c['canonique']   = get_post_type_archive_link( 'el_formule' );
		$c['fil']         = array( $etape( 'Nos formules' ) );
	} elseif ( is_post_type_archive( 'el_produit' ) ) {
		$c['titre']       = el_reglage( 'location_seo_titre' );
		$c['description'] = el_reglage( 'location_seo_description' );
		$c['canonique']   = get_post_type_archive_link( 'el_produit' );
		$c['fil']         = array( $etape( 'Location' ) );
	} elseif ( is_post_type_archive( 'el_realisation' ) ) {
		$c['titre']       = el_reglage( 'realisations_seo_titre' );
		$c['description'] = el_reglage( 'realisations_seo_description' );
		$c['canonique']   = get_post_type_archive_link( 'el_realisation' );
		$c['fil']         = array( $etape( 'Réalisations' ) );
	} elseif ( is_tax( 'el_categorie' ) ) {
		$terme = get_queried_object();
		$cat   = $terme ? el_categorie_par( 'id', $terme->term_id ) : null;
		if ( $cat ) {
			$c['titre']       = '' !== $cat['seoTitle'] ? $cat['seoTitle'] : 'Location ' . $cat['nom'] . ' à Amiens | Event\'Light';
			$c['description'] = '' !== $cat['seoDescription'] ? $cat['seoDescription'] : $cat['intro'];
			$c['canonique']   = $cat['url'];
			$c['fil']         = array( $etape( 'Location', get_post_type_archive_link( 'el_produit' ) ), $etape( $cat['nom'] ) );
		}
	} elseif ( is_singular() ) {
		$post           = get_queried_object();
		$c['canonique'] = get_permalink( $post );
		$c['noindex']   = (bool) get_post_meta( $post->ID, '_el_noindex', true );
		$titre_seo      = el_meta( $post->ID, 'seo_titre' );
		$desc_seo       = el_meta( $post->ID, 'seo_description' );

		if ( 'el_formule' === $post->post_type ) {
			$f                = el_formule( $post );
			$c['formule']     = $f;
			$c['titre']       = $f['titre'] . ' à Amiens | Event\'Light';
			$c['description'] = $f['description'];
			$c['fil']         = array( $etape( 'Nos formules', get_post_type_archive_link( 'el_formule' ) ), $etape( $f['titre'] ) );
		} elseif ( 'el_produit' === $post->post_type ) {
			$p                = el_produit( $post );
			$c['produit']     = $p;
			$c['titre']       = 'Location ' . $p['nom'] . ' Amiens - ' . $p['prix'] . ' | Event\'Light';
			$c['description'] = $p['description'] . ' Location à Amiens, livraison dès 25 €.';
			$c['fil']         = array(
				$etape( 'Location', get_post_type_archive_link( 'el_produit' ) ),
				$etape( '' !== $p['categorie'] ? $p['categorie'] : 'Catalogue', $p['categorieUrl'] ),
				$etape( $p['nom'] ),
			);
		} elseif ( 'el_realisation' === $post->post_type ) {
			$r                = el_realisation( $post );
			$c['titre']       = $r['titre'] . ' - ' . $r['annee'] . ' | Portfolio Event\'Light';
			$c['description'] = '' !== $r['texte'] ? $r['texte'] : $r['titre'] . ' - réalisation Event\'Light.';
			$c['fil']         = array( $etape( 'Réalisations', get_post_type_archive_link( 'el_realisation' ) ), $etape( el_titre_court( $r['titre'] ) ) );
		} else {
			$noms             = el_noms_courts();
			$titre            = get_the_title( $post );
			$c['titre']       = $titre . ' | Event\'Light';
			$c['description'] = wp_trim_words( wp_strip_all_tags( strip_shortcodes( $post->post_content ) ), 28, '…' );
			if ( 'page' === $post->post_type && 'merci' !== $post->post_name ) {
				$c['fil'] = array( $etape( isset( $noms[ $post->post_name ] ) ? $noms[ $post->post_name ] : $titre ) );
			}
		}
		if ( '' !== $titre_seo ) {
			$c['titre'] = $titre_seo;
		}
		if ( '' !== $desc_seo ) {
			$c['description'] = $desc_seo;
		}
	}
	return $c;
}

/* ---------------------------------------------------------------------- balises */

function el_titre_document( $titre ) {
	if ( is_feed() ) {
		return $titre;
	}
	$c = el_contexte();
	return '' !== $c['titre'] ? $c['titre'] : $titre;
}
add_filter( 'pre_get_document_title', 'el_titre_document' );

function el_robots( $robots ) {
	if ( ! empty( $robots['noindex'] ) ) {
		return $robots;
	}
	if ( el_contexte()['noindex'] ) {
		return array( 'noindex' => true );
	}
	$robots['index']             = true;
	$robots['follow']            = true;
	$robots['max-image-preview'] = 'large';
	$robots['max-snippet']       = '-1';
	$robots['max-video-preview'] = '-1';
	return $robots;
}
add_filter( 'wp_robots', 'el_robots' );

/** Image proposée quand une page est partagée. */
function el_image_partage() {
	return get_theme_file_uri( 'assets/logo/og-default.jpg' );
}

function el_balises() {
	$c    = el_contexte();
	$site = el_site();
	?>
<meta name="author" content="Event'Light">
<meta name="geo.region" content="<?php echo esc_attr( 'FR-' . substr( $site['postalCode'], 0, 2 ) ); ?>">
<meta name="geo.placename" content="<?php echo esc_attr( $site['city'] ); ?>">
<meta name="geo.position" content="<?php echo esc_attr( $site['latitude'] . ';' . $site['longitude'] ); ?>">
<meta name="ICBM" content="<?php echo esc_attr( $site['latitude'] . ', ' . $site['longitude'] ); ?>">
<meta name="theme-color" content="#d4d7d9" media="(prefers-color-scheme: light)">
<meta name="theme-color" content="#17191b" media="(prefers-color-scheme: dark)">
	<?php if ( ! has_site_icon() ) : ?>
<link rel="icon" href="<?php echo esc_url( get_theme_file_uri( 'assets/logo/favicon.svg' ) ); ?>" type="image/svg+xml">
<link rel="icon" href="<?php echo esc_url( get_theme_file_uri( 'assets/logo/favicon-48.png' ) ); ?>" sizes="48x48" type="image/png">
<link rel="apple-touch-icon" href="<?php echo esc_url( get_theme_file_uri( 'assets/logo/apple-touch-icon.png' ) ); ?>">
<link rel="manifest" href="<?php echo esc_url( home_url( '/?el-manifeste=1' ) ); ?>">
	<?php endif; ?>
	<?php
	if ( el_seo_externe() ) {
		return;
	}
	$image = el_image_partage();
	$titre = wp_get_document_title();
	if ( '' !== $c['description'] ) :
		?>
<meta name="description" content="<?php echo esc_attr( $c['description'] ); ?>">
		<?php
	endif;
	if ( '' !== $c['canonique'] ) :
		?>
<link rel="canonical" href="<?php echo esc_url( $c['canonique'] ); ?>">
<link rel="alternate" hreflang="fr" href="<?php echo esc_url( $c['canonique'] ); ?>">
<link rel="alternate" hreflang="x-default" href="<?php echo esc_url( $c['canonique'] ); ?>">
<meta property="og:url" content="<?php echo esc_url( $c['canonique'] ); ?>">
		<?php
	endif;
	?>
<meta property="og:title" content="<?php echo esc_attr( $titre ); ?>">
<meta property="og:description" content="<?php echo esc_attr( $c['description'] ); ?>">
<meta property="og:type" content="website">
<meta property="og:locale" content="fr_FR">
<meta property="og:site_name" content="Event'Light">
<meta property="og:image" content="<?php echo esc_url( $image ); ?>">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:image:alt" content="Event'Light, son, lumière et vidéo mapping à Amiens">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?php echo esc_attr( $titre ); ?>">
<meta name="twitter:description" content="<?php echo esc_attr( $c['description'] ); ?>">
<meta name="twitter:image" content="<?php echo esc_url( $image ); ?>">
	<?php
}
add_action( 'wp_head', 'el_balises', 2 );

/** Thème choisi par le visiteur, appliqué avant le premier affichage. Polices principales annoncées au navigateur. */
function el_avant_les_styles() {
	?>
<script>
document.documentElement.classList.add("js");
try {
	var t = localStorage.getItem("el-salle");
	if (t === "dark" || t === "light") document.documentElement.setAttribute("data-theme", t);
} catch (e) {}
</script>
<link rel="preload" href="<?php echo esc_url( get_theme_file_uri( 'assets/fonts/sofia-sans-extra-condensed-latin.woff2' ) ); ?>" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="<?php echo esc_url( get_theme_file_uri( 'assets/fonts/sofia-sans-latin.woff2' ) ); ?>" as="font" type="font/woff2" crossorigin>
	<?php
}
add_action( 'wp_head', 'el_avant_les_styles', 3 );

/** Petit fichier qui décrit le site aux téléphones (nom, icônes, couleur). */
function el_manifeste() {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( ! isset( $_GET['el-manifeste'] ) ) {
		return;
	}
	nocache_headers();
	header( 'Content-Type: application/manifest+json; charset=UTF-8' );
	header( 'Cache-Control: public, max-age=86400' );
	echo wp_json_encode(
		array(
			'name'             => 'Event\'Light',
			'short_name'       => 'Event\'Light',
			'description'      => 'Son, lumière et vidéo mapping à Amiens',
			'start_url'        => home_url( '/' ),
			'display'          => 'browser',
			'background_color' => '#d4d7d9',
			'theme_color'      => '#d4d7d9',
			'lang'             => 'fr',
			'icons'            => array(
				array(
					'src'   => get_theme_file_uri( 'assets/logo/icon-192.png' ),
					'sizes' => '192x192',
					'type'  => 'image/png',
				),
				array(
					'src'   => get_theme_file_uri( 'assets/logo/icon-512.png' ),
					'sizes' => '512x512',
					'type'  => 'image/png',
				),
			),
		),
		JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
	);
	exit;
}
add_action( 'init', 'el_manifeste' );

/* ---------------------------------------------------------------------- données structurées */

function el_schema() {
	$c         = el_contexte();
	$site      = el_site();
	$racine    = $site['url'];
	$commerce  = $racine . '/#business';
	$canonique = '' !== $c['canonique'] ? $c['canonique'] : home_url( add_query_arg( array() ) );
	$graphe    = array();

	$graphe[] = array(
		'@type'         => array( 'LocalBusiness', 'EntertainmentBusiness' ),
		'@id'           => $commerce,
		'name'          => $site['name'],
		'legalName'     => $site['name'],
		'alternateName' => array( 'Eventlight', 'Event Light', 'Event\'light' ),
		'url'           => $racine,
		'email'         => $site['email'],
		'telephone'     => $site['contacts'] ? $site['contacts'][0]['iso'] : '',
		'image'         => el_image_partage(),
		'logo'          => get_theme_file_uri( 'assets/logo/eventlight-logo-courant-noir.svg' ),
		'priceRange'    => '€€',
		'vatID'         => $site['siretRaw'],
		'description'   => 'Location et prestation son, lumière, effets, structures et vidéo mapping pour mariages, anniversaires, comités d\'entreprise et manifestations sportives à Amiens et dans la Somme.',
		'address'       => array(
			'@type'           => 'PostalAddress',
			'streetAddress'   => $site['street'],
			'addressLocality' => $site['city'],
			'postalCode'      => $site['postalCode'],
			'addressRegion'   => $site['department'],
			'addressCountry'  => 'FR',
		),
		'geo'           => array(
			'@type'     => 'GeoCoordinates',
			'latitude'  => (float) $site['latitude'],
			'longitude' => (float) $site['longitude'],
		),
		'areaServed'    => array(
			array(
				'@type' => 'City',
				'name'  => $site['city'],
			),
			array(
				'@type' => 'AdministrativeArea',
				'name'  => $site['department'],
			),
		),
		'sameAs'        => array_values( array_filter( array( $site['facebook'], $site['instagram'], $site['youtube'] ) ) ),
		'knowsAbout'    => array( 'sonorisation événementielle', 'éclairage événementiel', 'vidéo mapping', 'location matériel DJ', 'étincelles froides' ),
	);

	if ( ! el_seo_externe() ) {
		$graphe[] = array(
			'@type'      => 'WebSite',
			'@id'        => $racine . '/#website',
			'url'        => $racine,
			'name'       => $site['name'],
			'inLanguage' => 'fr-FR',
			'publisher'  => array( '@id' => $commerce ),
		);
		$graphe[] = array(
			'@type'       => 'WebPage',
			'@id'         => $canonique . '#webpage',
			'url'         => $canonique,
			'name'        => wp_get_document_title(),
			'description' => $c['description'],
			'isPartOf'    => array( '@id' => $racine . '/#website' ),
			'about'       => array( '@id' => $commerce ),
			'inLanguage'  => 'fr-FR',
		);
		if ( $c['fil'] ) {
			$elements = array(
				array(
					'@type'    => 'ListItem',
					'position' => 1,
					'name'     => 'Accueil',
					'item'     => home_url( '/' ),
				),
			);
			foreach ( $c['fil'] as $i => $etape ) {
				$element = array(
					'@type'    => 'ListItem',
					'position' => $i + 2,
					'name'     => $etape['nom'],
				);
				if ( '' !== $etape['url'] ) {
					$element['item'] = $etape['url'];
				}
				$elements[] = $element;
			}
			$graphe[] = array(
				'@type'           => 'BreadcrumbList',
				'itemListElement' => $elements,
			);
		}
	}

	if ( $c['produit'] ) {
		$p        = $c['produit'];
		$graphe[] = array(
			'@type'       => 'Product',
			'name'        => $p['nom'],
			'description' => $p['description'],
			'category'    => $p['categorie'],
			'sku'         => $p['slug'],
			'brand'       => array(
				'@type' => 'Brand',
				'name'  => 'Event\'Light',
			),
			'url'         => $canonique,
			'offers'      => array(
				'@type'            => 'Offer',
				'url'              => $canonique,
				'priceCurrency'    => 'EUR',
				'price'            => el_prix_schema( $p['prix'] ),
				'availability'     => 'https://schema.org/InStock',
				'businessFunction' => 'http://purl.org/goodrelations/v1#LeaseOut',
				'seller'           => array( '@id' => $commerce ),
				'areaServed'       => array(
					'@type' => 'City',
					'name'  => $site['city'],
				),
			),
		);
	}

	if ( $c['formule'] && $c['formule']['packs'] ) {
		$f        = $c['formule'];
		$packs    = $f['packs'];
		$graphe[] = array(
			'@type'       => 'Service',
			'name'        => 'Prestation ' . $f['titre'] . ' à ' . $site['city'],
			'serviceType' => $f['titre'],
			'provider'    => array( '@id' => $commerce ),
			'areaServed'  => array(
				'@type' => 'City',
				'name'  => $site['city'],
			),
			'description' => $c['description'],
			'url'         => $canonique,
			'offers'      => array(
				'@type'         => 'AggregateOffer',
				'priceCurrency' => 'EUR',
				'lowPrice'      => el_prix_schema( $packs[0]['prix'] ),
				'highPrice'     => el_prix_schema( $packs[ count( $packs ) - 1 ]['prix'] ),
				'offerCount'    => count( $packs ),
			),
		);
	}

	if ( $c['accueil'] && el_faq() ) {
		$questions = array();
		foreach ( el_faq() as $q ) {
			$questions[] = array(
				'@type'          => 'Question',
				'name'           => $q['q'],
				'acceptedAnswer' => array(
					'@type' => 'Answer',
					'text'  => $q['a'],
				),
			);
		}
		$graphe[] = array(
			'@type'      => 'FAQPage',
			'mainEntity' => $questions,
		);
	}

	$donnees = array(
		'@context' => 'https://schema.org',
		'@graph'   => $graphe,
	);
	echo '<script type="application/ld+json">' . wp_json_encode( $donnees, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . "</script>\n";
}
add_action( 'wp_head', 'el_schema', 20 );

/* ---------------------------------------------------------------------- styles et script */

/** Numéro de version d'un fichier du thème : sa date de modification, pour renouveler le cache. */
function el_version( $fichier ) {
	$chemin = get_theme_file_path( $fichier );
	return file_exists( $chemin ) ? (string) filemtime( $chemin ) : wp_get_theme()->get( 'Version' );
}

/** Vrai sur les pages entièrement dessinées par le thème, où aucun bloc de l'éditeur n'est affiché. */
function el_page_du_theme() {
	if ( is_front_page() || is_404() || is_post_type_archive( el_types_publics() ) || is_tax( 'el_categorie' ) || is_singular( el_types_publics() ) ) {
		return true;
	}
	return is_page( array( 'devis', 'merci', 'contact', 'video-mapping' ) );
}

function el_fichiers() {
	wp_enqueue_style( 'eventlight', get_theme_file_uri( 'assets/css/site.css' ), array(), el_version( 'assets/css/site.css' ) );
	wp_enqueue_script(
		'eventlight',
		get_theme_file_uri( 'assets/js/site.js' ),
		array(),
		el_version( 'assets/js/site.js' ),
		array(
			'strategy'  => 'defer',
			'in_footer' => true,
		)
	);

	if ( is_admin_bar_showing() ) {
		// La barre d'administration recouvre le haut de la page : l'en-tête collant se décale d'autant.
		wp_add_inline_style( 'eventlight', '.admin-bar .top{top:32px}@media (max-width:782px){.admin-bar .top{top:46px}}@media (max-width:600px){.admin-bar .top{top:0}}' );
	}

	if ( el_page_du_theme() ) {
		// Ces pages n'affichent aucun bloc de l'éditeur : inutile de charger leurs styles.
		wp_dequeue_style( 'wp-block-library' );
		wp_dequeue_style( 'wp-block-library-theme' );
		wp_dequeue_style( 'classic-theme-styles' );
		wp_dequeue_style( 'global-styles' );
		wp_dequeue_style( 'wp-global-styles-placeholder' );
		wp_dequeue_style( 'core-block-supports' );
		remove_action( 'wp_footer', 'wp_enqueue_global_styles', 1 );
	}
}
add_action( 'wp_enqueue_scripts', 'el_fichiers', 20 );

/** Sur les autres pages, WordPress ne charge que les styles des blocs réellement présents. */
add_filter( 'should_load_separate_core_block_assets', '__return_true' );

/** Ce que WordPress ajoute dans l'en-tête et qui ne sert pas ici. */
function el_nettoyage() {
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'wp_head', 'wp_generator' );
	remove_action( 'wp_head', 'rsd_link' );
	remove_action( 'wp_head', 'wlwmanifest_link' );
	remove_action( 'wp_head', 'wp_shortlink_wp_head', 10 );
	remove_action( 'wp_head', 'feed_links', 2 );
	remove_action( 'wp_head', 'feed_links_extra', 3 );
	remove_action( 'wp_head', 'rest_output_link_wp_head', 10 );
	remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
	remove_action( 'template_redirect', 'wp_shortlink_header', 11 );
	remove_action( 'template_redirect', 'rest_output_link_header', 11 );
	if ( ! el_seo_externe() ) {
		remove_action( 'wp_head', 'rel_canonical' );
	}
}
add_action( 'init', 'el_nettoyage' );

/** Les textes s'affichent tels qu'ils sont écrits : pas de guillemets ni de tirets transformés d'office. */
add_filter( 'run_wptexturize', '__return_false' );

/** « tag » est une classe du thème (une étiquette) : on la retire du corps de page des archives d'étiquettes. */
function el_classes_du_corps( $classes ) {
	return array_values( array_diff( $classes, array( 'tag' ) ) );
}
add_filter( 'body_class', 'el_classes_du_corps' );

/** Les images du thème portent déjà leurs attributs : WordPress n'a pas à en ajouter. */
add_filter( 'wp_img_tag_add_auto_sizes', '__return_false' );

/** La typographie française est appliquée à toute la page, juste avant l'envoi. */
function el_tampon( $gabarit ) {
	ob_start( 'el_typographie' );
	return $gabarit;
}
add_filter( 'template_include', 'el_tampon', 9999 );

/* ---------------------------------------------------------------------- navigation */

/** Chemin de la page en cours, depuis la racine du site : « /nos-formules/mariage/ ». */
function el_chemin_courant() {
	global $wp;
	$demande = isset( $wp->request ) ? trim( (string) $wp->request, '/' ) : '';
	return '' === $demande ? '/' : '/' . $demande . '/';
}

/** Chemin d'une adresse du site, depuis la racine du site. Vide si l'adresse mène ailleurs. */
function el_chemin_de( $url ) {
	$racine = untrailingslashit( home_url() );
	if ( 0 === strpos( $url, '/' ) && 0 !== strpos( $url, '//' ) ) {
		$chemin = $url;
	} elseif ( 0 === strpos( $url, $racine ) ) {
		$chemin = substr( $url, strlen( $racine ) );
	} else {
		return '';
	}
	$chemin = (string) strtok( $chemin, '?#' );
	return '' === trim( $chemin, '/' ) ? '/' : '/' . trim( $chemin, '/' ) . '/';
}

/** Les entrées de la navigation : le menu choisi dans Apparence > Menus, sinon les cinq rubriques du site. */
function el_navigation() {
	$entrees = array();
	$lieux   = get_nav_menu_locations();
	if ( ! empty( $lieux['principal'] ) ) {
		$elements = wp_get_nav_menu_items( $lieux['principal'] );
		foreach ( $elements ? $elements : array() as $e ) {
			if ( empty( $e->menu_item_parent ) ) {
				$entrees[] = array(
					'titre' => $e->title,
					'url'   => $e->url,
				);
			}
		}
	}
	if ( ! $entrees ) {
		$entrees = array(
			array(
				'titre' => 'Formules',
				'url'   => el_url( '/nos-formules/' ),
			),
			array(
				'titre' => 'Location',
				'url'   => el_url( '/location/' ),
			),
			array(
				'titre' => 'Mapping',
				'url'   => el_url( '/video-mapping/' ),
			),
			array(
				'titre' => 'Réalisations',
				'url'   => el_url( '/portfolio/' ),
			),
			array(
				'titre' => 'Contact',
				'url'   => el_url( '/contact/' ),
			),
		);
	}
	$ici = el_chemin_courant();
	foreach ( $entrees as &$entree ) {
		$chemin           = el_chemin_de( $entree['url'] );
		$entree['courant'] = '';
		if ( '' === $chemin ) {
			continue;
		}
		if ( $ici === $chemin ) {
			$entree['courant'] = 'page';
		} elseif ( '/' !== $chemin && 0 === strpos( $ici, $chemin ) ) {
			$entree['courant'] = 'true';
		} elseif ( '/contact/' === $chemin && 0 === strpos( $ici, '/devis/' ) ) {
			$entree['courant'] = 'true';
		}
	}
	unset( $entree );
	return $entrees;
}
