<?php
/**
 * Charte graphique d'Event'Light : un document de marque en pages A4 paysage.
 *
 * Document interne. Il n'existe pas comme page du site : il est servi par el_charte_servir()
 * (inc/redirections.php) aux seuls administrateurs et s'affiche dans le menu Event'Light > Charte graphique.
 * « Enregistrer en PDF » ouvre la boîte d'impression du navigateur, avec la mise en page prévue pour le papier.
 */

defined( 'ABSPATH' ) || exit;

$el_u = function ( $fichier ) {
	return esc_url( get_theme_file_uri( $fichier ) );
};
$el_l = function ( $forme, $graisse, $couleur ) use ( $el_u ) {
	return $el_u( 'assets/logo/eventlight-' . $forme . '-' . $graisse . '-' . $couleur . '.svg' );
};
$el_rvb = function ( $hex ) {
	$hex = ltrim( $hex, '#' );
	return hexdec( substr( $hex, 0, 2 ) ) . ', ' . hexdec( substr( $hex, 2, 2 ) ) . ', ' . hexdec( substr( $hex, 4, 2 ) );
};
$el_total = 18;
$el_pied  = function ( $n, $section ) use ( $el_total ) {
	return '<footer class="pied"><span>Event\'Light, charte graphique, version 1.0</span><span>' . esc_html( $section ) . '</span><span>' . (int) $n . ' / ' . (int) $el_total . '</span></footer>';
};
$el_imprimer = isset( $_GET['imprimer'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

$el_couleurs = array(
	array( 'Aluminium', '#d4d7d9', 'Le mur : fond de page, d\'écran, de document.' ),
	array( 'Lumière', '#ffffff', 'Ce qui est éclairé : cartes, champs, pack conseillé, survol.' ),
	array( 'Trait', '#000000', 'Texte, tracés, logo, bouton principal.' ),
	array( 'Sourdine', '#474c51', 'Texte secondaire, légendes.' ),
	array( 'Tungstène', '#ffd98a', 'Lumière chaude : faisceaux dessinés sur blanc, sélection.' ),
);
$el_couleurs_sombres = array(
	array( 'Salle éteinte', '#17191b', 'Le mur en thème sombre.' ),
	array( 'Éclairé, salle éteinte', '#303438', 'Panneaux mis en avant en thème sombre.' ),
	array( 'Écran', '#000000', 'Fond des photos et des vidéos, dans les deux thèmes.' ),
	array( 'Vitrine', '#ffffff', 'Fond des photos de matériel, dans les deux thèmes.' ),
);

ob_start( 'el_typographie' );
?>
<!doctype html>
<html lang="fr" data-theme="light">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Charte graphique | Event'Light</title>
<link rel="stylesheet" href="<?php echo $el_u( 'assets/css/site.css' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>">
<style>
@page { size: 297mm 210mm; margin: 0; }
html { background: #888d91; }
body.charte { margin: 0; background: #888d91; color: #000; font-family: var(--texte); font-size: 10.5pt; line-height: 1.45; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
.charte *, .charte *::before, .charte *::after { box-sizing: border-box; }
.charte .feuilles { display: grid; justify-content: center; gap: 12mm; padding: 10mm 0 14mm; transform-origin: top center; }
.charte .page { position: relative; width: 297mm; height: 210mm; overflow: hidden; background: var(--mur); color: var(--trait); padding: 17mm 18mm 0; break-after: page; page-break-after: always; }
.charte .page:last-child { break-after: auto; page-break-after: auto; }
.charte .page.sombre { background: #17191b; color: #fff; }

/* texte */
.charte h1, .charte h2, .charte h3, .charte p, .charte dl, .charte dd, .charte figure, .charte ul, .charte ol { margin: 0; padding: 0; }
.charte h2 { font-family: var(--display); font-weight: 300; font-size: 46pt; line-height: 0.95; letter-spacing: 0; margin: 0 0 7mm; text-wrap: balance; }
.charte h3 { font-family: var(--texte); font-weight: 600; font-size: 11pt; line-height: 1.25; margin: 0 0 1.5mm; }
.charte p { font-size: 10.5pt; line-height: 1.5; max-width: none; }
.charte .discret { color: var(--sourdine); }
.charte .petit { font-size: 8.5pt; line-height: 1.4; }
.charte .page.sombre .discret { color: #aeb3b8; }
.charte code { font-family: var(--texte); font-weight: 600; font-variant-numeric: tabular-nums; font-size: 9pt; background: none; padding: 0; }

/* mise en page : une colonne de texte à gauche, la matière à droite */
.charte .grille { display: grid; grid-template-columns: 74mm 1fr; column-gap: 14mm; height: 168mm; }
.charte .droite { display: grid; align-content: start; gap: 8mm; min-width: 0; }
.charte .pied { position: absolute; left: 18mm; right: 18mm; bottom: 8mm; display: grid; grid-template-columns: 1fr auto 1fr; gap: 6mm; border-top: 1.5px solid currentColor; padding-top: 2.4mm; font-size: 7.5pt; line-height: 1.2; color: var(--sourdine); }
.charte .pied span:nth-child(2) { text-align: center; }
.charte .pied span:last-child { text-align: right; font-variant-numeric: tabular-nums; }
.charte .page.sombre .pied { color: #aeb3b8; }

/* surfaces */
.charte .lum { background: #fff; color: #000; }
.charte .ecr { background: #000; color: #fff; }
.charte .mur2 { background: #17191b; color: #fff; }
.charte .alu { background: #d4d7d9; color: #000; }
.charte .cadre { border: 1.5px solid #000; }
.charte .centre { display: grid; place-items: center; }
.charte .legende { display: grid; gap: 0.6mm; padding-top: 2.4mm; }
.charte .legende b { font-weight: 600; font-size: 9.5pt; }
.charte .legende span { font-size: 8.5pt; line-height: 1.35; color: var(--sourdine); }
.charte .fiche { display: grid; gap: 0; border-top: 1.5px solid #000; }
.charte .fiche > div { display: grid; grid-template-columns: 36mm 1fr; gap: 6mm; padding: 2.4mm 0; border-bottom: 1px solid var(--mur-2); }
.charte .fiche dt { font-weight: 600; font-size: 9.5pt; }
.charte .fiche dd { font-size: 9.5pt; line-height: 1.4; }
.charte img.logo { display: block; max-width: 100%; height: auto; }

/* couverture */
.charte .couverture { padding: 0; }
.charte .couverture .faisceau { position: absolute; inset: 0; }
.charte .couverture .titre { position: absolute; left: 150mm; top: 78mm; right: 18mm; }
.charte .couverture h1 { font-family: var(--display); font-weight: 300; font-size: 92pt; line-height: 0.88; text-wrap: balance; }
.charte .couverture .signe-bas { position: absolute; left: 150mm; bottom: 20mm; right: 18mm; display: grid; gap: 1mm; font-size: 10pt; }
.charte .couverture .marque { position: absolute; left: 26mm; top: 34mm; width: 92mm; }

/* sommaire */
.charte .sommaire { list-style: none; border-top: 1.5px solid #000; }
.charte .sommaire li { display: grid; grid-template-columns: 1fr auto; gap: 8mm; align-items: baseline; padding: 0.8mm 0; border-bottom: 1px solid var(--mur-2); font-size: 10.5pt; }
.charte .sommaire li b { font-weight: 600; }
.charte .sommaire li span:last-child { font-variant-numeric: tabular-nums; color: var(--sourdine); }
.charte .sommaire li.groupe { font-family: var(--display); font-weight: 300; font-size: 14pt; line-height: 1; padding: 2.4mm 0 1.2mm; border-bottom: 1.5px solid #000; }

/* principes */
.charte .trois { display: grid; grid-template-columns: repeat(3, 1fr); gap: 6mm; }
.charte .trois > div { display: grid; grid-template-rows: 52mm auto; gap: 3mm; }
.charte .trois .vue { display: grid; place-items: center; }
.charte .phrase { font-family: var(--display); font-weight: 300; font-size: 30pt; line-height: 1; text-wrap: balance; }

/* signe */
.charte .construction { padding: 7mm 9mm; }
.charte .construction svg { display: block; width: 100%; height: 86mm; }

/* logos */
.charte .logos-duo { display: grid; grid-template-columns: 1fr 1fr; gap: 6mm; }
.charte .logos-duo .lum { height: 76mm; }
.charte .logos-duo img { height: 52mm; width: auto; }
.charte .logo-ligne-p { height: 48mm; }
.charte .logo-ligne-p img { width: 150mm; }

.charte .droite { min-width: 0; }

/* graisses */
.charte .graisses { display: grid; gap: 5mm; }
.charte .graisse { display: grid; grid-template-columns: 1fr 58mm; gap: 6mm; align-items: center; }
.charte .graisse .lum { height: 34mm; }
.charte .graisse .legende { padding-top: 0; }

/* zone de protection */
.charte .protection { position: relative; width: 148mm; height: 41.4mm; margin: 4mm auto; }
.charte .protection .zone { position: absolute; inset: 0; border: 1px dashed #000; }
.charte .protection img { position: absolute; left: 7.96mm; top: 7.96mm; width: 132mm; }
.charte .protection .x { position: absolute; width: 7.96mm; height: 7.96mm; background: var(--tungstene); border: 1px solid #000; display: grid; place-items: center; font-weight: 600; font-size: 8pt; }
.charte .fonds { display: grid; grid-template-columns: repeat(4, 1fr); gap: 4mm; }
.charte .fonds .tuile { height: 28mm; padding: 4mm; }
.charte .fonds img { width: 100%; }

/* interdits */
.charte .interdits { display: grid; grid-template-columns: repeat(3, 1fr); gap: 5mm 6mm; }
.charte .interdit .tuile { position: relative; height: 38mm; overflow: hidden; background: #fff; padding: 5mm 8mm; display: grid; place-items: center; }
.charte .interdit .tuile img { width: 100%; }
.charte .interdit .tuile::after { content: ""; position: absolute; right: 2mm; top: 2mm; width: 7mm; height: 7mm; background: linear-gradient(45deg, transparent 46%, #a4161a 46% 54%, transparent 54%), linear-gradient(-45deg, transparent 46%, #a4161a 46% 54%, transparent 54%); }
.charte .masque { width: 100%; aspect-ratio: 3794 / 441; -webkit-mask: url("<?php echo $el_l( 'ligne', 'courant', 'noir' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>") center / contain no-repeat; mask: url("<?php echo $el_l( 'ligne', 'courant', 'noir' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>") center / contain no-repeat; }

/* couleurs */
.charte .palette { display: grid; grid-template-columns: repeat(5, 1fr); gap: 4mm; }
.charte .nuance .echantillon { height: 58mm; border: 1.5px solid #000; }
.charte .palette-s { display: grid; grid-template-columns: repeat(4, 1fr); gap: 4mm; }
.charte .palette-s .echantillon { height: 26mm; border: 1.5px solid #000; }
.charte .nuance .legende b { font-size: 10.5pt; }
.charte .nuance code { display: block; }
.charte .contrastes { display: grid; grid-template-columns: repeat(2, 1fr); gap: 4mm 6mm; }
.charte .contraste { display: grid; grid-template-columns: 30mm 1fr; gap: 5mm; align-items: center; }
.charte .contraste .aa { height: 20mm; display: grid; place-items: center; font-family: var(--display); font-weight: 300; font-size: 30pt; line-height: 1; border: 1.5px solid #000; }
.charte .contraste b { font-size: 11pt; font-variant-numeric: tabular-nums; }
.charte .repartition { display: grid; grid-template-columns: 70fr 15fr 10fr 5fr; height: 18mm; border: 1.5px solid #000; }
.charte .repartition div { display: grid; align-items: end; padding: 1.5mm 2mm; font-size: 8pt; font-weight: 600; }
.charte .repartition div + div { border-left: 1.5px solid #000; }

/* typographie */
.charte .famille { display: grid; gap: 3mm; padding: 6mm 8mm; }
.charte .famille .nom { font-size: 8.5pt; font-weight: 600; }
.charte .grand { font-family: var(--display); font-weight: 300; font-size: 66pt; line-height: 0.9; }
.charte .alphabet { font-size: 15pt; line-height: 1.3; letter-spacing: 0.02em; word-break: break-all; }
.charte .alphabet.cond { font-family: var(--display); font-weight: 300; font-size: 21pt; line-height: 1.15; }
.charte .echelle { display: grid; gap: 0; border-top: 1.5px solid #000; }
.charte .echelle > div { display: grid; grid-template-columns: 56mm 1fr; gap: 6mm; align-items: center; padding: 3mm 0; border-bottom: 1px solid var(--mur-2); min-height: 22mm; }
.charte .echelle .role { font-size: 8.5pt; line-height: 1.35; color: var(--sourdine); }
.charte .echelle .role b { display: block; color: var(--trait); font-size: 9.5pt; font-weight: 600; }
.charte .ex-1 { font-family: var(--display); font-weight: 300; font-size: 48pt; line-height: 0.95; }
.charte .ex-3 { font-family: var(--display); font-weight: 300; font-size: 26pt; line-height: 1; }
.charte .ex-prix { font-family: var(--display); font-weight: 300; font-size: 42pt; line-height: 0.95; }
.charte .ex-inter { font-weight: 600; font-size: 14pt; line-height: 1.2; }
.charte .ex-texte { font-size: 10.5pt; line-height: 1.5; max-width: 90mm; }

/* trait et dessins */
.charte .pictos { display: grid; grid-template-columns: repeat(5, 1fr); gap: 4mm; list-style: none; }
.charte .pictos li { display: grid; gap: 1.5mm; font-size: 8.5pt; font-weight: 600; }
.charte .pictos .vitrine { display: grid; place-items: center; height: 26mm; padding: 3mm; background: #fff; }
.charte .pictos .vitrine svg { width: 100%; height: 100%; color: #000; stroke-width: 1.5; }
.charte .plans { display: grid; grid-template-columns: 1fr 1fr; gap: 6mm; }
.charte .plans figure { display: grid; gap: 2mm; }
.charte .plans .lit { background: #fff; padding: 3mm; }
.charte .plans svg { display: block; width: 100%; height: 44mm; }
.charte .epaisseur { display: grid; grid-template-columns: repeat(3, 1fr); gap: 6mm; }

/* composants */
.charte .composants { display: flex; flex-wrap: wrap; gap: 4mm 5mm; align-items: center; padding: 8mm; }
.charte .etats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 5mm; }
.charte .etat .tuile { height: 40mm; padding: 5mm; display: grid; align-content: space-between; font-weight: 600; font-size: 11pt; }
.charte .etat .repos { border: 1.5px solid #000; background: var(--mur); }
.charte .etat .eclaire { background: #fff; border: 1.5px solid #000; }
.charte .etat .plein { background: #000; color: #fff; border: 1.5px solid #000; }
.charte .etat .tuile small { font-weight: 400; font-size: 8.5pt; line-height: 1.35; }
.charte .btn { pointer-events: none; }
.charte .signe-btn { width: 11mm; }

/* images */
.charte .ecrans { display: grid; grid-template-columns: 1fr 1fr; gap: 6mm; }
.charte .ecrans .surface { height: 78mm; display: grid; place-items: center; border: 1.5px solid #000; }
.charte .ecrans .surface svg.el-signe { width: 52mm; height: auto; }
.charte .ecrans .surface svg.picto { width: 46mm; height: 46mm; stroke-width: 1.5; }

/* ton */
.charte .ton { display: grid; grid-template-columns: 1fr 1fr; gap: 0 8mm; border-top: 1.5px solid #000; }
.charte .ton > div { padding: 3mm 0; border-bottom: 1px solid var(--mur-2); font-size: 10.5pt; line-height: 1.4; }
.charte .ton .titre-col { font-family: var(--display); font-weight: 300; font-size: 20pt; line-height: 1; padding: 3mm 0 2.5mm; border-bottom: 1.5px solid #000; }

/* applications */
.charte .cartes { display: flex; gap: 8mm; align-items: flex-start; }
.charte .carte { width: calc((100% - 8mm) / 2); height: 52mm; box-sizing: border-box; padding: 6mm; display: grid; align-content: space-between; border: 1.5px solid #000; }
.charte .carte.noire { background: #000; color: #fff; place-items: center; }
.charte .carte.noire img { width: 24mm; }
.charte .carte.claire { background: #fff; color: #000; font-size: 8pt; line-height: 1.4; }
.charte .carte.claire .nom { font-family: var(--display); font-weight: 300; font-size: 19pt; line-height: 1; }
.charte .barre { display: flex; justify-content: space-between; align-items: center; padding: 4mm 5mm; background: var(--mur); border: 1.5px solid #000; font-size: 8pt; font-weight: 500; gap: 6mm; }
.charte .barre img { height: 5mm; width: auto; flex-shrink: 0; max-width: none; }
.charte .barre .menu { display: flex; gap: 4mm; white-space: nowrap; align-items: center; }
.charte .barre .plein { background: #000; color: #fff; padding: 2mm 4mm; font-weight: 600; }
.charte .courriel { padding: 6mm 8mm; display: grid; gap: 1mm; font-family: Arial, Helvetica, sans-serif; font-size: 8.5pt; line-height: 1.45; }
.charte .courriel strong { font-size: 11pt; }
.charte .courriel img { width: 62mm; margin: 2mm 0; }
.charte .courriel .gris { color: #555; }

/* fichiers */
.charte .fichiers { display: grid; gap: 0; border-top: 1.5px solid #000; }
.charte .fichiers > div { display: grid; grid-template-columns: 52mm 1fr; gap: 6mm; padding: 2.8mm 0; border-bottom: 1px solid var(--mur-2); font-size: 9.5pt; line-height: 1.4; }
.charte .fichiers dt { font-weight: 600; }
.charte .fichiers code { font-weight: 400; font-size: 8.5pt; }
.charte .contacts { display: grid; grid-template-columns: 1fr 1fr; gap: 6mm; }
.charte .contacts .lum { padding: 6mm; display: grid; gap: 1mm; font-size: 10pt; }
.charte .contacts .lum b { font-family: var(--display); font-weight: 300; font-size: 22pt; line-height: 1; }

/* à l'écran */
@media screen {
	.charte .feuilles { zoom: var(--echelle, 1); }
	.charte .page { box-shadow: 0 1mm 4mm rgba(0, 0, 0, 0.35); }
}
@media print {
	html, body.charte { background: none; }
	.charte .feuilles { padding: 0; gap: 0; zoom: 1; display: block; }
	.charte .page { box-shadow: none; }
}
</style>
</head>
<body class="charte">
<main class="feuilles" id="feuilles">

<!-- 1. couverture -->
<section class="page sombre couverture">
	<svg class="faisceau" viewBox="0 0 297 210" preserveAspectRatio="none" aria-hidden="true">
		<polygon points="72,105 297,18 297,192" fill="#ffd98a" fill-opacity="0.13"/>
	</svg>
	<img class="marque logo" src="<?php echo $el_l( 'logo', 'fin', 'blanc' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>" alt="Event'Light">
	<div class="titre">
		<h1>Charte graphique</h1>
	</div>
	<div class="signe-bas">
		<span>Version 1.0, octobre 2026</span>
		<span class="discret">Document interne, ne pas diffuser.</span>
	</div>
</section>

<!-- 2. sommaire -->
<section class="page">
	<div class="grille">
		<div>
			<h2>Sommaire</h2>
			<p class="discret">Event'Light crée et fait vivre des soirées avec du son, de la lumière, de la vidéo et du mapping, et loue son matériel. Cette charte dit comment l'identité s'écrit, se dessine et se montre.</p>
		</div>
		<div class="droite">
			<ul class="sommaire">
				<li class="groupe" style="padding-top:0"><span>L'identité</span><span></span></li>
				<li><b>Principes</b><span>3</span></li>
				<li><b>Le signe</b><span>4</span></li>
				<li class="groupe"><span>Le logo</span><span></span></li>
				<li><b>Les trois formes</b><span>5</span></li>
				<li><b>Les trois graisses et les tailles</b><span>6</span></li>
				<li><b>Zone de protection et fonds</b><span>7</span></li>
				<li><b>Ce qu'il ne faut pas faire</b><span>8</span></li>
				<li class="groupe"><span>Couleurs et typographie</span><span></span></li>
				<li><b>Les couleurs</b><span>9</span></li>
				<li><b>Contrastes et répartition</b><span>10</span></li>
				<li><b>Les polices</b><span>11</span></li>
				<li><b>La hiérarchie du texte</b><span>12</span></li>
				<li class="groupe"><span>Dessin, lumière, images</span><span></span></li>
				<li><b>Le trait et les dessins</b><span>13</span></li>
				<li><b>La lumière</b><span>14</span></li>
				<li><b>Les images</b><span>15</span></li>
				<li class="groupe"><span>Parole et usages</span><span></span></li>
				<li><b>Le ton</b><span>16</span></li>
				<li><b>Applications</b><span>17</span></li>
				<li><b>Fichiers et contacts</b><span>18</span></li>
			</ul>
		</div>
	</div>
	<?php echo $el_pied( 2, 'Sommaire' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</section>

<!-- 3. principes -->
<section class="page">
	<div class="grille">
		<div>
			<h2>Principes</h2>
			<p class="discret">Tout part du signe : un boîtier, un faisceau. L'identité reprend ce que fait le métier : on éclaire un mur, et ce qui est éclairé devient visible.</p>
		</div>
		<div class="droite">
			<p class="phrase">Un mur gris aluminium, de la lumière blanche, un trait noir.</p>
			<div class="trois">
				<div><div class="vue alu cadre"><span style="font-size:8.5pt;font-weight:600">Le mur</span></div><div class="legende"><b>Le fond est un mur</b><span>Aluminium, #D4D7D9. Il porte tout le reste et ne cherche pas à se faire voir.</span></div></div>
				<div><div class="vue lum cadre"><span style="font-size:8.5pt;font-weight:600">La lumière</span></div><div class="legende"><b>Ce qui compte est éclairé</b><span>Donc blanc. Une carte, un champ, le pack conseillé : on passe du gris au blanc.</span></div></div>
				<div><div class="vue cadre" style="background:#000;color:#fff"><span style="font-size:8.5pt;font-weight:600">Le trait</span></div><div class="legende"><b>Un seul trait, noir</b><span>Texte, logo, dessins, bouton principal. Une seule épaisseur, des angles vifs.</span></div></div>
			</div>
			<dl class="fiche">
				<div><dt>La couleur</dt><dd>Elle vient des photos et des vidéos, jamais de l'interface.</dd></div>
				<div><dt>La forme</dt><dd>Des rectangles. Aucun arrondi, aucune ombre.</dd></div>
				<div><dt>Le mouvement</dt><dd>Un seul mouvement programmé sur tout le site : le faisceau qui s'ouvre.</dd></div>
				<div><dt>La signature</dt><dd>« Créateurs de rêves. »</dd></div>
			</dl>
		</div>
	</div>
	<?php echo $el_pied( 3, 'L\'identité' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</section>

<!-- 4. le signe -->
<section class="page">
	<div class="grille">
		<div>
			<h2>Le signe</h2>
			<p class="discret">Redessiné d'après le logo d'origine, sans en changer la forme. Les irrégularités du tracé à la main sont corrigées : base verticale, pointe au centre du boîtier, épaisseur unique.</p>
		</div>
		<div class="droite">
			<div class="construction lum cadre"><svg viewBox="-44 -22 448 292" role="img" aria-label="Construction du signe : boîtier de 110 par 100, faisceau long de 288 et haut de 216, ouverture de 41 degrés">
					<g fill="none" stroke="var(--sourdine)" stroke-width="1" vector-effect="non-scaling-stroke">
						<path d="M-12 108H356" stroke-dasharray="3 4"/>
						<path d="M0 176H110M0 171V181M110 171V181"/>
						<path d="M-16 58V158M-21 58H-11M-21 158H-11"/>
						<path d="M55 238H343M55 233V243M343 233V243"/>
						<path d="M362 0V216M357 0H367M357 216H367"/>
						<path d="M215 48H295V18"/>
						<path d="M123.4 82.4A73 73 0 0 1 123.4 133.6"/>
					</g>
					<g fill="none" stroke="currentColor" stroke-width="1.5" stroke-linejoin="miter" stroke-miterlimit="4">
						<polygon points="55,108 343,0 343,216" fill="color-mix(in srgb, var(--tungstene) 45%, transparent)" vector-effect="non-scaling-stroke"/>
						<rect x="0" y="58" width="110" height="100" vector-effect="non-scaling-stroke"/>
					</g>
					<g fill="currentColor" font-family="Sofia Sans, sans-serif" font-size="11" text-anchor="middle">
						<text x="55" y="192">110</text>
						<text x="-26" y="112" transform="rotate(-90 -26 108)">100</text>
						<text x="199" y="254">288</text>
						<text x="376" y="112" transform="rotate(90 376 108)">216</text>
						<text x="255" y="62">8</text>
						<text x="303" y="37">3</text>
						<text x="150" y="112">41°</text>
					</g>
				</svg></div>
			<dl class="fiche">
				<div><dt>Boîtier</dt><dd>Rectangle 11:10 (110 × 100 unités).</dd></div>
				<div><dt>Pointe du faisceau</dt><dd>Au centre du boîtier.</dd></div>
				<div><dt>Pente du faisceau</dt><dd>3 pour 8, soit 41° d'ouverture.</dd></div>
				<div><dt>Base</dt><dd>Verticale, 216 unités, à 288 unités de la pointe.</dd></div>
				<div><dt>Superposition</dt><dd>Le faisceau passe par-dessus le boîtier, les deux tracés restent visibles.</dd></div>
			</dl>
		</div>
	</div>
	<?php echo $el_pied( 4, 'L\'identité' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</section>

<!-- 5. les trois formes -->
<section class="page">
	<div class="grille">
		<div>
			<h2>Les trois formes du logo</h2>
			<p class="discret">Le logo existe en trois formes. Elles se déclinent chacune en trois graisses, en noir et en blanc. On choisit la forme selon la place disponible, jamais selon l'humeur.</p>
		</div>
		<div class="droite">
			<div class="logos-duo">
				<figure>
					<div class="lum cadre centre"><img class="logo" src="<?php echo $el_l( 'logo', 'fin', 'noir' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>" alt="Logo principal"></div>
					<figcaption class="legende"><b>Logo principal</b><span>La composition d'origine : EVENT, le signe, LIGHT. Grands formats, affiches, pied de page.</span></figcaption>
				</figure>
				<figure>
					<div class="lum cadre centre"><img class="logo" src="<?php echo $el_l( 'signe', 'fort', 'noir' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>" alt="Signe seul" style="height:34mm"></div>
					<figcaption class="legende"><b>Signe seul</b><span>Icônes, réseaux sociaux, marquage du matériel.</span></figcaption>
				</figure>
			</div>
			<figure>
				<div class="lum cadre centre logo-ligne-p"><img class="logo" src="<?php echo $el_l( 'ligne', 'fin', 'noir' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>" alt="Logo en ligne"></div>
				<figcaption class="legende"><b>Logo en ligne</b><span>Le signe tient la place de l'apostrophe. En-tête du site, signatures, bandeaux.</span></figcaption>
			</figure>
		</div>
	</div>
	<?php echo $el_pied( 5, 'Le logo' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</section>

<!-- 6. graisses et tailles -->
<section class="page">
	<div class="grille">
		<div>
			<h2>Trois graisses, selon la taille</h2>
			<p class="discret">Plus le logo est petit, plus le trait est fort. C'est l'épaisseur qui s'adapte, jamais la forme.</p>
			<p class="discret" style="margin-top:4mm">Le noir sert sur fond clair, le blanc sur fond sombre.</p>
		</div>
		<div class="droite">
			<div class="graisses">
				<div class="graisse">
					<div class="lum cadre centre"><img class="logo" style="width:92mm" src="<?php echo $el_l( 'ligne', 'fin', 'noir' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>" alt="Logo en ligne, fin"></div>
					<div class="legende"><b>Fin</b><span>Le trait d'origine. À partir de 40 cm de large à l'impression, ou 600 px à l'écran.</span></div>
				</div>
				<div class="graisse">
					<div class="lum cadre centre"><img class="logo" style="width:80mm" src="<?php echo $el_l( 'ligne', 'courant', 'noir' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>" alt="Logo en ligne, courant"></div>
					<div class="legende"><b>Courant</b><span>De 8 à 40 cm de large, de 200 à 600 px.</span></div>
				</div>
				<div class="graisse">
					<div class="ecr cadre centre" style="height:34mm"><img class="logo" style="width:52mm" src="<?php echo $el_l( 'ligne', 'fort', 'blanc' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>" alt="Logo en ligne, fort"></div>
					<div class="legende"><b>Fort</b><span>En dessous de 8 cm ou de 200 px : en-tête du site, cartes de visite, étiquettes.</span></div>
				</div>
			</div>
		</div>
	</div>
	<?php echo $el_pied( 6, 'Le logo' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</section>

<!-- 7. zone de protection et fonds -->
<section class="page">
	<div class="grille">
		<div>
			<h2>Zone de protection et fonds</h2>
			<p class="discret">Autour du logo, on laisse un vide égal à la hauteur du boîtier. Rien n'y entre : ni texte, ni image, ni bord de page.</p>
			<p class="discret" style="margin-top:4mm">Sur les photos, le logo se pose dans une zone calme et sombre.</p>
		</div>
		<div class="droite">
			<div class="lum cadre" style="padding:6mm 0">
				<div class="protection">
					<div class="zone"></div>
					<img class="logo" src="<?php echo $el_l( 'ligne', 'courant', 'noir' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>" alt="Logo en ligne avec sa zone de protection">
					<span class="x" style="left:0;top:0">x</span>
					<span class="x" style="right:0;bottom:0">x</span>
				</div>
				<p class="petit discret" style="text-align:center">x = la hauteur du boîtier.</p>
			</div>
			<div class="fonds">
				<figure><div class="tuile alu cadre centre"><img class="logo" src="<?php echo $el_l( 'ligne', 'courant', 'noir' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>" alt=""></div><figcaption class="legende"><b>Aluminium</b><span>Noir</span></figcaption></figure>
				<figure><div class="tuile lum cadre centre"><img class="logo" src="<?php echo $el_l( 'ligne', 'courant', 'noir' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>" alt=""></div><figcaption class="legende"><b>Blanc</b><span>Noir</span></figcaption></figure>
				<figure><div class="tuile ecr cadre centre"><img class="logo" src="<?php echo $el_l( 'ligne', 'courant', 'blanc' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>" alt=""></div><figcaption class="legende"><b>Noir</b><span>Blanc</span></figcaption></figure>
				<figure><div class="tuile mur2 cadre centre"><img class="logo" src="<?php echo $el_l( 'ligne', 'courant', 'blanc' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>" alt=""></div><figcaption class="legende"><b>Salle éteinte</b><span>Blanc</span></figcaption></figure>
			</div>
			<p class="petit discret">Jamais de couleur, jamais de dégradé : noir sur fond clair, blanc sur fond sombre.</p>
		</div>
	</div>
	<?php echo $el_pied( 7, 'Le logo' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</section>

<!-- 8. interdits -->
<section class="page">
	<div class="grille">
		<div>
			<h2>Ce qu'il ne faut pas faire</h2>
			<p class="discret">Le logo ne se retouche pas. On ne l'étire pas, on n'incline pas ses lignes, on n'arrondit pas ses angles, on ne remplit pas le boîtier, on ne change pas l'ouverture du faisceau.</p>
			<p class="discret" style="margin-top:4mm">En cas de doute, on reprend le fichier d'origine.</p>
		</div>
		<div class="droite">
			<div class="interdits">
				<figure class="interdit"><div class="tuile"><img class="logo" style="transform:scaleX(1.3)" src="<?php echo $el_l( 'ligne', 'courant', 'noir' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>" alt=""></div><figcaption class="legende"><b>Étirer</b><span>Les proportions ne bougent pas.</span></figcaption></figure>
				<figure class="interdit"><div class="tuile"><img class="logo" style="transform:skewX(-20deg)" src="<?php echo $el_l( 'ligne', 'courant', 'noir' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>" alt=""></div><figcaption class="legende"><b>Incliner</b><span>Les lignes restent verticales et horizontales.</span></figcaption></figure>
				<figure class="interdit"><div class="tuile"><img class="logo" style="transform:rotate(-9deg)" src="<?php echo $el_l( 'ligne', 'courant', 'noir' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>" alt=""></div><figcaption class="legende"><b>Pivoter</b><span>Le logo se lit à l'horizontale.</span></figcaption></figure>
				<figure class="interdit"><div class="tuile"><div class="masque" style="background:#d4452a"></div></div><figcaption class="legende"><b>Mettre en couleur</b><span>Noir ou blanc, rien d'autre.</span></figcaption></figure>
				<figure class="interdit"><div class="tuile"><div class="masque" style="background:linear-gradient(90deg,#ff7a18,#af002d)"></div></div><figcaption class="legende"><b>Appliquer un dégradé</b><span>Pas d'effet de matière.</span></figcaption></figure>
				<figure class="interdit"><div class="tuile"><img class="logo" style="filter:drop-shadow(1.2mm 1.6mm 1mm rgba(0,0,0,.55))" src="<?php echo $el_l( 'ligne', 'courant', 'noir' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>" alt=""></div><figcaption class="legende"><b>Ajouter une ombre</b><span>Aucune ombre, aucun halo.</span></figcaption></figure>
			</div>
			<figure class="interdit" style="max-width:96mm"><div class="tuile" style="height:30mm"><span style="font-family:Arial,Helvetica,sans-serif;font-weight:700;font-size:25pt;letter-spacing:.04em">EVENT'LIGHT</span></div><figcaption class="legende"><b>Recomposer les lettres</b><span>Les lettres du logo sont dessinées, on ne les retape pas dans une autre police.</span></figcaption></figure>
		</div>
	</div>
	<?php echo $el_pied( 8, 'Le logo' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</section>

<!-- 9. couleurs -->
<section class="page">
	<div class="grille">
		<div>
			<h2>Les couleurs</h2>
			<p class="discret">Le fond est un mur. Ce qui compte est éclairé, donc blanc. La couleur vient des photos, pas de l'interface.</p>
			<p class="discret" style="margin-top:4mm">Le tungstène est la seule teinte de la charte : il ne remplit jamais un fond, il éclaire un faisceau.</p>
		</div>
		<div class="droite">
			<div class="palette">
				<?php foreach ( $el_couleurs as $c ) : ?>
				<div class="nuance"><div class="echantillon" style="background:<?php echo esc_attr( $c[1] ); ?>"></div><div class="legende"><b><?php echo esc_html( $c[0] ); ?></b><code><?php echo esc_html( strtoupper( $c[1] ) ); ?></code><code class="discret">RVB <?php echo esc_html( $el_rvb( $c[1] ) ); ?></code><span><?php echo esc_html( $c[2] ); ?></span></div></div>
				<?php endforeach; ?>
			</div>
			<div class="palette-s">
				<?php foreach ( $el_couleurs_sombres as $c ) : ?>
				<div class="nuance"><div class="echantillon" style="background:<?php echo esc_attr( $c[1] ); ?>"></div><div class="legende"><b><?php echo esc_html( $c[0] ); ?></b><code><?php echo esc_html( strtoupper( $c[1] ) ); ?></code><span><?php echo esc_html( $c[2] ); ?></span></div></div>
				<?php endforeach; ?>
			</div>
		</div>
	</div>
	<?php echo $el_pied( 9, 'Couleurs et typographie' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</section>

<!-- 10. contrastes et répartition -->
<section class="page">
	<div class="grille">
		<div>
			<h2>Contrastes et répartition</h2>
			<p class="discret">Chaque association de couleurs est lisible. Les rapports de contraste ci-contre dépassent tous 4,5 pour 1, le seuil de lecture confortable du texte courant.</p>
		</div>
		<div class="droite">
			<div class="contrastes">
				<?php
				$el_paires = array(
					array( 'Trait sur aluminium', '#000000', '#d4d7d9', '14,5 pour 1' ),
					array( 'Sourdine sur aluminium', '#474c51', '#d4d7d9', '6,0 pour 1' ),
					array( 'Trait sur lumière', '#000000', '#ffffff', '21,0 pour 1' ),
					array( 'Sourdine sur lumière', '#474c51', '#ffffff', '8,7 pour 1' ),
					array( 'Trait sur tungstène', '#000000', '#ffd98a', '15,5 pour 1' ),
					array( 'Blanc sur noir (bouton principal)', '#ffffff', '#000000', '21,0 pour 1' ),
					array( 'Blanc sur salle éteinte', '#ffffff', '#17191b', '17,6 pour 1' ),
					array( 'Sourdine claire sur salle éteinte', '#aeb3b8', '#17191b', '8,3 pour 1' ),
				);
				foreach ( $el_paires as $p ) :
					?>
				<div class="contraste"><div class="aa" style="background:<?php echo esc_attr( $p[2] ); ?>;color:<?php echo esc_attr( $p[1] ); ?>">Aa</div><div><b><?php echo esc_html( $p[3] ); ?></b><br><span class="petit discret"><?php echo esc_html( $p[0] ); ?></span></div></div>
				<?php endforeach; ?>
			</div>
			<div>
				<h3>Répartition conseillée d'une page, à titre indicatif</h3>
				<div class="repartition" style="margin-top:2mm">
					<div style="background:#d4d7d9">Aluminium</div>
					<div style="background:#fff">Lumière</div>
					<div style="background:#000;color:#fff">Trait</div>
					<div style="background:#ffd98a"></div>
				</div>
				<p class="petit discret" style="margin-top:2mm">L'aluminium domine. Le blanc éclaire ce qui compte, le noir dessine, le tungstène reste rare.</p>
			</div>
		</div>
	</div>
	<?php echo $el_pied( 10, 'Couleurs et typographie' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</section>

<!-- 11. polices -->
<section class="page">
	<div class="grille">
		<div>
			<h2>Les polices</h2>
			<p class="discret">Une seule famille, Sofia Sans, en deux largeurs. Les titres sont étroits et légers, comme des barres de lumière. Le texte est en largeur normale.</p>
			<p class="discret" style="margin-top:4mm">Sofia Sans est sous licence SIL Open Font License : libre d'usage, y compris en impression et en document.</p>
		</div>
		<div class="droite">
			<div class="famille lum cadre">
				<span class="nom">Sofia Sans Extra Condensed, 300 : titres, sous-titres, prix</span>
				<span class="grand">Étincelles froides</span>
				<span class="alphabet cond">ABCDEFGHIJKLMNOPQRSTUVWXYZ<br>abcdefghijklmnopqrstuvwxyz<br>0123456789 € % & ! ? « »</span>
			</div>
			<div class="famille lum cadre">
				<span class="nom">Sofia Sans, 400 et 600 : texte courant, intertitres, étiquettes</span>
				<span class="alphabet" style="font-weight:400">ABCDEFGHIJKLMNOPQRSTUVWXYZ abcdefghijklmnopqrstuvwxyz 0123456789</span>
				<span class="alphabet" style="font-weight:600">ABCDEFGHIJKLMNOPQRSTUVWXYZ abcdefghijklmnopqrstuvwxyz 0123456789</span>
			</div>
			<dl class="fiche">
				<div><dt>Hors du site</dt><dd>Dans un document ou un courrier, on installe Sofia Sans. À défaut : Arial Narrow pour les titres, Arial pour le texte.</dd></div>
				<div><dt>Typographie française</dt><dd>Espace insécable avant « : », « ; », « ! », « ? » et « € ». Guillemets français « comme ceci ».</dd></div>
			</dl>
		</div>
	</div>
	<?php echo $el_pied( 11, 'Couleurs et typographie' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</section>

<!-- 12. hiérarchie -->
<section class="page">
	<div class="grille">
		<div>
			<h2>La hiérarchie du texte</h2>
			<p class="discret">Six niveaux suffisent. Les titres sont grands et légers, le texte est sobre : l'œil passe du titre au prix sans effort.</p>
		</div>
		<div class="droite">
			<div class="echelle">
				<div><span class="role"><b>Titre de page</b>Extra Condensed, 300. De 56 à 136 px à l'écran.</span><span class="ex-1">Mapping</span></div>
				<div><span class="role"><b>Sous-titre</b>Extra Condensed, 300. De 32 à 48 px.</span><span class="ex-3">Pack Standard, à partir de 799 €</span></div>
				<div><span class="role"><b>Prix</b>Extra Condensed, 300. De 52 à 92 px.</span><span class="ex-prix">40 €/j</span></div>
				<div><span class="role"><b>Intertitre</b>Sofia Sans, 600.</span><span class="ex-inter">Vous réservez la date</span></div>
				<div><span class="role"><b>Texte courant</b>Sofia Sans, 400, 17 px, 65 caractères par ligne au plus.</span><span class="ex-texte">On monte le son, la lumière et les effets, on reste pendant la soirée, puis on démonte.</span></div>
				<div><span class="role"><b>Étiquette</b>Sofia Sans, 600, 13 px.</span><span class="tags"><span class="tag">Mapping</span> <span class="tag">40 €/j</span> <span class="tag tag-trait">Recommandé</span></span></div>
			</div>
		</div>
	</div>
	<?php echo $el_pied( 12, 'Couleurs et typographie' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</section>

<!-- 13. le trait et les dessins -->
<section class="page">
	<div class="grille">
		<div>
			<h2>Le trait et les dessins</h2>
			<p class="discret">Une seule épaisseur à l'écran : 1,5 px, quelle que soit la taille du dessin. Angles vifs, aucun arrondi, aucune ombre.</p>
			<p class="discret" style="margin-top:4mm">Les pictogrammes et les plans de feu sont dessinés avec le signe : un boîtier, un faisceau.</p>
		</div>
		<div class="droite">
			<ul class="pictos">
				<?php foreach ( el_categories() as $c ) : ?>
				<li><span class="vitrine"><?php echo el_picto( $c['picto'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span><span><?php echo esc_html( $c['nom'] ); ?></span></li>
				<?php endforeach; ?>
			</ul>
			<div class="plans">
				<figure><div class="lit"><?php echo el_plan_de_feu( array( 'son', 'totems-2', 'brouillard' ), array( 'titre' => 'Plan de feu : sono, deux totems, brouillard' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div><figcaption class="legende"><b>Plan de feu simple</b><span>Sono, deux totems, brouillard.</span></figcaption></figure>
				<figure><div class="lit"><?php echo el_plan_de_feu( array( 'son', 'totems-4', 'brouillard', 'etincelles', 'fumee', 'animateur', 'ceremonie' ), array( 'titre' => 'Plan de feu complet d\'un mariage' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div><figcaption class="legende"><b>Plan de feu complet</b><span>Un mariage : cérémonie, soirée, effets.</span></figcaption></figure>
			</div>
		</div>
	</div>
	<?php echo $el_pied( 13, 'Éléments graphiques' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</section>

<!-- 14. la lumière -->
<section class="page">
	<div class="grille">
		<div>
			<h2>La lumière</h2>
			<p class="discret">Éclairer, c'est passer du gris au blanc. Le survol fait office de poursuite : l'élément pointé s'éclaire.</p>
			<p class="discret" style="margin-top:4mm">Un seul mouvement programmé sur tout le site : le faisceau qui s'ouvre.</p>
		</div>
		<div class="droite">
			<div class="composants cadre alu">
				<span class="btn btn-plein">Demander un devis <?php echo el_signe(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				<span class="btn">Voir la location</span>
				<span class="tag">Étiquette</span>
				<span class="tag tag-trait">Étiquette au trait</span>
			</div>
			<div class="etats">
				<div class="etat"><div class="tuile repos"><span>Au repos</span><small>Contour au trait sur le mur.</small></div></div>
				<div class="etat"><div class="tuile eclaire"><span>Éclairé</span><small>Fond blanc : survol, page en cours, pack conseillé, question ouverte.</small></div></div>
				<div class="etat"><div class="tuile plein"><span>Plein</span><small>Fond noir, texte blanc : l'action principale de la page, une seule par écran.</small></div></div>
			</div>
			<dl class="fiche">
				<div><dt>Boutons</dt><dd>Rectangles, angles vifs. Le bouton principal est noir, le secondaire est au trait.</dd></div>
				<div><dt>Thème sombre</dt><dd>« Salle éteinte » : le mur passe à #17191B, le trait devient blanc. Les photos et les vitrines ne changent pas.</dd></div>
			</dl>
		</div>
	</div>
	<?php echo $el_pied( 14, 'Éléments graphiques' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</section>

<!-- 15. images -->
<section class="page">
	<div class="grille">
		<div>
			<h2>Les images</h2>
			<p class="discret">Les images sont des écrans et des vitrines : toujours des rectangles, aucun arrondi, aucun cadre décoratif.</p>
		</div>
		<div class="droite">
			<div class="ecrans">
				<figure>
					<div class="surface ecr"><?php echo el_signe(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
					<figcaption class="legende"><b>Écran noir</b><span>Les réalisations, les vidéos et les visuels : photos de soirée, mapping, captures. Le fond est noir dans les deux thèmes.</span></figcaption>
				</figure>
				<figure>
					<div class="surface lum"><?php echo el_picto( 'projecteurs-robotises' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
					<figcaption class="legende"><b>Vitrine blanche</b><span>Le matériel : objet détouré sur fond blanc, sans ombre. Le fond est blanc dans les deux thèmes.</span></figcaption>
				</figure>
			</div>
			<dl class="fiche">
				<div><dt>Photo de matériel</dt><dd>Objet détouré, centré, sur fond blanc.</dd></div>
				<div><dt>Photo de soirée</dt><dd>Plein cadre sur écran noir. La lumière vient de la photo, on n'ajoute ni filtre ni cadre.</dd></div>
				<div><dt>Visuel sur fond noir</dt><dd>Affiches, mapping, captures : sur écran noir, avec le logo en blanc.</dd></div>
			</dl>
		</div>
	</div>
	<?php echo $el_pied( 15, 'Éléments graphiques' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</section>

<!-- 16. le ton -->
<section class="page">
	<div class="grille">
		<div>
			<h2>Le ton</h2>
			<p class="discret">On parle comme sur un chantier bien tenu : des faits, des prix, des phrases courtes.</p>
			<p class="discret" style="margin-top:4mm">« Vous » pour le client, « on » pour l'équipe.</p>
		</div>
		<div class="droite">
			<div class="ton">
				<div class="titre-col">On écrit</div><div class="titre-col">On évite</div>
				<div>On monte, on assure la soirée, on démonte.</div><div class="discret">Une expérience inoubliable, clé en main, sur mesure.</div>
				<div>4 totems, étincelles froides, animateur : à partir de 799 €.</div><div class="discret">Des prestations haut de gamme à prix compétitifs.</div>
				<div>Lyre, totem, fumée lourde, gabarit : les mots du métier, montrés ou expliqués.</div><div class="discret">Un vocabulaire flou qui ne décrit rien.</div>
				<div>Une phrase, un fait, un prix.</div><div class="discret">Un point d'exclamation, un tiret long, un émoji.</div>
			</div>
		</div>
	</div>
	<?php echo $el_pied( 16, 'Voix' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</section>

<!-- 17. applications -->
<section class="page">
	<div class="grille">
		<div>
			<h2>Applications</h2>
			<p class="discret">Quelques exemples de mise en œuvre, à titre indicatif : mêmes couleurs, même trait, même logo.</p>
		</div>
		<div class="droite">
			<div class="cartes">
				<div class="carte noire"><img class="logo" src="<?php echo $el_l( 'logo', 'fort', 'blanc' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>" alt="Event'Light"></div>
				<div class="carte claire">
					<div><div class="nom">Albéric Delabie</div></div>
					<div>07 81 53 36 00<br>eventlight80@gmail.com<br>www.eventlight.net<br>53 rue du Général Friant, 80000 Amiens</div>
				</div>
			</div>
			<div class="barre"><img class="logo" src="<?php echo $el_l( 'ligne', 'fort', 'noir' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>" alt="Event'Light"><span class="menu"><span>Formules</span><span>Location</span><span>Mapping</span><span>Réalisations</span><span>Contact</span><span class="plein">Demander un devis</span></span></div>
			<div class="courriel lum cadre">
				<strong>Antoine Capel</strong>
				<span class="gris">Éclairage</span>
				<img class="logo" src="<?php echo $el_l( 'ligne', 'courant', 'noir' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>" alt="Event'Light">
				<span>06 46 82 08 31 | eventlight80@gmail.com</span>
				<span>53 rue du Général Friant, 80000 Amiens</span>
				<span>www.eventlight.net</span>
			</div>
		</div>
	</div>
	<?php echo $el_pied( 17, 'Applications' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</section>

<!-- 18. fichiers et contacts -->
<section class="page">
	<div class="grille">
		<div>
			<h2>Fichiers et contacts</h2>
			<p class="discret">Les fichiers d'origine sont dans le dépôt du site. On les reprend tels quels, on ne les redessine pas.</p>
		</div>
		<div class="droite">
			<dl class="fichiers">
				<div><dt>Logos, SVG</dt><dd><code>assets/logo/eventlight-{logo | ligne | signe}-{fin | courant | fort}-{noir | blanc}.svg</code><br>Dix-huit fichiers : trois formes, trois graisses, deux couleurs.</dd></div>
				<div><dt>Logo pour la signature e-mail</dt><dd><code>assets/logo/eventlight-signature.png</code>, 520 px de large, fond blanc.</dd></div>
				<div><dt>Polices</dt><dd><code>assets/fonts/</code> : Sofia Sans et Sofia Sans Extra Condensed, avec leur licence SIL OFL.</dd></div>
				<div><dt>Couleurs</dt><dd>Les valeurs de la page 9, reprises dans la feuille de style du site (<code>assets/css/site.css</code>).</dd></div>
			</dl>
			<div class="contacts">
				<div class="lum cadre"><b>Albéric Delabie</b><span>07 81 53 36 00</span><span>eventlight80@gmail.com</span></div>
				<div class="lum cadre"><b>Antoine Capel</b><span>06 46 82 08 31</span><span>eventlight80@gmail.com</span></div>
			</div>
			<p class="petit discret">Pour toute question sur l'usage de l'identité, ou avant toute impression de grand format, on se parle d'abord.</p>
		</div>
	</div>
	<?php echo $el_pied( 18, 'Fichiers et contacts' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</section>

</main>
<script>
(function () {
	var f = document.getElementById("feuilles");
	function adapter() {
		var largeur = document.documentElement.clientWidth;
		var page = 297 * 96 / 25.4;
		f.style.setProperty("--echelle", String(Math.min(1, (largeur - 24) / page)));
	}
	adapter();
	window.addEventListener("resize", adapter);
	<?php if ( $el_imprimer ) : ?>
	window.addEventListener("load", function () { setTimeout(function () { window.print(); }, 400); });
	<?php endif; ?>
})();
</script>
</body>
</html>
<?php
ob_end_flush();
