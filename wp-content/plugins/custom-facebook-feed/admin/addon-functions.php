<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Deactivate addon.
 *
 * @since 1.0.0
 */
function cff_deactivate_addon() {

	// Run a security check.
	check_ajax_referer( 'cff-admin', 'nonce' );
	$cap = current_user_can( 'manage_custom_facebook_feed_options' ) ? 'manage_custom_facebook_feed_options' : 'manage_options';
	$cap = apply_filters( 'cff_settings_pages_capability', $cap );
	// Check for permissions.
	if ( ! current_user_can( $cap ) ) {
		wp_send_json_error();
	}

	$type = 'addon';
	if ( ! empty( $_POST['type'] ) ) {
		$type = sanitize_key( $_POST['type'] );
	}

	if ( isset( $_POST['plugin'] ) ) {
		deactivate_plugins( $_POST['plugin'] );

		if ( 'plugin' === $type ) {
			wp_send_json_success( esc_html__( 'Plugin deactivated.', 'custom-facebook-feed' ) );
		} else {
			wp_send_json_success( esc_html__( 'Addon deactivated.', 'custom-facebook-feed' ) );
		}
	}

	wp_send_json_error( esc_html__( 'Could not deactivate the addon. Please deactivate from the Plugins page.', 'custom-facebook-feed' ) );
}
add_action( 'wp_ajax_cff_deactivate_addon', 'cff_deactivate_addon' );

/**
 * Activate addon.
 *
 * @since 1.0.0
 */
function cff_activate_addon() {

	// Run a security check.
	check_ajax_referer( 'cff-admin', 'nonce' );

	// Check for permissions.
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error();
	}

	if ( isset( $_POST['plugin'] ) ) {

		$type = 'addon';
		if ( ! empty( $_POST['type'] ) ) {
			$type = sanitize_key( $_POST['type'] );
		}

		$activate = activate_plugins( $_POST['plugin'] );

		if ( ! is_wp_error( $activate ) ) {
			if ( 'plugin' === $type ) {
				wp_send_json_success( esc_html__( 'Plugin activated.', 'custom-facebook-feed' ) );
			} else {
				wp_send_json_success( esc_html__( 'Addon activated.', 'custom-facebook-feed' ) );
			}
		}
	}

	wp_send_json_error( esc_html__( 'Could not activate addon. Please activate from the Plugins page.', 'custom-facebook-feed' ) );
}
add_action( 'wp_ajax_cff_activate_addon', 'cff_activate_addon' );

/**
 * Install addon.
 *
 * @since 1.0.0
 */
function cff_install_addon() {

	// Run a security check.
	check_ajax_referer( 'cff-admin', 'nonce' );

	// Check for permissions.
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error();
	}

	$error = esc_html__( 'Could not install addon. Please download from smashballoon.com and install manually.', 'custom-facebook-feed' );

	if ( empty( $_POST['plugin'] ) ) {
		wp_send_json_error( $error );
	}

	// Set the current screen to avoid undefined notices.
	set_current_screen( 'cff-about' );

	// Prepare variables.
	$url = esc_url_raw(
		add_query_arg(
			array(
				'page' => 'cff-about',
			),
			admin_url( 'admin.php' )
		)
	);

	$creds = request_filesystem_credentials( $url, '', false, false, null );

	// Check for file system permissions.
	if ( false === $creds ) {
		wp_send_json_error( $error );
	}

	if ( ! WP_Filesystem( $creds ) ) {
		wp_send_json_error( $error );
	}

	/*
	 * We do not need any extra credentials if we have gotten this far, so let's install the plugin.
	 */

	require_once CFF_PLUGIN_DIR . 'admin/class-install-skin.php';

	// Do not allow WordPress to search/download translations, as this will break JS output.
	remove_action( 'upgrader_process_complete', array( 'Language_Pack_Upgrader', 'async_upgrade' ), 20 );

	// Create the plugin upgrader with our custom skin.
	$installer = new CFF\Helpers\PluginSilentUpgrader( new CFF_Install_Skin() );

	// Error check.
	if ( ! method_exists( $installer, 'install' ) || empty( $_POST['plugin'] ) ) {
		wp_send_json_error( $error );
	}

	$installer->install( $_POST['plugin'] ); // phpcs:ignore

	// Flush the cache and return the newly installed plugin basename.
	wp_cache_flush();

	$plugin_basename = $installer->plugin_info();

	if ( $plugin_basename ) {

		$type = 'addon';
		if ( ! empty( $_POST['type'] ) ) {
			$type = sanitize_key( $_POST['type'] );
		}

		// Activate the plugin silently.
		$activated = activate_plugin( $plugin_basename );

		if ( ! is_wp_error( $activated ) ) {
			wp_send_json_success(
				array(
					'msg'          => 'plugin' === $type ? esc_html__( 'Plugin installed & activated.', 'custom-facebook-feed' ) : esc_html__( 'Addon installed & activated.', 'custom-facebook-feed' ),
					'is_activated' => true,
					'basename'     => $plugin_basename,
				)
			);
		} else {
			wp_send_json_success(
				array(
					'msg'          => 'plugin' === $type ? esc_html__( 'Plugin installed.', 'custom-facebook-feed' ) : esc_html__( 'Addon installed.', 'custom-facebook-feed' ),
					'is_activated' => false,
					'basename'     => $plugin_basename,
				)
			);
		}
	}

	wp_send_json_error( $error );
}
add_action( 'wp_ajax_cff_install_addon', 'cff_install_addon' );