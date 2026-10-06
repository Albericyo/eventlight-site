<?php
/**
 * Demandes de devis, réservations, stock et disponibilités.
 *
 * Une demande et une réservation sont le même objet, un « dossier », qui avance d'un statut à
 * l'autre : demande reçue, devis envoyé, confirmée, terminée, ou sans suite. Un dossier porte
 * des dates et des lignes de matériel. Seuls les dossiers confirmés (ou terminés) sortent le
 * matériel du stock ; les autres sont comptés à part, « en attente ».
 */

defined( 'ABSPATH' ) || exit;

function el_planning_type() {
	register_post_type(
		'el_dossier',
		array(
			'labels'       => array(
				'name'               => 'Demandes et réservations',
				'singular_name'      => 'Dossier',
				'menu_name'          => 'Planning',
				'all_items'          => 'Demandes et réservations',
				'add_new'            => 'Ajouter',
				'add_new_item'       => 'Ajouter une réservation',
				'edit_item'          => 'Demande ou réservation',
				'new_item'           => 'Nouvelle réservation',
				'search_items'       => 'Rechercher',
				'not_found'          => 'Rien pour le moment.',
				'not_found_in_trash' => 'Rien dans la corbeille.',
				'item_updated'       => 'Dossier enregistré.',
				'item_published'     => 'Dossier enregistré.',
			),
			'public'       => false,
			'show_ui'      => true,
			'show_in_menu' => 'el-planning',
			'supports'     => array( 'title' ),
			'show_in_rest' => false,
		)
	);
}
add_action( 'init', 'el_planning_type' );

/** Les statuts d'un dossier, dans l'ordre où il avance. */
function el_statuts() {
	return array(
		'demande'   => 'Demande reçue',
		'devis'     => 'Devis envoyé',
		'confirmee' => 'Confirmée',
		'encours'   => 'En cours',
		'terminee'  => 'Terminée',
		'annulee'   => 'Sans suite',
	);
}

/** Les statuts qui sortent réellement le matériel du stock. */
function el_statuts_fermes() {
	return array( 'confirmee', 'encours', 'terminee' );
}

/** Les statuts qui ne bloquent rien, mais qu'il faut garder à l'œil. */
function el_statuts_en_attente() {
	return array( 'demande', 'devis' );
}

function el_genres() {
	return array(
		'prestation' => 'Événement',
		'location'   => 'Location',
	);
}

/** Vrai pour une date écrite AAAA-MM-JJ et qui existe. */
function el_est_date( $date ) {
	if ( ! is_string( $date ) || ! preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', $date, $m ) ) {
		return false;
	}
	return checkdate( (int) $m[2], (int) $m[3], (int) $m[1] );
}

/** « 2026-10-17 » devient « 17/10/2026 ». */
function el_date_fr( $date ) {
	return el_est_date( $date ) ? substr( $date, 8, 2 ) . '/' . substr( $date, 5, 2 ) . '/' . substr( $date, 0, 4 ) : '';
}

/** « du 17 au 19/10/2026 », « le 17/10/2026 », ou rien si le dossier n'a pas de date. */
function el_periode_fr( $debut, $fin ) {
	if ( ! el_est_date( $debut ) ) {
		return '';
	}
	if ( ! el_est_date( $fin ) || $fin === $debut ) {
		return 'le ' . el_date_fr( $debut );
	}
	if ( substr( $debut, 0, 7 ) === substr( $fin, 0, 7 ) ) {
		return 'du ' . substr( $debut, 8, 2 ) . ' au ' . el_date_fr( $fin );
	}
	return 'du ' . el_date_fr( $debut ) . ' au ' . el_date_fr( $fin );
}

/** Les jours d'une période, bornes comprises. Limité à 400 jours. */
function el_jours( $debut, $fin ) {
	$jours = array();
	if ( ! el_est_date( $debut ) || ! el_est_date( $fin ) || $fin < $debut ) {
		return $jours;
	}
	$jour = $debut;
	while ( $jour <= $fin && count( $jours ) < 400 ) {
		$jours[] = $jour;
		$jour    = gmdate( 'Y-m-d', strtotime( $jour . ' 12:00:00 UTC' ) + DAY_IN_SECONDS );
	}
	return $jours;
}

