<?php
/**
 * Site setup shared by the local WP-CLI importers (builder/import*.php) and the
 * WordPress Playground demos (playground/setup.php).
 *
 * Two sites use it: "academy" (projector.academy) and "help" (projector.help).
 * Idempotent: pages are matched by slug and updated in place; menus and the
 * contact form are rebuilt.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function pa_log( $message ) {
	if ( class_exists( 'WP_CLI' ) ) {
		WP_CLI::log( $message );
	} else {
		echo $message . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput
	}
}

/**
 * Per-site settings: identity, theme options, menus and the contact form.
 *
 * Menu items are [ type, target, label, css classes ] where type is "page"
 * (target = slug) or "custom" (target = URL, opens in a new tab).
 *
 * @param string $site "academy" or "help".
 * @return array
 */
function pa_site_config( $site ) {
	$configs = array(
		'academy' => array(
			'blogname'        => 'Projector Academy',
			'blogdescription' => 'Affiliate Sales and User Guide',
			'theme_mods'      => array(
				'pa_site_key'    => 'academy',
				'pa_bg'          => 'calm',
				'pa_brand_tag'   => 'Academy',
				'pa_footer_text' => 'Projector Academy is a resource for all Projector Authorized Affiliate Members.',
				'pa_copyright'   => '',
			),
			'menus'           => array(
				'pa-primary' => array(
					'name'  => 'Header',
					'items' => array(
						array( 'page', 'projector-affiliate-membership-sales-and-user-guide', 'Sales and User Guide' ),
						array( 'custom', 'https://projector.help/', 'Projector Help Center' ),
						array( 'page', 'contact', 'Contact' ),
						array( 'page', 'newsletter', 'Newsletter' ),
					),
				),
				'pa-footer'  => array(
					'name'  => 'Footer',
					'items' => array(
						array( 'page', 'welcome-to-projector-academy', 'Welcome to Projector Academy' ),
						array( 'custom', 'https://support.projector.app', 'Projector Help Center' ),
						array( 'page', 'contact', 'Contact' ),
					),
				),
			),
			'form'            => array(
				'title'   => 'Contact Projector',
				'subject' => 'Projector Academy contact: [first-name] [last-name]',
				'from'    => '[first-name] [last-name] <[your-email]>',
				'body'    => <<<'FORM'
<div class="pa-form-row">
<p><label>Name <span class="pa-req">*</span>
[text* first-name autocomplete:given-name] <span class="pa-hint">First</span></label></p>
<p><label><span class="screen-reader-text">Last name</span>&nbsp;
[text* last-name autocomplete:family-name] <span class="pa-hint">Last</span></label></p>
</div>
<p><label>Email <span class="pa-req">*</span>
[email* your-email autocomplete:email]</label></p>
<p><label>Please Provide a Detailed Message <span class="pa-req">*</span>
[textarea* your-message]</label></p>
<p>[submit "Contact Us"]</p>
FORM
				,
			),
		),
		'help'    => array(
			'blogname'        => 'Projector Help Center',
			'blogdescription' => '',
			'theme_mods'      => array(
				'pa_site_key'    => 'help',
				'pa_bg'          => 'calm',
				'pa_brand_tag'   => 'Help Center',
				'pa_footer_text' => '',
				'pa_copyright'   => '© {year} Projector, LLC. All Rights Reserved.',
			),
			'menus'           => array(
				'pa-primary' => array(
					'name'  => 'Header',
					'items' => array(
						array( 'page', 'home', 'Home' ),
						array( 'page', 'contact', 'Contact Us' ),
						array( 'custom', 'https://www.projectorstream.com/', 'START USING PROJECTOR', 'pa-menu-cta' ),
					),
				),
				'pa-footer'  => array(
					'name'  => 'Footer',
					'items' => array(
						array( 'page', 'about', 'What is Projector?' ),
						array( 'page', 'gettingstarted', 'Getting started' ),
						array( 'page', 'faq', "FAQ's" ),
						array( 'page', 'contact', 'Contact Us' ),
					),
				),
			),
			'form'            => array(
				'title'   => 'Contact Projector Support',
				'subject' => 'Projector Help Center: [your-name]',
				'from'    => '[your-name] <[your-email]>',
				'body'    => <<<'FORM'
<p><label>Name <span class="pa-req">*</span>
[text* your-name autocomplete:name]</label></p>
<p><label>Email <span class="pa-req">*</span>
[email* your-email autocomplete:email]
<span class="pa-hint">If you have an active subscription, please ensure the email entered matches your Projector account.</span></label></p>
<p><label>Comment or Message <span class="pa-req">*</span>
[textarea* your-message]</label></p>
<p>[submit "Submit"]</p>
FORM
				,
			),
		),
	);
	return $configs[ $site ];
}

