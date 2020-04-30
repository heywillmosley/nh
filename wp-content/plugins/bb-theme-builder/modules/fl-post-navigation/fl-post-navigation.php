<?php

/**
 * @since 1.0
 * @class FLPostNavigationModule
 */
class FLPostNavigationModule extends FLBuilderModule {

	/**
	 * @method __construct
	 */
	public function __construct() {
		parent::__construct(array(
			'name'            => __( 'Post Navigation', 'bb-theme-builder' ),
			'description'     => __( 'Displays the next / previous post navigation links.', 'bb-theme-builder' ),
			'group'           => __( 'Themer Modules', 'bb-theme-builder' ),
			'category'        => __( 'Posts', 'bb-theme-builder' ),
			'partial_refresh' => true,
			'dir'             => FL_THEME_BUILDER_DIR . 'modules/fl-post-navigation/',
			'url'             => FL_THEME_BUILDER_URL . 'modules/fl-post-navigation/',
			'enabled'         => FLThemeBuilderLayoutData::current_post_is( 'singular' ),
		));
	}
}

FLBuilder::register_module( 'FLPostNavigationModule', array(
	'general' => array(
		'title'    => __( 'Settings', 'bb-theme-builder' ),
		'sections' => array(
			'general' => array(
				'title'  => '',
				'fields' => array(
					'prev_text'    => array(
						'type'    => 'text',
						'label'   => __( 'Previous Text', 'bb-theme-builder' ),
						'default' => '&larr; %title',
					),
					'next_text'    => array(
						'type'    => 'text',
						'label'   => __( 'Next Text', 'bb-theme-builder' ),
						'default' => '%title &rarr;',
					),
					'in_same_term' => array(
						'type'    => 'select',
						'label'   => __( 'Navigate in same taxonomy', 'bb-theme-builder' ),
						'default' => '0',
						'help'    => __( 'Whether to navigate in the same taxonomy as the current post or not.', 'bb-theme-builder' ),
						'toggle'  => array(
							'1' => array(
								'fields' => array( 'tax_select' ),
							),
						),
						'options' => array(
							'1' => __( 'Enable', 'bb-theme-builder' ),
							'0' => __( 'Disable', 'bb-theme-builder' ),
						),
					),
					'tax_select'   => array(
						'type'    => 'text',
						'size'    => 16,
						'label'   => __( 'Taxonomy', 'bb-theme-builder' ),
						'default' => 'category',
						'help'    => __( 'The default taxonomy is category.', 'bb-theme-builder' ),
					),
				),
			),
		),
	),
) );