/** Les lignes de matériel d'un dossier : produit et quantité. */
function el_lignes_materiel( $brut ) {
	$lignes = array();
	foreach ( is_array( $brut ) ? $brut : array() as $l ) {
		$produit = isset( $l['produit'] ) ? (int) $l['produit'] : 0;
		$q       = isset( $l['q'] ) ? (int) $l['q'] : 0;
		if ( $produit > 0 && $q > 0 ) {
			if ( isset( $lignes[ $produit ] ) ) {
				$lignes[ $produit ]['q'] += $q;
			} else {
				$lignes[ $produit ] = array(
					'produit' => $produit,
					'q'       => min( $q, 9999 ),
				);
			}
		}
	}
	return array_values( $lignes );
}

function el_dossier( $post ) {
	$post = get_post( $post );
	if ( ! $post || 'el_dossier' !== $post->post_type ) {
		return null;
	}
	$statut  = el_meta( $post->ID, 'statut' );
	$genre   = el_meta( $post->ID, 'genre' );
	$debut   = el_meta( $post->ID, 'debut' );
	$fin     = el_meta( $post->ID, 'fin' );
	$statuts = el_statuts();
	$genres  = el_genres();
	if ( ! el_est_date( $debut ) ) {
		$debut = '';
		$fin   = '';
	} elseif ( ! el_est_date( $fin ) || $fin < $debut ) {
		$fin = $debut;
	}
	return array(
		'id'             => $post->ID,
		'titre'          => get_the_title( $post ),
		'statut'         => isset( $statuts[ $statut ] ) ? $statut : 'demande',
		'genre'          => isset( $genres[ $genre ] ) ? $genre : 'prestation',
		'debut'          => $debut,
		'fin'            => $fin,
		'client'         => el_meta( $post->ID, 'client' ),
		'email'          => el_meta( $post->ID, 'email' ),
		'tel'            => el_meta( $post->ID, 'tel' ),
		'lieu'           => el_meta( $post->ID, 'lieu' ),
		'invites'        => el_meta( $post->ID, 'invites' ),
		'type_evenement' => el_meta( $post->ID, 'type_evenement' ),
		'message'        => el_meta( $post->ID, 'message' ),
		'materiel_texte' => el_meta( $post->ID, 'materiel_texte' ),
		'lignes'         => el_lignes_materiel( get_post_meta( $post->ID, '_el_lignes', true ) ),
		'montant'        => el_meta( $post->ID, 'montant' ),
		'acompte'        => el_meta( $post->ID, 'acompte' ),
		'notes'          => el_meta( $post->ID, 'notes' ),
		'origine'        => el_meta( $post->ID, 'origine' ),
		'recu'           => $post->post_date,
	);
}

/**
 * Les dossiers qui touchent une période.
 *
 * @param string        $debut   Premier jour, AAAA-MM-JJ.
 * @param string        $fin     Dernier jour.
 * @param string[]|null $statuts Statuts voulus. Par défaut : tous sauf « sans suite ».
 */
function el_dossiers_entre( $debut, $fin, $statuts = null ) {
	if ( ! el_est_date( $debut ) || ! el_est_date( $fin ) ) {
		return array();
	}
	if ( null === $statuts ) {
		$statuts = array_diff( array_keys( el_statuts() ), array( 'annulee' ) );
	}
	$posts = get_posts(
		array(
			'post_type'   => 'el_dossier',
			'post_status' => 'publish',
			'numberposts' => -1,
			'orderby'     => 'ID',
			'order'       => 'ASC',
			'meta_query'  => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				'relation' => 'AND',
				array(
					'key'     => '_el_debut',
					'value'   => $fin,
					'compare' => '<=',
				),
				array(
					'key'     => '_el_fin',
					'value'   => $debut,
					'compare' => '>=',
				),
				array(
					'key'     => '_el_statut',
					'value'   => array_values( $statuts ),
					'compare' => 'IN',
				),
			),
		)
	);
	$dossiers = array_values( array_filter( array_map( 'el_dossier', $posts ) ) );
	usort(
		$dossiers,
		function ( $a, $b ) {
			return strcmp( $a['debut'] . sprintf( '%09d', $a['id'] ), $b['debut'] . sprintf( '%09d', $b['id'] ) );
		}
	);
	return $dossiers;
}

/** Le stock de chaque produit suivi : identifiant => quantité. Un produit sans stock renseigné n'est pas suivi. */
function el_stocks() {
	static $stocks = null;
	if ( null === $stocks ) {
		$stocks = array();
		foreach ( el_contenus( 'el_produit' ) as $post ) {
			$valeur = get_post_meta( $post->ID, '_el_stock', true );
			if ( '' !== $valeur && null !== $valeur && is_numeric( $valeur ) ) {
				$stocks[ $post->ID ] = max( 0, (int) $valeur );
			}
		}
		// Un produit qui a une recette n'a pas de stock à lui : c'est ce que ses pièces permettent de monter.
		$pieces = el_pieces_stocks();
		foreach ( el_recettes() as $id => $recette ) {
			$capacite = el_recette_capacite( $recette, $pieces );
			if ( null !== $capacite ) {
				$stocks[ $id ] = $capacite;
			}
		}
	}
	return $stocks;
}

