<?php

/* Default */
FLCustomizer::add_preset('default', array(
	'name'      => 'Default',
	'skin'      => 'default',
	'settings'  => array()
));

/* Default Dark */
FLCustomizer::add_preset('default-dark', array(
	'name'      => 'Default Dark',
	'skin'      => 'default',
	'settings'  => array(
		'fl-accent'                 => '#95bf48',
		'fl-heading-text-color'     => '#95bf48',
		'fl-topbar-bg-type'         => 'custom',
		'fl-topbar-bg-color'        => '#3e4147',
		'fl-header-bg-type'         => 'custom',
		'fl-header-bg-color'        => '#282a2e'
	)
));

/* Classic */
FLCustomizer::add_preset('classic', array(
	'name'      => 'Classic',
	'skin'      => 'default',
	'settings'  => array(
		'fl-layout-width'              => 'boxed',
		'fl-layout-spacing'            => '30',
		'fl-layout-shadow-size'        => '15',
		'fl-layout-shadow-color'       => '#d9d9d9',
		'fl-accent'                    => '#483182',
		'fl-body-bg-color'             => '#efefe9',
		'fl-header-layout'             => 'bottom',
		'fl-nav-bg-type'               => 'custom',
		'fl-nav-bg-color'              => '#483182'
	)
));

/* Modern */
FLCustomizer::add_preset('modern', array(
	'name'      => 'Modern',
	'skin'      => 'modern',
	'settings'  => array(
		'fl-layout-width'              => 'boxed',
		'fl-accent'                    => '#cf6713',
		'fl-topbar-bg-type'            => 'custom',
		'fl-topbar-bg-color'           => '#333333',
		'fl-topbar-bg-gradient'        => '1',
		'fl-header-layout'             => 'bottom',
		'fl-nav-bg-type'               => 'custom',
		'fl-nav-bg-color'              => '#fafafa',
		'fl-nav-bg-gradient'           => '1',
		'fl-footer-bg-type'            => 'custom',
		'fl-footer-bg-color'           => '#333333',
		'fl-footer-widgets-bg-type'    => 'custom',
		'fl-footer-widgets-bg-color'   => '#fafafa'
	)
));

/* Bold */
FLCustomizer::add_preset('bold', array(
	'name'      => 'Bold',
	'skin'      => 'default',
	'settings'  => array(
		'fl-topbar-bg-type'            => 'custom',
		'fl-topbar-bg-color'           => '#326796',
		'fl-header-layout'             => 'bottom',
		'fl-header-bg-type'            => 'custom',
		'fl-header-bg-color'           => '#326796',
		'fl-header-bg-gradient'        => '1',
		'fl-nav-bg-type'               => 'custom',
		'fl-nav-bg-color'              => '#428bca',
		'fl-footer-bg-type'            => 'custom',
		'fl-footer-bg-color'           => '#428bca',
		'fl-footer-widgets-bg-type'    => 'custom',
		'fl-footer-widgets-bg-color'   => '#326796'
	)
));

/* Stripe */
FLCustomizer::add_preset('stripe', array(
	'name'      => 'Stripe',
	'skin'      => 'default',
	'settings'  => array(
		'fl-body-bg-color'             => '#e6e6e6',
		'fl-topbar-bg-type'            => 'custom',
		'fl-topbar-bg-color'           => '#fafafa',
		'fl-header-layout'             => 'bottom',
		'fl-nav-bg-type'               => 'custom',
		'fl-nav-bg-color'              => '#385f82',
		'fl-footer-bg-type'            => 'custom',
		'fl-footer-bg-color'           => '#385f82',
		'fl-footer-widgets-bg-type'    => 'custom',
		'fl-footer-widgets-bg-color'   => '#fafafa'
	)
));

/* Deluxe */
FLCustomizer::add_preset('deluxe', array(
	'name'      => 'Deluxe',
	'skin'      => 'deluxe',
	'settings'  => array(
		'fl-accent'                    => '#657f8c',
		'fl-body-bg-color'             => '#efefe9',
		'fl-topbar-bg-type'            => 'custom',
		'fl-topbar-bg-color'           => '#657f8c',
		'fl-topbar-bg-gradient'        => '1',
		'fl-header-bg-type'            => 'custom',
		'fl-header-bg-color'           => '#1f1f1f',
		'fl-header-bg-gradient'        => '1',
		'fl-header-layout'             => 'centered',
		'fl-nav-bg-type'               => 'custom',
		'fl-nav-bg-color'              => '#333333',
		'fl-nav-bg-gradient'           => '1',
		'fl-footer-bg-type'            => 'custom',
		'fl-footer-bg-color'           => '#657f8c',
		'fl-footer-widgets-bg-type'    => 'custom',
		'fl-footer-widgets-bg-color'   => '#1f1f1f'
		
	)
));

/* Premier */
FLCustomizer::add_preset('premier', array(
	'name'      => 'Premier',
	'skin'      => 'premier',
	'settings'  => array(
		'fl-accent'                    => '#319753',
		'fl-body-bg-color'             => '#262626',
		'fl-topbar-bg-type'            => 'none',
		'fl-header-bg-type'            => 'custom',
		'fl-header-bg-color'           => '#262626',
		'fl-header-bg-gradient'        => '1',
		'fl-header-layout'             => 'bottom',
		'fl-nav-bg-type'               => 'custom',
		'fl-nav-bg-color'              => '#319753',
		'fl-nav-bg-gradient'           => '1',
		'fl-footer-bg-type'            => 'none',
		'fl-footer-widgets-bg-type'    => 'none'
	)
));

/* Dusk */
FLCustomizer::add_preset('dusk', array(
	'name'      => 'Dusk',
	'skin'      => 'premier',
	'settings'  => array(
		'fl-layout-width'              => 'boxed',
		'fl-accent'                    => '#cc3f26',
		'fl-heading-text-color'        => '#e6e6e6',
		'fl-body-bg-color'             => '#1a1a1a',
		'fl-body-text-color'           => '#999999',
		'fl-topbar-bg-type'            => 'custom',
		'fl-topbar-bg-color'           => '#262626',
		'fl-header-bg-type'            => 'none',
		'fl-header-layout'             => 'bottom',
		'fl-nav-bg-type'               => 'custom',
		'fl-nav-bg-color'              => '#cc3f26',
		'fl-nav-bg-gradient'           => '1',
		'fl-content-bg-color'          => '#262626',
		'fl-footer-bg-type'            => 'none',
		'fl-footer-widgets-bg-type'    => 'none'
	)
));

/* Midnight */
FLCustomizer::add_preset('midnight', array(
	'name'      => 'Midnight',
	'skin'      => 'modern',
	'settings'  => array(
		'fl-layout-width'              => 'boxed',
		'fl-heading-text-color'        => '#e6e6e6',
		'fl-body-bg-color'             => '#1a1a1a',
		'fl-body-text-color'           => '#999999',
		'fl-topbar-bg-type'            => 'none',
		'fl-header-bg-type'            => 'custom',
		'fl-header-bg-color'           => '#262626',
		'fl-header-bg-gradient'        => '1',
		'fl-nav-bg-type'               => 'none',
		'fl-content-bg-color'          => '#262626',
		'fl-footer-bg-type'            => 'none',
		'fl-footer-widgets-bg-type'    => 'none'
	)
));