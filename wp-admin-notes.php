<?php
/**
 * Plugin Name: WPAdmin Notes
 * Plugin URI: #
 * Description: A handy note block for your admin panel.
 * Version: 1.0.0
 * Text Domain: wpn-notes
 * Domain Path: /languages
 * Author: Ervinator
 * Author URI: https://era-kast.ch
 */

if (! defined( 'ABSPATH' )) {
    die();
}

add_action( 'admin_init', 'wpn_admin_init' );
function wpn_admin_init() {
    require_once plugin_dir_path(__FILE__) . 'inc/wpn-ajax-calls.inc.php';
    load_plugin_textdomain( 'wpn-notes', false, 'wp-admin-notes/languages' );
}

add_action( 'admin_enqueue_scripts', 'wpn_enqueue_admin_scripts' );
function wpn_enqueue_admin_scripts() {
    wp_enqueue_script('wpn-admin-notes-backend', plugin_dir_url(__FILE__) . 'assets/js/wpn-admin-notes.js', ['jquery'], '', true);
}

/**
 * NAV SUB MENU
 */
add_action( 'admin_menu', 'wpn_admin_sub_menu' );
function wpn_admin_sub_menu() {
    add_submenu_page(
        'tools.php',
        'WPAdmin Notes',
        'WPAdmin Notes',
        'manage_options',
        'wp-admin-notes',
        'wpn_main_page_contents'
    );
}
function wpn_main_page_contents() {
    ?>
    <div class="wrap">
        <h1>WPAdmin Notes</h1>
        <p>Lorem ipsum dolor sit amet consectetur adipisicing elit. Nostrum dolore aliquid, doloribus quo necessitatibus temporibus fuga quae recusandae ipsa, consequuntur animi quam vitae ab at a nulla. Accusamus, vitae ea.</p>
    </div>
    <?php
}

// add a link to the WP Toolbar
function custom_toolbar_link($wp_admin_bar) {
    $screen = get_current_screen();

    // Only show on dashboard
    if ( 'dashboard' !== $screen->id ) {
        return;
    }
    $args = array(
        'id' => 'wpn-notes-add',
        'parent' => 'top-secondary',
        'title' => 'Search WPBeginner', 
        'href' => 'javascript:void(0);', 
        'meta' => array(
            'class' => 'wpn-notes-add-note', 
            'title' => 'Search WPBeginner Tutorials'
            )
    );
    $wp_admin_bar->add_node($args);
}
add_action('admin_bar_menu', 'custom_toolbar_link', 100000);



add_action('wp_dashboard_setup', 'wpn_add_dashboard_widget');
function wpn_add_dashboard_widget() {
    wp_add_dashboard_widget("wpn_dash_wig", "example", "widget_contents");
}

function widget_contents() {
    echo "<b>hehe</b>";
}

/**
 * ADDING A SIMPLE DASHBOARD WIDGET
 */
// add_action('wp_dashboard_setup', 'my_custom_dashboard_widgets');
  
// function my_custom_dashboard_widgets() {
//     wp_add_dashboard_widget('custom_help_widget', 'Theme Support', 'custom_dashboard_help');
// }
 
// function custom_dashboard_help() {
//     echo '<p>Welcome to Custom Blog Theme! Need help? Contact the developer <a href="mailto:yourusername@gmail.com">here</a>. For WordPress Tutorials visit: <a href="https://www.wpbeginner.com" target="_blank">WPBeginner</a></p>';
// }