/** Le stock d'un produit : un nombre, ou null quand il n'est pas suivi. */
function el_produit_stock( $produit_id ) {
	$stocks = el_stocks();
	return isset( $stocks[ (int) $produit_id ] ) ? $stocks[ (int) $produit_id ] : null;
}

/** Les stocks suivis, par adresse de produit : { slug: quantité }. Sert au plafond côté navigateur. */
function el_stocks_par_slug() {
	$carte = array();
	foreach ( el_stocks() as $id => $stock ) {
		$post = get_post( $id );
		if ( $post && 'publish' === $post->post_status ) {
			$carte[ $post->post_name ] = $stock;
		}
	}
	return $carte;
}

/** Attribut HTML du bouton « Ajouter » : data-stock="N" quand le stock est suivi, rien sinon. */
function el_attr_stock( $produit_id ) {
	$stock = el_produit_stock( $produit_id );
	return null === $stock ? '' : ' data-stock="' . (int) $stock . '"';
}

/**
 * Ce qui est sorti, produit par produit, sur une période.
 *
 * Pour chaque produit, on retient le jour le plus chargé de la période : deux réservations qui ne
 * se chevauchent pas ne s'additionnent pas.
 *
 * @param string $debut Premier jour.
 * @param string $fin   Dernier jour.
 * @param int    $sauf  Dossier à ignorer (celui qu'on est en train de modifier).
 * @return array identifiant de produit => array( 'ferme' => quantité confirmée, 'attente' => quantité demandée ailleurs ).
 */
function el_sorties( $debut, $fin, $sauf = 0 ) {
	$par_jour = array(
		'ferme'   => array(),
		'attente' => array(),
	);
	$fermes   = el_statuts_fermes();
	foreach ( el_dossiers_entre( $debut, $fin ) as $d ) {
		if ( $d['id'] === (int) $sauf || ! $d['lignes'] ) {
			continue;
		}
		$sorte = in_array( $d['statut'], $fermes, true ) ? 'ferme' : 'attente';
		foreach ( el_jours( max( $d['debut'], $debut ), min( $d['fin'], $fin ) ) as $jour ) {
			foreach ( $d['lignes'] as $l ) {
				if ( ! isset( $par_jour[ $sorte ][ $l['produit'] ][ $jour ] ) ) {
					$par_jour[ $sorte ][ $l['produit'] ][ $jour ] = 0;
				}
				$par_jour[ $sorte ][ $l['produit'] ][ $jour ] += $l['q'];
			}
		}
	}
	$sorties = array();
	foreach ( $par_jour as $sorte => $produits ) {
		foreach ( $produits as $produit => $jours ) {
			if ( ! isset( $sorties[ $produit ] ) ) {
				$sorties[ $produit ] = array(
					'ferme'   => 0,
					'attente' => 0,
				);
			}
			$sorties[ $produit ][ $sorte ] = max( $jours );
		}
	}
	return $sorties;
}

/**
 * Les disponibilités de tout le catalogue suivi, sur une période.
 *
 * @return array identifiant de produit => array( 'stock', 'ferme', 'attente', 'dispo' ).
 */
function el_disponibilites( $debut, $fin, $sauf = 0 ) {
	$sorties  = el_sorties( $debut, $fin, $sauf );
	$pieces   = el_pieces_dispos( $debut, $fin, $sauf );
	$recettes = el_recettes();
	$dispos   = array();
	foreach ( el_stocks() as $produit => $stock ) {
		$ferme   = isset( $sorties[ $produit ] ) ? $sorties[ $produit ]['ferme'] : 0;
		$attente = isset( $sorties[ $produit ] ) ? $sorties[ $produit ]['attente'] : 0;
		$dispo   = $stock - $ferme;
		if ( isset( $recettes[ $produit ] ) ) {
			// Pour un produit fait de pièces : ce que les pièces encore libres permettent de monter.
			$restes = array();
			foreach ( $pieces as $piece => $x ) {
				$restes[ $piece ] = $x['dispo'];
			}
			$possible = el_recette_capacite( $recettes[ $produit ], $restes );
			if ( null !== $possible ) {
				$dispo = $possible;
				$ferme = $stock - $possible;
			}
		}
		$dispos[ $produit ] = array(
			'stock'   => $stock,
			'ferme'   => $ferme,
			'attente' => $attente,
			'dispo'   => $dispo,
		);
	}
	return $dispos;
}

