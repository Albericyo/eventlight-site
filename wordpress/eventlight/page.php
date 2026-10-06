<?php
/**
 * Une page ordinaire, écrite dans l'éditeur de WordPress.
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<div class="wrap">
	<header class="page-head">
		<?php el_fil(); ?>
		<h1><?php the_title(); ?></h1>
	</header>

	<div class="prose">
		<?php
		while ( have_posts() ) {
			the_post();
			the_content();
		}
		?>
	</div>
</div>
<?php
get_footer();
