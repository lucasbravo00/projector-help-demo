<?php
/**
 * WordPress Playground setup for a Projector demo site.
 * Runs from the blueprint's runPHP step after Elementor, Contact Form 7 and the themes are installed.
 */

require_once '/wordpress/wp-load.php';
require_once __DIR__ . '/lib.php';

// Remove WordPress's sample content.
foreach ( array( 'hello-world' => 'post', 'sample-page' => 'page', 'privacy-policy' => 'page' ) as $slug => $type ) {
	$sample = get_page_by_path( $slug, OBJECT, $type );
	if ( $sample ) {
		wp_delete_post( $sample->ID, true );
	}
}

$manifest = json_decode( file_get_contents( __DIR__ . '/pages.json' ), true );
pa_import_site( $manifest, 'help' );

// A demo copy of a live site: keep it out of search engines.
update_option( 'blog_public', '0' );