/**
 * Ce qui manquerait si ce dossier était confirmé tel quel.
 *
 * @return array Lignes en dépassement : array( 'produit', 'nom', 'demande', 'dispo' ).
 */
function el_depassements( $dossier ) {
	if ( ! $dossier || '' === $dossier['debut'] || ! $dossier['lignes'] ) {
		return array();
	}
	$dispos   = el_disponibilites( $dossier['debut'], $dossier['fin'], $dossier['id'] );
	$recettes = el_recettes();
	$manques  = array();
	foreach ( $dossier['lignes'] as $l ) {
		// Les produits faits de pièces sont vérifiés plus bas, pièce par pièce, tous produits confondus.
		if ( isset( $recettes[ $l['produit'] ] ) ) {
			continue;
		}
		if ( isset( $dispos[ $l['produit'] ] ) && $l['q'] > $dispos[ $l['produit'] ]['dispo'] ) {
			$manques[] = array(
				'produit' => $l['produit'],
				'nom'     => get_the_title( $l['produit'] ),
				'demande' => $l['q'],
				'dispo'   => max( 0, $dispos[ $l['produit'] ]['dispo'] ),
			);
		}
	}
	foreach ( el_pieces_manques( $dossier ) as $m ) {
		$manques[] = array(
			'produit' => 0,
			'nom'     => 'Pièce : ' . $m['nom'],
			'demande' => $m['demande'],
			'dispo'   => $m['dispo'],
		);
	}
	return $manques;
}

/** Le nombre de demandes qui attendent une réponse. */
function el_demandes_a_traiter() {
	$ids = get_posts(
		array(
			'post_type'   => 'el_dossier',
			'post_status' => 'publish',
			'numberposts' => -1,
			'fields'      => 'ids',
			'meta_key'    => '_el_statut', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_value'  => 'demande', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
		)
	);
	return count( $ids );
}

/**
 * Enregistre un dossier.
 *
 * @param array $donnees Voir el_dossier() pour les clés. « id » : dossier existant à mettre à jour.
 * @return int Identifiant du dossier, 0 en cas d'échec.
 */
function el_dossier_enregistrer( $donnees ) {
	$id = isset( $donnees['id'] ) ? (int) $donnees['id'] : 0;
	if ( ! $id ) {
		$id = wp_insert_post(
			array(
				'post_type'   => 'el_dossier',
				'post_status' => 'publish',
				'post_title'  => isset( $donnees['titre'] ) ? $donnees['titre'] : 'Réservation',
			)
		);
		if ( ! $id || is_wp_error( $id ) ) {
			return 0;
		}
	}
	$debut = isset( $donnees['debut'] ) && el_est_date( $donnees['debut'] ) ? $donnees['debut'] : '';
	$fin   = isset( $donnees['fin'] ) && el_est_date( $donnees['fin'] ) ? $donnees['fin'] : '';
	if ( '' === $debut ) {
		$fin = '';
	} elseif ( '' === $fin || $fin < $debut ) {
		$fin = $debut;
	}
	$donnees['debut'] = $debut;
	$donnees['fin']   = $fin;
	$textes           = array( 'statut', 'genre', 'debut', 'fin', 'client', 'email', 'tel', 'lieu', 'invites', 'type_evenement', 'message', 'materiel_texte', 'montant', 'acompte', 'notes', 'origine' );
	foreach ( $textes as $cle ) {
		if ( array_key_exists( $cle, $donnees ) ) {
			update_post_meta( $id, '_el_' . $cle, wp_slash( (string) $donnees[ $cle ] ) );
		}
	}
	if ( array_key_exists( 'lignes', $donnees ) ) {
		update_post_meta( $id, '_el_lignes', el_lignes_materiel( $donnees['lignes'] ) );
	}
	return $id;
}

/* ---------------------------------------------------------------------- agenda à suivre depuis un téléphone */

/** La clé secrète de l'adresse de l'agenda. */
function el_agenda_jeton() {
	$jeton = (string) get_option( 'el_agenda_jeton', '' );
	if ( '' === $jeton ) {
		$jeton = wp_generate_password( 32, false );
		update_option( 'el_agenda_jeton', $jeton, false );
	}
	return $jeton;
}

function el_agenda_adresse() {
	return home_url( '/?el-agenda=' . el_agenda_jeton() );
}

/** Texte d'un champ d'agenda : caractères spéciaux protégés. */
function el_ics_texte( $texte ) {
	$texte = str_replace( array( "\r\n", "\r" ), "\n", (string) $texte );
	return str_replace( array( '\\', ';', ',', "\n" ), array( '\\\\', '\\;', '\\,', '\\n' ), $texte );
}

