<div class="fl-content-full container">
    <div class="row">
<!-- Row for main content area -->
	<div class="fl-content col-md-12 columns <?php
				if ( is_user_logged_in() ) { } else { echo 'loggedinq';} ?>" id="content qwa" role="main">
		<article <?php post_class() ?> id="post-<?php the_ID(); ?>">
			<header>
				<h1 class="entry-title">New Human Support</h1>
				<h3><?php the_title(); ?></h3>
				<p class="intro">We are here to help. Please <a href="http://www.thenewhuman.com/questions-2/">search the knowledgebase</a> before submitting a ticket.
Chances are, your question has already been asked and answered by someone else.</p>
			</header>
			<div class="entry-content">
				
				<?php
				if ( is_user_logged_in() ) { ?>
				    <?php the_content(); ?>
				<?php } else { ?>
				    
				    <p>You need to be logged in to ask a question or submit a support ticket. Please <a href="https://www.thenewhuman.com/login/">login here</a> or <a href="https://www.thenewhuman.com/register/">register here</a>. </p>
				<?php }
				?>