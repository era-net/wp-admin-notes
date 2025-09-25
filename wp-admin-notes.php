<?php
/*
 * Plugin Name:       WPAdmin Notes
 * Plugin URI:        https://github.com/era-net/wp-admin-notes
 * Description:       A handy markdown note block for your admin panel.
 * Version:           1.1.0
 * Requires at least: 6.3
 * Requires PHP:      8.0
 * Author:            ERA
 * Author URI:        https://era-kast.ch/
 * License:           GNU v3 or later
 * License URI:       https://www.gnu.org/licenses/#GPL
 * Update URI:        https://github.com/era-net/wp-admin-notes/releases
 * Text Domain:       mdn-notes
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
if ( ! is_admin() ) return; // Only load plugin when user is in admin

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\CommonMark\Node\Inline\Link;
use League\CommonMark\Extension\CommonMark\Node\Block\ListBlock;
use League\CommonMark\Extension\CommonMark\Node\Block\ListItem;
use League\CommonMark\Extension\DefaultAttributes\DefaultAttributesExtension;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\Extension\TaskList\TaskListItemMarker;
use League\CommonMark\MarkdownConverter;

add_action( 'admin_init', 'mdn_admin_init' );
function mdn_admin_init() {
    require_once plugin_dir_path(__FILE__) . 'inc/mdn-ajax-calls.inc.php';

    load_plugin_textdomain( 'mdn-notes', false, 'wp-admin-notes/languages' );

    // Register Post Type
    register_post_type('mdn_note', [
        'label'           => 'mdn_note',
        'show_ui'         => false,
        'show_in_menu'    => false,
        'capability_type' => 'post',
        'map_meta_cap'    => true,
        'rewrite'         => array( 'slug' => 'notes' ),
        '_builtin'        => false,
        'query_var'       => true,
        'supports'        => array( 'title', 'editor' )
    ]);
}

add_action( 'admin_enqueue_scripts', 'mdn_enqueue_admin_scripts' );
function mdn_enqueue_admin_scripts() {

    $screen = get_current_screen();
    // The styles and scripts for the functionality are only needed in the dashboard
    if ($screen->id == 'dashboard') {
        wp_enqueue_style('mdn-admin-markdown', plugin_dir_url(__FILE__) . 'assets/css/mdn-admin-notes.min.css');
        wp_enqueue_script('mdn-admin-notes-backend', plugin_dir_url(__FILE__) . 'assets/js/mdn-admin-notes.min.js', ['jquery'], '', true);
    }

    // Skipping releases
    wp_enqueue_script('mdn-admin-skip-release', plugin_dir_url(__FILE__) . 'assets/js/mdn-admin-skip-release.min.js', ['jquery'], '', true);
    wp_localize_script('mdn-admin-skip-release', 'skipRelease', [__( 'confirm skipping', 'mdn-notes' )]);

}

/**
 * NEW NOTE TOOLBAR BUTTON
 */
add_action('admin_bar_menu', 'mdn_custom_toolbar_link', 10);
function mdn_custom_toolbar_link($wp_admin_bar) {
    $screen = get_current_screen();

    // Only show on dashboard
    if ( 'dashboard' !== $screen->id ) {
        return;
    }
    $args = array(
        'id' => 'mdn-notes-add',
        'parent' => 'top-secondary',
        'title' => '+ ' . __( 'New Note', 'mdn-notes' ), 
        'href' => 'javascript:void(0);',
        'meta' => array(
            'class' => 'mdn-notes-add-note', 
            'title' => __( 'New markdown note', 'mdn-notes' )
            )
    );
    $wp_admin_bar->add_node($args);
}

/**
 * RENDER DASHBOARD WIDGETS
 */
add_action('wp_dashboard_setup', 'mdn_init_dashboard_widgets');
  
function mdn_init_dashboard_widgets() {
    $notes = get_posts(['posts_per_page' => '-1', 'post_type' => 'mdn_note']);
    $user = wp_get_current_user();

    foreach ($notes as $note) {
        // show notes only to users that created them
        if ($user->ID != $note->post_author) {
            continue;
        }
        wp_add_dashboard_widget(
            'mdn_note_' . $note->ID,
            $note->post_title,
            'mdn_render_dashboard_widget',
            '',
            $note,
            'normal',
            'high'
        );
    }
}

/**
 * RENDERING THE NOTES
 */