/** Une ligne d'agenda, repliée à 75 octets comme le demande le format. */
function el_ics_ligne( $ligne ) {
	$sortie = '';
	while ( strlen( $ligne ) > 73 ) {
		$morceau = function_exists( 'mb_strcut' ) ? mb_strcut( $ligne, 0, 73, 'UTF-8' ) : substr( $ligne, 0, 73 );
		$sortie .= $morceau . "\r\n ";
		$ligne   = substr( $ligne, strlen( $morceau ) );
	}
	return $sortie . $ligne . "\r\n";
}

/** Sert le planning au format iCalendar, pour Google Agenda, l'iPhone ou Outlook. */
function el_agenda() {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( ! isset( $_GET['el-agenda'] ) ) {
		return;
	}
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$recu = sanitize_text_field( wp_unslash( $_GET['el-agenda'] ) );
	if ( ! hash_equals( el_agenda_jeton(), $recu ) ) {
		status_header( 403 );
		exit;
	}
	$statuts  = el_statuts();
	$genres   = el_genres();
	$attente  = el_statuts_en_attente();
	$hote     = (string) wp_parse_url( home_url(), PHP_URL_HOST );
	$debut    = gmdate( 'Y-m-d', time() - 90 * DAY_IN_SECONDS );
	$fin      = gmdate( 'Y-m-d', time() + 3 * YEAR_IN_SECONDS );
	$sortie   = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//Event'Light//Planning//FR\r\nCALSCALE:GREGORIAN\r\nMETHOD:PUBLISH\r\n";
	$sortie  .= el_ics_ligne( 'X-WR-CALNAME:' . el_ics_texte( 'Event\'Light, planning' ) );
	foreach ( el_dossiers_entre( $debut, $fin ) as $d ) {
		$lignes = array();
		foreach ( $d['lignes'] as $l ) {
			$lignes[] = $l['q'] . ' x ' . get_the_title( $l['produit'] );
		}
		$description = array_filter(
			array(
				$statuts[ $d['statut'] ],
				'' !== $d['client'] ? 'Client : ' . $d['client'] : '',
				'' !== $d['tel'] ? 'Téléphone : ' . $d['tel'] : '',
				'' !== $d['email'] ? 'E-mail : ' . $d['email'] : '',
				$lignes ? "Matériel :\n" . implode( "\n", $lignes ) : '',
				'' !== $d['notes'] ? "Notes :\n" . $d['notes'] : '',
			)
		);
		$titre       = $genres[ $d['genre'] ] . ' : ' . $d['titre'] . ( in_array( $d['statut'], $attente, true ) ? ' (à confirmer)' : '' );
		$sortie     .= "BEGIN:VEVENT\r\n";
		$sortie     .= el_ics_ligne( 'UID:dossier-' . $d['id'] . '@' . $hote );
		$sortie     .= el_ics_ligne( 'DTSTAMP:' . gmdate( 'Ymd\THis\Z', (int) get_post_modified_time( 'U', true, $d['id'] ) ) );
		$sortie     .= el_ics_ligne( 'DTSTART;VALUE=DATE:' . str_replace( '-', '', $d['debut'] ) );
		$sortie     .= el_ics_ligne( 'DTEND;VALUE=DATE:' . gmdate( 'Ymd', strtotime( $d['fin'] . ' 12:00:00 UTC' ) + DAY_IN_SECONDS ) );
		$sortie     .= el_ics_ligne( 'SUMMARY:' . el_ics_texte( $titre ) );
		if ( '' !== $d['lieu'] ) {
			$sortie .= el_ics_ligne( 'LOCATION:' . el_ics_texte( $d['lieu'] ) );
		}
		$sortie .= el_ics_ligne( 'DESCRIPTION:' . el_ics_texte( implode( "\n", $description ) ) );
		$sortie .= el_ics_ligne( 'STATUS:' . ( in_array( $d['statut'], $attente, true ) ? 'TENTATIVE' : 'CONFIRMED' ) );
		$sortie .= "TRANSP:OPAQUE\r\nEND:VEVENT\r\n";
	}
	$sortie .= "END:VCALENDAR\r\n";

	nocache_headers();
	header( 'Content-Type: text/calendar; charset=utf-8' );
	header( 'Content-Disposition: inline; filename="eventlight-planning.ics"' );
	header( 'X-Robots-Tag: noindex' );
	echo $sortie; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	exit;
}
add_action( 'init', 'el_agenda', 20 );
