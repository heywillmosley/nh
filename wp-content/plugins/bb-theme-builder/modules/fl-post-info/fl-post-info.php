<?php

/**
 * @class FLPostInfoModule
 */
class FLPostInfoModule extends FLBuilderModule {

	/**
	 * @method __construct
	 */
	public function __construct() {
		parent::__construct(array(
			'name'            => __( 'Post Info', 'bb-theme-builder' ),
			'description'     => __( 'Displays meta information for a post.', 'bb-theme-builder' ),
			'group'           => __( 'Themer Modules', 'bb-theme-builder' ),
			'category'        => __( 'Posts', 'bb-theme-builder' ),
			'partial_refresh' => true,
			'dir'             => FL_THEME_BUILDER_DIR . 'modules/fl-post-info/',
			'url'             => FL_THEME_BUILDER_URL . 'modules/fl-post-info/',
			'enabled'         => FLThemeBuilderLayoutData::current_post_is( 'singular' ),
		));
	}
}

/**
 * Register the module and its form settings.
 */
FLBuilder::register_module( 'FLPostInfoModule', array(
	'general' => array(
		'title'    => __( 'General', 'bb-theme-builder' ),
		'sections' => array(
			'date'          => array(
				'title'  => __( 'Date', 'bb-theme-builder' ),
				'fields' => array(
					'show_date'   => array(
						'type'    => 'select',
						'label'   => __( 'Date', 'bb-theme-builder' ),
						'default' => '1',
						'options' => array(
							'1' => __( 'Show', 'bb-theme-builder' ),
							'0' => __( 'Hide', 'bb-theme-builder' ),
						),
					),
					'date_format' => array(
						'type'    => 'select',
						'label'   => __( 'Date Format', 'bb-theme-builder' ),
						'default' => '',
						'options' => array(
							''       => __( 'Default', 'bb-theme-builder' ),
							'M j, Y' => gmdate( 'M j, Y' ),
							'F j, Y' => gmdate( 'F j, Y' ),
							'm/d/Y'  => gmdate( 'm/d/Y' ),
							'm-d-Y'  => gmdate( 'm-d-Y' ),
							'd M Y'  => gmdate( 'd M Y' ),
							'd F Y'  => gmdate( 'd F Y' ),
							'Y-m-d'  => gmdate( 'Y-m-d' ),
							'Y/m/d'  => gmdate( 'Y/m/d' ),
						),
					),
					'date_prefix' => array(
						'type'    => 'text',
						'label'   => __( 'Prefix', 'bb-theme-builder' ),
						'default' => '',
					),
				),
			),
			'modified_date' => array(
				'title'  => __( 'Modified Date', 'bb-theme-builder' ),
				'fields' => array(
					'show_modified_date'   => array(
						'type'    => 'select',
						'label'   => __( 'Modified Date', 'bb-theme-builder' ),
						'default' => '0',
						'options' => array(
							'1' => __( 'Show', 'bb-theme-builder' ),
							'0' => __( 'Hide', 'bb-theme-builder' ),
						),
					),
					'modified_date_format' => array(
						'type'    => 'select',
						'label'   => __( 'Modified Date Format', 'bb-theme-builder' ),
						'default' => '',
						'options' => array(
							''       => __( 'Default', 'bb-theme-builder' ),
							'M j, Y' => gmdate( 'M j, Y' ),
							'F j, Y' => gmdate( 'F j, Y' ),
							'm/d/Y'  => gmdate( 'm/d/Y' ),
							'm-d-Y'  => gmdate( 'm-d-Y' ),
							'd M Y'  => gmdate( 'd M Y' ),
							'd F Y'  => gmdate( 'd F Y' ),
							'Y-m-d'  => gmdate( 'Y-m-d' ),
							'Y/m/d'  => gmdate( 'Y/m/d' ),
							'human'  => __( '3 days ago', 'bb-theme-builder' ),
						),
					),
					'modified_date_prefix' => array(
						'type'    => 'text',
						'label'   => __( 'Prefix', 'bb-theme-builder' ),
						'default' => __( 'Last Updated&nbsp;', 'bb-theme-builder' ),
					),
				),
			),
			'author'        => array(
				'title'  => __( 'Author', 'bb-theme-builder' ),
				'fields' => array(
					'show_author' => array(
						'type'    => 'select',
						'label'   => __( 'Author', 'bb-theme-builder' ),
						'default' => '1',
						'options' => array(
							'1' => __( 'Show', 'bb-theme-builder' ),
							'0' => __( 'Hide', 'bb-theme-builder' ),
						),
					),
				),
			),
			'comments'      => array(
				'title'  => __( 'Comments', 'bb-theme-builder' ),
				'fields' => array(
					'show_comments' => array(
						'type'    => 'select',
						'label'   => __( 'Comments', 'bb-theme-builder' ),
						'default' => '1',
						'options' => array(
							'1' => __( 'Show', 'bb-theme-builder' ),
							'0' => __( 'Hide', 'bb-theme-builder' ),
						),
					),
					'none_text'     => array(
						'type'    => 'text',
						'label'   => __( 'No Comments Text', 'bb-theme-builder' ),
						'default' => __( 'No Comments', 'bb-theme-builder' ),
					),
					'one_text'      => array(
						'type'    => 'text',
						'label'   => __( 'One Comment Text', 'bb-theme-builder' ),
						'default' => __( '1 Comment', 'bb-theme-builder' ),
					),
					'more_text'     => array(
						'type'    => 'text',
						'label'   => __( 'Comments Text', 'bb-theme-builder' ),
						'default' => __( '% Comments', 'bb-theme-builder' ),
					),
				),
			),
			'terms'         => array(
				'title'  => __( 'Terms', 'bb-theme-builder' ),
				'fields' => array(
					'show_terms'      => array(
						'type'    => 'select',
						'label'   => __( 'Terms', 'bb-theme-builder' ),
						'default' => '1',
						'options' => array(
							'1' => __( 'Show', 'bb-theme-builder' ),
							'0' => __( 'Hide', 'bb-theme-builder' ),
						),
					),
					'terms_taxonomy'  => array(
						'type'    => 'select',
						'label'   => __( 'Taxonomy', 'bb-theme-builder' ),
						'default' => 'category',
						'options' => FLPageDataPost::get_taxonomy_options(),
					),
					'terms_separator' => array(
						'type'    => 'text',
						'label'   => __( 'Separator', 'bb-theme-builder' ),
						'default' => __( ', ', 'bb-theme-builder' ),
						'size'    => '4',
					),
				),
			),
		),
	),
	'style'   => array(
		'title'    => __( 'Style', 'bb-theme-builder' ),
		'sections' => array(
			'general' => array(
				'title'  => '',
				'fields' => array(
					'align'      => array(
						'type'    => 'select',
						'label'   => __( 'Alignment', 'bb-theme-builder' ),
						'default' => 'left',
						'options' => array(
							'left'   => __( 'Left', 'bb-theme-builder' ),
							'center' => __( 'Center', 'bb-theme-builder' ),
							'right'  => __( 'Right', 'bb-theme-builder' ),
						),
						'preview' => array(
							'type'     => 'css',
							'selector' => '.fl-module-content',
							'property' => 'text-align',
						),
					),
					'font_size'  => array(
						'type'        => 'text',
						'label'       => __( 'Font Size', 'bb-theme-builder' ),
						'default'     => '',
						'maxlength'   => '3',
						'size'        => '4',
						'description' => 'px',
					),
					'text_color' => array(
						'type'       => 'color',
						'label'      => __( 'Color', 'bb-theme-builder' ),
						'show_reset' => true,
					),
					'separator'  => array(
						'type'    => 'text',
						'label'   => __( 'Separator', 'bb-theme-builder' ),
						'default' => ' | ',
						'size'    => '4',
						'preview' => array(
							'type'     => 'text',
							'selector' => '.fl-post-info-sep',
						),
					),
				),
			),
		),
	),
	'order'   => array(
		'title'    => __( 'Order', 'bb-theme-builder' ),
		'sections' => array(
			'general' => array(
				'title'  => '',
				'fields' => array(
					'order' => array(
						'type'    => 'ordering',
						'label'   => '',
						'default' => array( 'date', 'modified_date', 'author', 'comments', 'terms' ),
						'options' => array(
							'date'          => __( 'Date', 'bb-theme-builder' ),
							'modified_date' => __( 'Modified Date', 'bb-theme-builder' ),
							'author'        => __( 'Author', 'bb-theme-builder' ),
							'comments'      => __( 'Comments', 'bb-theme-builder' ),
							'terms'         => __( 'Terms', 'bb-theme-builder' ),
						),
					),
				),
			),
		),
	),
));
