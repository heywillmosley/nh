<?php get_header(); ?>

<div class="container">
	<div class="row">
        <div class="col" style="max-width: 700px; margin: 0 auto">
            <?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>
    				<?php get_template_part( 'content', 'single' ); ?>
    			<?php endwhile;
    endif; ?>
        </div>
	</div>
</div>

<?php get_footer(); ?>