/**
 * Site options the design relies on.
 */
function pa_site_options( array $config ) {
	update_option( 'blogname', $config['blogname'] );
	update_option( 'blogdescription', $config['blogdescription'] );
	update_option( 'elementor_disable_color_schemes', 'yes' );
	update_option( 'elementor_disable_typography_schemes', 'yes' );
	update_option( 'elementor_load_fa4_shim', '' );
	update_option( 'elementor_onboarded', 1 );
	if ( get_option( 'permalink_structure' ) !== '/%postname%/' ) {
		update_option( 'permalink_structure', '/%postname%/' );
	}
	foreach ( $config['theme_mods'] as $name => $value ) {
		set_theme_mod( $name, $value );
	}
	delete_transient( 'elementor_activation_redirect' );
}

/**
 * Creates (or updates) the Contact Form 7 form that mirrors the live contact form.
 *
 * @return string Shortcode, or '' when Contact Form 7 is not active.
 */
function pa_contact_form_shortcode( array $form_config ) {
	if ( ! class_exists( 'WPCF7_ContactForm' ) ) {
		return '';
	}
	$existing = get_posts(
		array(
			'post_type'   => 'wpcf7_contact_form',
			'title'       => $form_config['title'],
			'numberposts' => 1,
		)
	);
	$form     = $existing ? WPCF7_ContactForm::get_instance( $existing[0]->ID ) : WPCF7_ContactForm::get_template();

	$form->set_title( $form_config['title'] );
	$props                               = $form->get_properties();
	$props['form']                       = $form_config['body'];
	$props['mail']['subject']            = $form_config['subject'];
	$props['mail']['body']               = 'From: ' . $form_config['from'] . "\n\n[your-message]\n\n-- \nSent from the " . $form_config['title'] . ' form';
	$props['mail']['additional_headers'] = 'Reply-To: [your-email]';
	$form->set_properties( $props );
	$form->save();

	return $form->shortcode();
}

/**
 * Imports a page manifest built by builder/build.py.
 *
 * @param array  $manifest List of pages: slug, title, menu_order, data, front.
 * @param string $site     "academy" or "help".
 * @return array<string, int> Page IDs by slug.
 */
function pa_import_site( array $manifest, $site = 'academy' ) {
	$config = pa_site_config( $site );

	// Elementor checks capabilities when site options sync into its kit, so act as an administrator.
	if ( ! get_current_user_id() ) {
		$admins = get_users(
			array(
				'role'   => 'administrator',
				'number' => 1,
				'fields' => 'ID',
			)
		);
		if ( $admins ) {
			wp_set_current_user( (int) $admins[0] );
		}
	}

	pa_site_options( $config );

	// Point the contact page at this site's own form.
	$shortcode = pa_contact_form_shortcode( $config['form'] );

	$elementor_version = defined( 'ELEMENTOR_VERSION' ) ? ELEMENTOR_VERSION : '3.0.0';
	$ids               = array();

	foreach ( $manifest as $page ) {
		$data_json = wp_json_encode( $page['data'] );
		if ( $shortcode ) {
			$escaped   = substr( wp_json_encode( $shortcode ), 1, -1 );
			$data_json = preg_replace_callback(
				'/\[contact-form-7[^\]]*\]/',
				function () use ( $escaped ) {
					return $escaped;
				},
				$data_json
			);
		}

		$existing = get_page_by_path( $page['slug'], OBJECT, 'page' );
		$postarr  = array(
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_title'   => $page['title'],
			'post_name'    => $page['slug'],
			'menu_order'   => (int) $page['menu_order'],
			'post_content' => '',
		);
		if ( $existing ) {
			$postarr['ID'] = $existing->ID;
			$id            = wp_update_post( $postarr, true );
		} else {
			$id = wp_insert_post( $postarr, true );
		}
		if ( is_wp_error( $id ) ) {
			pa_log( 'WARNING ' . $page['slug'] . ': ' . $id->get_error_message() );
			continue;
		}

		update_post_meta( $id, '_wp_page_template', 'elementor_header_footer' );
		update_post_meta( $id, '_elementor_edit_mode', 'builder' );
		update_post_meta( $id, '_elementor_template_type', 'wp-page' );
		update_post_meta( $id, '_elementor_version', $elementor_version );
		update_post_meta( $id, '_elementor_page_settings', array( 'hide_title' => 'yes' ) );
		update_post_meta( $id, '_elementor_data', wp_slash( $data_json ) );
		delete_post_meta( $id, '_elementor_css' );
		delete_post_meta( $id, '_elementor_element_cache' );
		delete_post_meta( $id, '_elementor_page_assets' );

		$ids[ $page['slug'] ] = $id;
		if ( ! empty( $page['front'] ) ) {
			update_option( 'show_on_front', 'page' );
			update_option( 'page_on_front', $id );
		}
		pa_log( sprintf( '%-55s #%d', $page['slug'], $id ) );
	}

	pa_kit_settings();
	pa_menus( $config['menus'], $ids );

	if ( class_exists( '\Elementor\Plugin' ) ) {
		\Elementor\Plugin::$instance->files_manager->clear_cache();
	}
	flush_rewrite_rules();

	return $ids;
}

