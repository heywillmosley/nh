<?php

/* Footer Panel */
FLCustomizer::add_panel('fl-footer', array(
	'title'    => _x( 'Footer', 'Customizer panel title.', 'fl-automator' ),
	'sections' => array(

		/* Footer Widgets Layout Section */
		'fl-footer-widgets-layout' => array(
			'title'   => _x( 'Footer Widgets Layout', 'Customizer section title.', 'fl-automator' ),
			'options' => array(

				/* Footer Widgets Display */
				'fl-footer-widgets-display' => array(
					'setting'   => array(
						'default'   => 'all'
					),
					'control'   => array(
						'class'         => 'WP_Customize_Control',
						'label'         => __('Footer Widgets Display', 'fl-automator'),
						'type'          => 'select',
						'choices'       => array(
							'disabled'          => __('Disabled', 'fl-automator'),
							'all'               => __('All Pages', 'fl-automator'),
							'home'              => __('Homepage Only', 'fl-automator')
						)
					)
				)
			)
		),

		/* Footer Widgets Background Section */
		'fl-footer-widgets-background' => array(
			'title'   => _x( 'Footer Widgets Background', 'Customizer section title.', 'fl-automator' ),
			'options' => array(

				/* Footer Widgets Background Type */
				'fl-footer-widgets-bg-type' => array(
					'setting'   => array(
						'default'   => 'content'
					),
					'control'   => array(
						'class'         => 'WP_Customize_Control',
						'label'         => __('Footer Widgets Background Type', 'fl-automator'),
						'type'          => 'select',
						'choices'       => array(
							'none'          => __('None', 'fl-automator'),
							'content'       => __('Same as Content', 'fl-automator'),
							'custom'        => __('Custom', 'fl-automator')
						)
					)
				),

				/* Footer Widgets Background Color */
				'fl-footer-widgets-bg-color' => array(
					'setting'   => array(
						'default'   => ''
					),
					'control'   => array(
						'class'     => 'WP_Customize_Color_Control',
						'label'     => __('Footer Widgets Background Color', 'fl-automator')
					)
				)
			)
		),

		/* Footer Layout Section */
		'fl-footer-layout' => array(
			'title'   => _x( 'Footer Layout', 'Customizer section title.', 'fl-automator' ),
			'options' => array(

				/* Footer Layout */
				'fl-footer-layout' => array(
					'setting'   => array(
						'default'   => '1-col'
					),
					'control'   => array(
						'class'         => 'WP_Customize_Control',
						'label'         => __('Footer Layout', 'fl-automator'),
						'type'          => 'select',
						'choices'       => array(
							'none'          => __('None', 'fl-automator'),
							'1-col'         => __('1 Column', 'fl-automator'),
							'2-cols'        => __('2 Columns', 'fl-automator')
						)
					)
				),

				/* Line */
				'fl-footer-line1' => array(
					'control'   => array(
						'class'         => 'FLCustomizerControl',
						'type'          => 'line'
					)
				),

				/* Footer Column 1 Layout */
				'fl-footer-col1-layout' => array(
					'setting'   => array(
						'default'   => 'text'
					),
					'control'   => array(
						'class'   => 'WP_Customize_Control',
						'label'   => sprintf( _x( 'Footer Column %d Layout', '%d stands for column order number.', 'fl-automator' ), 1 ),
						'type'    => 'select',
						'choices' => array(
							'text'        => __('Text', 'fl-automator'),
							'social'      => __('Social Icons', 'fl-automator'),
							'social-text' => __('Text &amp; Social Icons', 'fl-automator'),
							'menu'        => __('Menu', 'fl-automator')
						)
					)
				),

				/* Footer Column 1 Text */
				'fl-footer-col1-text' => array(
					'setting'   => array(
						'default'   => '',
						'transport' => 'postMessage'
					),
					'control'   => array(
						'class' => 'WP_Customize_Control',
						'label' => sprintf( _x( 'Footer Column %d Text', '%d stands for column order number.', 'fl-automator' ), 1 ),
						'type'  => 'textarea'
					)
				),

				/* Line */
				'fl-footer-line2' => array(
					'control'   => array(
						'class'         => 'FLCustomizerControl',
						'type'          => 'line'
					)
				),

				/* Footer Column 2 Layout */
				'fl-footer-col2-layout' => array(
					'setting'   => array(
						'default'   => 'text'
					),
					'control'   => array(
						'class'   => 'WP_Customize_Control',
						'label'   => sprintf( _x( 'Footer Column %d Layout', '%d stands for column order number.', 'fl-automator' ), 2 ),
						'type'    => 'select',
						'choices' => array(
							'text'        => __('Text', 'fl-automator'),
							'social'      => __('Social Icons', 'fl-automator'),
							'social-text' => __('Text &amp; Social Icons', 'fl-automator'),
							'menu'        => __('Menu', 'fl-automator')
						)
					)
				),

				/* Footer Column 2 Text */
				'fl-footer-col2-text' => array(
					'setting'   => array(
						'default'   => '1-800-555-5555 &bull; <a href="mailto:info@mydomain.com">info@mydomain.com</a>',
						'transport' => 'postMessage'
					),
					'control'   => array(
						'class' => 'WP_Customize_Control',
						'label' => sprintf( _x( 'Footer Column %d Text', '%d stands for column order number.', 'fl-automator' ), 2 ),
						'type'  => 'textarea'
					)
				)
			)
		),

		/* Footer Background Section */
		'fl-footer-background' => array(
			'title'   => _x( 'Footer Background', 'Customizer section title.', 'fl-automator' ),
			'options' => array(

				/* Footer Background Type */
				'fl-footer-bg-type' => array(
					'setting'   => array(
						'default'   => 'content'
					),
					'control'   => array(
						'class'         => 'WP_Customize_Control',
						'label'         => __('Footer Background Type', 'fl-automator'),
						'type'          => 'select',
						'choices'       => array(
							'none'          => __('None', 'fl-automator'),
							'content'       => __('Same as Content', 'fl-automator'),
							'custom'        => __('Custom', 'fl-automator')
						)
					)
				),

				/* Footer Background Color */
				'fl-footer-bg-color' => array(
					'setting'   => array(
						'default'   => ''
					),
					'control'   => array(
						'class'     => 'WP_Customize_Color_Control',
						'label'     => __('Footer Background Color', 'fl-automator')
					)
				)
			)
		)
	)
));