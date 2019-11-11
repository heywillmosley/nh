<header class="fl-page-header fl-page-nav-centered fl-page-header-primary" itemscope="itemscope" itemtype="http://schema.org/WPHeader">
	<div class="fl-page-header-wrap">
		<div class="fl-page-header-container container">
			<div class="fl-page-header-row row">
				<div class="fl-page-header-logo col-md-12">
					<div class="fl-page-header-logo" itemscope="itemscope" itemtype="http://schema.org/Organization">
						<a href="<?php echo home_url(); ?>" itemprop="url"><?php FLTheme::logo(); ?></a>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="fl-page-nav-wrap">
		<div class="fl-page-nav-container container">
			<nav class="fl-page-nav fl-nav navbar navbar-default" role="navigation" itemscope="itemscope" itemtype="http://schema.org/SiteNavigationElement">
				<button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".fl-page-nav-collapse">
					<span><?php _ex( 'Menu', 'Mobile navigation toggle button text.', 'fl-automator' ); ?></span>
				</button>
				<div class="fl-page-nav-collapse collapse navbar-collapse">
					<?php

					wp_nav_menu(array(
						'theme_location' => 'header',
						'items_wrap' => '<ul id="%1$s" class="nav navbar-nav %2$s">%3$s</ul>',
						'container' => false,
						'fallback_cb' => 'FLTheme::nav_menu_fallback'
					));

					FLTheme::nav_search();

					?>
				</div>
			</nav>
		</div>
	</div>
</header><!-- .fl-page-header -->