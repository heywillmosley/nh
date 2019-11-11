<?php

/* Content Panel */
FLCustomizer::add_panel('fl-content', array(
	'title'    => _x( 'Content', 'Customizer panel title.', 'fl-automator' ),
	'sections' => array(

		/* Content Background Section */
		'fl-content-bg' => array(
			'title'   => _x( 'Content Background', 'Customizer section title.', 'fl-automator' ),
			'options' => array(

				/* Content Background Color */
				'fl-content-bg-color' => array(
					'setting'   => array(
						'default'   => 'ffffff'
					),
					'control'   => array(
						'class'         => 'WP_Customize_Color_Control',
						'label'         => __('Content Background Color', 'fl-automator')
					)
				)
			)
		),

		/* Blog Section */
		'fl-content-blog' => array(
			'title'   => _x( 'Blog Layout', 'Customizer section title.', 'fl-automator' ),
			'options' => array(

				/* Blog Layout */
				'fl-blog-layout' => array(
					'setting'   => array(
						'default'   => 'sidebar-right'
					),
					'control'   => array(
						'class'         => 'WP_Customize_Control',
						'label'         => __('Blog Sidebar Position', 'fl-automator'),
						'type'          => 'select',
						'choices'       => array(
							'sidebar-right'     => __('Sidebar Right', 'fl-automator'),
							'sidebar-left'      => __('Sidebar Left', 'fl-automator'),
							'no-sidebar'        => __('No Sidebar', 'fl-automator')
						)
					)
				),

				/* Blog Sidebar Size */
				'fl-blog-sidebar-size' => array(
					'setting'   => array(
						'default'   => '4'
					),
					'control'   => array(
						'class'         => 'WP_Customize_Control',
						'label'         => __('Blog Sidebar Size', 'fl-automator'),
						'type'          => 'select',
						'choices'       => array(
							'4' => _x( 'Large', 'Sidebar size.', 'fl-automator' ),
							'3' => _x( 'Medium', 'Sidebar size.', 'fl-automator' ),
							'2' => _x( 'Small', 'Sidebar size.', 'fl-automator' )
						)
					)
				),

				/* Blog Sidebar Display */
				'fl-blog-sidebar-display' => array(
					'setting'   => array(
						'default'   => 'desktop'
					),
					'control'   => array(
						'class'         => 'WP_Customize_Control',
						'label'         => __('Blog Sidebar Display', 'fl-automator'),
						'type'          => 'select',
						'choices'       => array(
							'desktop'       => __('Desktop Only', 'fl-automator'),
							'always'        => __('Always', 'fl-automator')
						)
					)
				),

				/* Line */
				'fl-blog-line1' => array(
					'control'   => array(
						'class'     => 'FLCustomizerControl',
						'type'      => 'line'
					)
				),

				/* Post Author */
				'fl-blog-post-author' => array(
					'setting'   => array(
						'default'   => 'visible'
					),
					'control'   => array(
						'class'         => 'WP_Customize_Control',
						'label'         => __('Post Author', 'fl-automator'),
						'type'          => 'select',
						'choices'       => array(
							'visible'           => __('Visible', 'fl-automator'),
							'hidden'            => __('Hidden', 'fl-automator')
						)
					)
				),

				/* Post Date */
				'fl-blog-post-date' => array(
					'setting'   => array(
						'default'   => 'visible'
					),
					'control'   => array(
						'class'         => 'WP_Customize_Control',
						'label'         => __('Post Date', 'fl-automator'),
						'type'          => 'select',
						'choices'       => array(
							'visible'           => __('Visible', 'fl-automator'),
							'hidden'            => __('Hidden', 'fl-automator')
						)
					)
				)
			)
		),

		/* Archive Pages Section */
		'fl-content-archives' => array(
			'title'   => _x( 'Archive Layout', 'Customizer section title.', 'fl-automator' ),
			'options' => array(

				/* Show Full Text */
				'fl-archive-show-full' => array(
					'setting'   => array(
						'default'   => '0'
					),
					'control'   => array(
						'class'         => 'WP_Customize_Control',
						'label'         => __('Show Full Text', 'fl-automator'),
						'description'   => __('Whether or not to show the full post. If no, the excerpt will be shown.', 'fl-automator'),
						'type'          => 'select',
						'choices'       => array(
							'1'                 => __('Yes', 'fl-automator'),
							'0'                 => __('No', 'fl-automator')
						)
					)
				),

				/* Read More Text */
				'fl-archive-readmore-text' => array(
					'setting'   => array(
						'default'           => __('Read More', 'fl-automator'),
					),
					'control'   => array(
						'class' => 'WP_Customize_Control',
						'label' => __( '"Read More" Text', 'fl-automator' ),
						'type'  => 'text'
					)
				),

				/* Featured Image */
				'fl-archive-show-thumbs' => array(
					'setting'   => array(
						'default'   => 'beside'
					),
					'control'   => array(
						'class'         => 'WP_Customize_Control',
						'label'         => __('Featured Image', 'fl-automator'),
						'type'          => 'select',
						'choices'       => array(
							''                  => __('Hidden', 'fl-automator'),
							'above'             => __('Above Posts', 'fl-automator'),
							'beside'            => __('Beside Posts', 'fl-automator')
						)
					)
				)
			)
		),

		/* Post Pages Section */
		'fl-content-posts' => array(
			'title'   => _x( 'Post Layout', 'Customizer section title.', 'fl-automator' ),
			'options' => array(

				/* Featured Image */
				'fl-posts-show-thumbs' => array(
					'setting'   => array(
						'default'   => ''
					),
					'control'   => array(
						'class'         => 'WP_Customize_Control',
						'label'         => __('Featured Image', 'fl-automator'),
						'type'          => 'select',
						'choices'       => array(
							''                  => __('Hidden', 'fl-automator'),
							'above'             => __('Above Post', 'fl-automator'),
							'beside'            => __('Beside Post', 'fl-automator')
						)
					)
				),

				/* Post Categories */
				'fl-posts-show-cats' => array(
					'setting'   => array(
						'default'   => 'visible'
					),
					'control'   => array(
						'class'         => 'WP_Customize_Control',
						'label'         => __('Post Categories', 'fl-automator'),
						'type'          => 'select',
						'choices'       => array(
							'visible'           => __('Visible', 'fl-automator'),
							'hidden'            => __('Hidden', 'fl-automator')
						)
					)
				),

				/* Post Tags */
				'fl-posts-show-tags' => array(
					'setting'   => array(
						'default'   => 'visible'
					),
					'control'   => array(
						'class'         => 'WP_Customize_Control',
						'label'         => __('Post Tags', 'fl-automator'),
						'type'          => 'select',
						'choices'       => array(
							'visible'           => __('Visible', 'fl-automator'),
							'hidden'            => __('Hidden', 'fl-automator')
						)
					)
				),

				/* Prev/Next Post Links */
				'fl-posts-show-nav' => array(
					'setting'   => array(
						'default'   => 'hidden'
					),
					'control'   => array(
						'class'         => 'WP_Customize_Control',
						'label'         => __('Prev/Next Post Links', 'fl-automator'),
						'type'          => 'select',
						'choices'       => array(
							'visible'           => __('Visible', 'fl-automator'),
							'hidden'            => __('Hidden', 'fl-automator')
						)
					)
				),
			)
		),

		/* WooCommerce Section */
		'fl-content-woo' => array(
			'title'   => _x( 'WooCommerce Layout', 'Customizer section title.', 'fl-automator' ),
			'options' => array(

				/* WooCommerce Layout */
				'fl-woo-layout' => array(
					'setting'   => array(
						'default'   => 'no-sidebar'
					),
					'control'   => array(
						'class'         => 'WP_Customize_Control',
						'label'         => __('WooCommerce Sidebar Position', 'fl-automator'),
						'description'   => __('The location of the WooCommerce sidebar.', 'fl-automator'),
						'type'          => 'select',
						'choices'       => array(
							'sidebar-right'     => __('Sidebar Right', 'fl-automator'),
							'sidebar-left'      => __('Sidebar Left', 'fl-automator'),
							'no-sidebar'        => __('No Sidebar', 'fl-automator')
						)
					)
				),

				/* WooCommerce Sidebar Size */
				'fl-woo-sidebar-size' => array(
					'setting'   => array(
						'default'   => '4'
					),
					'control'   => array(
						'class'         => 'WP_Customize_Control',
						'label'         => __('WooCommerce Sidebar Size', 'fl-automator'),
						'type'          => 'select',
						'choices'       => array(
							'4' => _x( 'Large', 'Sidebar size.', 'fl-automator' ),
							'3' => _x( 'Medium', 'Sidebar size.', 'fl-automator' ),
							'2' => _x( 'Small', 'Sidebar size.', 'fl-automator' )
						)
					)
				),

				/* WooCommerce Sidebar Display */
				'fl-woo-sidebar-display' => array(
					'setting'   => array(
						'default'   => 'desktop'
					),
					'control'   => array(
						'class'         => 'WP_Customize_Control',
						'label'         => __('WooCommerce Sidebar Display', 'fl-automator'),
						'type'          => 'select',
						'choices'       => array(
							'desktop'       => __('Desktop Only', 'fl-automator'),
							'always'        => __('Always', 'fl-automator')
						)
					)
				),

				/* Line */
				'fl-woo-line1' => array(
					'control'   => array(
						'class'     => 'FLCustomizerControl',
						'type'      => 'line'
					)
				),

				/* Add to Cart Button */
				'fl-woo-cart-button' => array(
					'setting'   => array(
						'default'   => 'hidden'
					),
					'control'   => array(
						'class'         => 'WP_Customize_Control',
						'label'         => __( '"Add to Cart" Button', 'fl-automator' ),
						'description'   => __( 'Show the "Add to Cart" button on product category pages?', 'fl-automator' ),
						'type'          => 'select',
						'choices'       => array(
							'visible'           => __('Visible', 'fl-automator'),
							'hidden'            => __('Hidden', 'fl-automator')
						)
					)
				)
			)
		),

		/* Lightbox Section */
		'fl-lightbox-layout' => array(
			'title'   => _x( 'Lightbox', 'Customizer section title.', 'fl-automator' ),
			'options' => array(

				/* Lightbox */
				'fl-lightbox' => array(
					'setting'   => array(
						'default'   => 'enabled'
					),
					'control'   => array(
						'class'         => 'WP_Customize_Control',
						'label'         => __('Lightbox', 'fl-automator'),
						'type'          => 'select',
						'choices'       => array(
							'enabled'           => __('Enabled', 'fl-automator'),
							'disabled'          => __('Disabled', 'fl-automator')
						)
					)
				)
			)
		),
	)
));