function mdn_render_dashboard_widget($_, $args) {
    require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';

    $env_config = [
        'default_attributes' => [
            Link::class => [
                'target' => '_blank',
            ],
            ListBlock::class => [
                'class' => static function (ListBlock $ul) {
                    foreach ($ul->children() as $x) {
                        foreach ($x->children() as $y) {
                            if ($y->firstChild() instanceof TaskListItemMarker){
                                return "mdn-has-task-list";
                            }
                        }
                    }
                }
            ],
            ListItem::class => [
                'class' => static function (ListItem $li) {
                    foreach ($li->children() as $ch) {
                        if ($ch->firstChild() instanceof TaskListItemMarker) {
                            return 'mdn-task-list-item';
                        }
                    }
                }
            ]
        ],
        'html_input' => 'escape',
        'allow_unsafe_links' => false,
        'max_nesting_level' => 12,
        'renderer' => [
            'soft_break' => '</br>'
        ],
    ];

    $env = new Environment($env_config);
    $env->addExtension(new CommonMarkCoreExtension());
    $env->addExtension(new DefaultAttributesExtension());
    $env->addExtension(new GithubFlavoredMarkdownExtension());

    $note = $args['args'];

    $converter = new MarkdownConverter($env);

    $html = $converter->convert($note->post_content)->getContent();

    ob_start(); ?>

    <div class="mdn-markdown-content">
        <?= $html ?>
    </div>
    <div class="mdn-footer">
        <div class="mdn-footer-actions">
            <div class="mdn-footer-inside">
                <button type="button" class="mdn-footer-action-btn" data-name="mdn-note-delete" data-confirm-message="<?= sprintf(__( 'Sure to delete &quot;%s&quot; ?', 'mdn-notes' ), $note->post_title ) ?>" data-note-id="<?= $note->ID ?>" title="<?= __( 'delete', 'mdn-notes' ) ?>">
                    <svg width="16px" height="16px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M3 6.98996C8.81444 4.87965 15.1856 4.87965 21 6.98996" stroke="#fff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M8.00977 5.71997C8.00977 4.6591 8.43119 3.64175 9.18134 2.8916C9.93148 2.14146 10.9489 1.71997 12.0098 1.71997C13.0706 1.71997 14.0881 2.14146 14.8382 2.8916C15.5883 3.64175 16.0098 4.6591 16.0098 5.71997" stroke="#fff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M12 13V18" stroke="#fff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M19 9.98999L18.33 17.99C18.2225 19.071 17.7225 20.0751 16.9246 20.8123C16.1266 21.5494 15.0861 21.9684 14 21.99H10C8.91389 21.9684 7.87336 21.5494 7.07541 20.8123C6.27745 20.0751 5.77745 19.071 5.67001 17.99L5 9.98999" stroke="#fff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
                <button type="button" class="mdn-footer-action-btn" data-name="mdn-note-edit" data-note-id="<?= $note->ID ?>" title="<?= __( 'edit', 'mdn-notes' ) ?>">
                    <svg width="16px" height="16px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M21.2799 6.40005L11.7399 15.94C10.7899 16.89 7.96987 17.33 7.33987 16.7C6.70987 16.07 7.13987 13.25 8.08987 12.3L17.6399 2.75002C17.8754 2.49308 18.1605 2.28654 18.4781 2.14284C18.7956 1.99914 19.139 1.92124 19.4875 1.9139C19.8359 1.90657 20.1823 1.96991 20.5056 2.10012C20.8289 2.23033 21.1225 2.42473 21.3686 2.67153C21.6147 2.91833 21.8083 3.21243 21.9376 3.53609C22.0669 3.85976 22.1294 4.20626 22.1211 4.55471C22.1128 4.90316 22.0339 5.24635 21.8894 5.5635C21.7448 5.88065 21.5375 6.16524 21.2799 6.40005V6.40005Z" stroke="#fff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M11 4H6C4.93913 4 3.92178 4.42142 3.17163 5.17157C2.42149 5.92172 2 6.93913 2 8V18C2 19.0609 2.42149 20.0783 3.17163 20.8284C3.92178 21.5786 4.93913 22 6 22H17C19.21 22 20 20.2 20 18V13" stroke="#fff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <?php
    echo ob_get_clean();
}

/**
 * CHECK FOR UPDATES
 * Checks for updates and displays a notice if updates are available.
 */
add_action('current_screen', 'mdn_check_for_updates');
function mdn_check_for_updates() {
    // update the current version
    $pd = get_plugin_data(__FILE__);
    $version = $pd['Version'];
    $current = 'mdn_version';
    update_option($current, $version);

    $new = 'mdn_latest';
    // check if option exists
    if (get_option($new)) {
        $skip = 'mdn_release_skip';
        // do not trigger notice if user skipped this release
        if (get_option($new) === get_option($skip)) {
            return;
        }
        // compare versions
        if (get_option($current) !== get_option($new)) {
            // show a notice
            add_action('admin_notices', 'mdn_show_update_notice');
        }
    }
}

function mdn_show_update_notice() {
    $screen = get_current_screen();
    // do not show during the updating process
    if ($screen->id !== 'update') {
        ?>
        <div id="mdn-admin-update-notice" class="notice notice-warning">
            
                <?php
                if ($screen->id == "plugin-install") {
                    echo '<p><strong>WPAdmin Notes • <a href="https://github.com/era-net/wp-admin-notes/releases/latest" target="_blank">v' . get_option("mdn_latest") . '</a></strong></p>';
                    echo '<a href="https://github.com/era-net/wp-admin-notes/releases/latest/download/wp-admin-notes.zip" class="button button-primary">wp-admin-notes.zip • v' . get_option("mdn_latest") . '</a> ';
                    echo '<div style="margin-top: 0.5em; padding-top: 2px;"></div>';
                } else {
                    ?>
                        <h3>WPAdmin Notes <small><?= __( 'just released an update', 'mdn-notes' ) ?></small></h3>
                        <p>
                            <a href="<?= esc_url( admin_url() . 'plugin-install.php?tab=upload' ) ?>" class="button button-primary"> <strong><?= __( 'Update Now', 'mdn-notes' ) ?></strong>!</a> 
                            <button id="mdn-skip-release" class="button button-secondary"><?= __( 'skip this release', 'mdn-notes' ) ?></button>
                            <div>
                                <a href="#"><?= __( 'Need help updating', 'mdn-notes' ) ?>?</a> | 
                                <a href="https://github.com/era-net/wp-admin-notes/releases/latest" target="_blank"><strong><?= get_option("mdn_latest") ?></strong></a>
                            </div>
                        </p>
                    <?php
                }
                ?>
        </div>
        <?php
    }
}