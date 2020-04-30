<?php
if ( tribe_get_option( 'views_v2_enabled' ) ) {
	if ( is_post_type_archive( 'tribe_events' ) ) {
			echo tribe( \Tribe\Events\Views\V2\Template_Bootstrap::class )->get_view_html();
	}
} else {
	include 'frontend-legacy.php';
}
