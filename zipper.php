<?php
//root file zipper.php
/**
 * Plugin Name: Zipper
 * Description: Zipper rapidement un ou plusieurs plugins et gérer les .zip (download / delete). Enregistre les fichiers .zip dans wp-content/uploads/zipper/.
 * Version: 1.0.0
 * Author: mp-weblab
 * Site: https://mp-weblab.com
 * Licence : GPLv2+
 * Note de l'auteur :
 * Ce plugin est distribué gratuitement dans un esprit de partage.
 * Merci de ne pas le vendre ou monétiser sous une forme quelconque.
 * Text Domain: zipper
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

define( 'ZIPPER_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'ZIPPER_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'ZIPPER_UPLOAD_DIR', wp_upload_dir()['basedir'] . '/zipper' );
define( 'ZIPPER_UPLOAD_URL', wp_upload_dir()['baseurl'] . '/zipper' );

// includes
require_once ZIPPER_PLUGIN_DIR . 'includes/zip-acts.php';
require_once ZIPPER_PLUGIN_DIR . 'includes/admin-page.php';
require_once ZIPPER_PLUGIN_DIR . 'includes/list-table.php';

// Activation: ensure upload dir exists
register_activation_hook( __FILE__, function() {
    if ( ! file_exists( ZIPPER_UPLOAD_DIR ) ) {
        wp_mkdir_p( ZIPPER_UPLOAD_DIR );
        // add .htaccess for safety
        $ht = "Options -Indexes\n<IfModule mod_php7.c>\n    php_flag engine off\n</IfModule>\n";
        @file_put_contents( ZIPPER_UPLOAD_DIR . '/.htaccess', $ht );
    }
});

// Add assets
add_action( 'admin_enqueue_scripts', function( $hook ) {
    // enqueue only on our tools page or plugins page (for row action script)
    if ( $hook === 'tools_page_zipper' || $hook === 'plugins.php' ) {
        wp_enqueue_script( 'zipper-admin-js', ZIPPER_PLUGIN_URL . 'assets/js/script.js', array( 'jquery' ), '1.0.0', true );
        wp_localize_script( 'zipper-admin-js', 'zipperData', array(
            'ajaxUrl' => admin_url( 'admin-post.php' ),
            'nonce'   => wp_create_nonce( 'zipper_nonce' )
        ));
    }
});

// Add link under plugin row (Paramètres link)
add_filter( 'plugin_action_links', function( $actions, $plugin_file, $plugin_data, $context ) {
    // add "Paramètres" (link to Outils -> Zipper) only for the Zipper plugin row
    $self_file = plugin_basename( __FILE__ );
    if ( $plugin_file === $self_file ) {
        $url = admin_url( 'tools.php?page=zipper' );
        $actions['zipper_settings'] = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Paramètres', 'zipper' ) . '</a>';
    }
    return $actions;
}, 10, 4 );

// Add row action "Zipper ce plugin" under every plugin (the link will point to admin-post with action to zip & stream)
add_filter( 'plugin_row_meta', function( $plugin_meta, $plugin_file, $plugin_data, $status ) {
    // add only a simple row action link (we'll append it)
    $href = wp_nonce_url( admin_url( 'admin-post.php?action=zipper_row_zip&plugin=' . rawurlencode( $plugin_file ) ), 'zipper_row_zip' );
    $plugin_meta[] = '<a href="' . esc_url( $href ) . '">' . esc_html__( 'Zipper ce plugin', 'zipper' ) . '</a>';
    return $plugin_meta;
}, 10, 4 );