/**
 * Global colors & fonts in the Elementor kit, so the editor offers the brand palette.
 */
function pa_kit_settings() {
	$kit_id = (int) get_option( 'elementor_active_kit' );
	if ( ! $kit_id && class_exists( '\Elementor\Plugin' ) ) {
		$kit_id = (int) \Elementor\Plugin::$instance->kits_manager->get_active_id();
	}
	if ( ! $kit_id ) {
		pa_log( 'WARNING no Elementor kit found; global colors were not set.' );
		return;
	}

	$kit = get_post_meta( $kit_id, '_elementor_page_settings', true );
	$kit = is_array( $kit ) ? $kit : array();

	$kit['system_colors'] = array(
		array( '_id' => 'primary', 'title' => 'Primary (Projector Blue)', 'color' => '#3D8EF2' ),
		array( '_id' => 'secondary', 'title' => 'Secondary (Ink)', 'color' => '#0D1017' ),
		array( '_id' => 'text', 'title' => 'Text', 'color' => '#2A303C' ),
		array( '_id' => 'accent', 'title' => 'Accent (Cyan)', 'color' => '#5FD3E9' ),
	);
	$kit['custom_colors'] = array(
		array( '_id' => 'pa_night', 'title' => 'Night', 'color' => '#04060C' ),
		array( '_id' => 'pa_violet', 'title' => 'Violet', 'color' => '#7B62FF' ),
		array( '_id' => 'pa_canvas', 'title' => 'Canvas', 'color' => '#F2F5F9' ),
		array( '_id' => 'pa_muted', 'title' => 'Muted', 'color' => '#697386' ),
	);
	$font = function ( $id, $title, $weight ) {
		return array(
			'_id'                    => $id,
			'title'                  => $title,
			'typography_typography'  => 'custom',
			'typography_font_family' => 'Montserrat',
			'typography_font_weight' => $weight,
		);
	};
	$kit['system_typography'] = array(
		$font( 'primary', 'Primary', '800' ),
		$font( 'secondary', 'Secondary', '700' ),
		$font( 'text', 'Text', '400' ),
		$font( 'accent', 'Accent', '600' ),
	);
	$kit['container_width']         = array( 'unit' => 'px', 'size' => 1200, 'sizes' => array() );
	$kit['global_image_lightbox']   = 'yes';
	$kit['lightbox_enable_counter'] = 'no';
	update_post_meta( $kit_id, '_elementor_page_settings', $kit );
}

/**
 * Header and footer menus.
 *
 * @param array              $menus Menu definitions by theme location.
 * @param array<string, int> $ids   Page IDs by slug.
 */
function pa_menus( array $menus, array $ids ) {
	$locations = get_theme_mod( 'nav_menu_locations', array() );
	foreach ( $menus as $location => $menu ) {
		$obj = wp_get_nav_menu_object( $menu['name'] );
		if ( $obj ) {
			foreach ( wp_get_nav_menu_items( $obj->term_id ) ?: array() as $item ) {
				wp_delete_post( $item->ID, true );
			}
			$menu_id = $obj->term_id;
		} else {
			$menu_id = wp_create_nav_menu( $menu['name'] );
		}
		foreach ( $menu['items'] as $pos => $item ) {
			$type    = $item[0];
			$target  = $item[1];
			$label   = $item[2];
			$classes = isset( $item[3] ) ? $item[3] : '';
			$args    = array(
				'menu-item-title'    => $label,
				'menu-item-status'   => 'publish',
				'menu-item-position' => $pos + 1,
				'menu-item-classes'  => $classes,
			);
			if ( 'page' === $type && isset( $ids[ $target ] ) ) {
				$args['menu-item-type']      = 'post_type';
				$args['menu-item-object']    = 'page';
				$args['menu-item-object-id'] = $ids[ $target ];
			} else {
				$args['menu-item-type']   = 'custom';
				$args['menu-item-url']    = $target;
				$args['menu-item-target'] = '_blank';
			}
			wp_update_nav_menu_item( $menu_id, 0, $args );
		}
		$locations[ $location ] = $menu_id;
	}
	set_theme_mod( 'nav_menu_locations', $locations );
}
