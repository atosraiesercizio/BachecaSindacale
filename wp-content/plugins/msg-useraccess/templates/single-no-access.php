<?php
/**
 * Template per contenuti a cui l'utente NON può accedere
 */

get_header();
 ?>
	<div id="primary" class="content-area">
		<main id="main" class="site-main" role="main">

		<?php
		// Start the loop.
		while ( have_posts() ) : the_post();

			?>
			<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
			<header class="entry-header">
				<h1 class="entry-title">Accesso riservato</h1>
			</header><!-- .entry-header -->
		
			<div class="entry-content">
				<p>Non hai i permessi per visualizzare questo contenuto.<br>Torna alla <a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a></p>
			</div><!-- .entry-content -->
		</article><!-- #post-## -->
		<?php
		// End the loop.
		endwhile;
		?>
		
		</main><!-- .site-main -->
	</div><!-- .content-area -->
<?php get_footer(); ?>