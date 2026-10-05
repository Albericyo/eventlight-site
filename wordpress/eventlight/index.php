<?php
/**
 * Gabarit par défaut : articles, archives et résultats de recherche, si le site en publie.
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<div class="wrap">
	<?php if ( is_singular() ) : ?>
		<?php
		while ( have_posts() ) :
			the_post();
			?>
	<header class="page-head">
		<h1><?php the_title(); ?></h1>
		<p class="note"><?php echo esc_html( get_the_date() ); ?></p>
	</header>
	<div class="prose">
			<?php the_content(); ?>
	</div>
		<?php endwhile; ?>
	<?php else : ?>
	<header class="page-head">
		<h1>
			<?php
			if ( is_search() ) {
				echo 'Recherche : ' . esc_html( get_search_query() );
			} elseif ( is_archive() ) {
				echo esc_html( wp_strip_all_tags( get_the_archive_title() ) );
			} else {
				echo 'Actualités';
			}
			?>
		</h1>
	</header>
		<?php if ( have_posts() ) : ?>
	<div class="prose">
			<?php
			while ( have_posts() ) :
				the_post();
				?>
		<h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
		<p class="note"><?php echo esc_html( get_the_date() ); ?></p>
		<p><?php echo esc_html( get_the_excerpt() ); ?></p>
			<?php endwhile; ?>
	</div>
	<div class="actions" style="margin-top: var(--e-6)">
			<?php
			previous_posts_link( 'Plus récent' );
			next_posts_link( 'Plus ancien' );
			?>
	</div>
		<?php else : ?>
	<p class="lede">Rien à afficher ici pour le moment.</p>
		<?php endif; ?>
	<?php endif; ?>
</div>
<?php
get_footer();
