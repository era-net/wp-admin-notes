<?php
/**
 * Plugin Name: WPAdmin Notes
 * Plugin URI: https://github.com/era-net/wp-admin-notes
 * Description: A handy markdown note block for your admin panel.
 * Version: 1.0.1
 * Text Domain: mdn-notes
 * Domain Path: /languages
 * Author: Ervinator
 * Author URI: https://era-kast.ch
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
    require_once plugin_dir_path(__FILE__) . 'vendor/radishconcepts/wordpress-github-plugin-updater/updater.php';
    require_once plugin_dir_path(__FILE__) . 'inc/mdn-ajax-calls.inc.php';

    // Check for updates via github
    $config = [
        'slug' => plugin_basename(__FILE__), // this is the slug of your plugin
        'proper_folder_name' => 'wp-admin-notes', // this is the name of the folder your plugin lives in
        'api_url' => 'https://api.github.com/repos/era-net/wp-admin-notes', // the GitHub API url of your GitHub repo
        'raw_url' => 'https://raw.github.com/era-net/wp-admin-notes/master', // the GitHub raw url of your GitHub repo
        'github_url' => 'https://github.com/era-net/wp-admin-notes', // the GitHub url of your GitHub repo
        'zip_url' => 'https://github.com/username/repository-name/zipball/master', // the zip url of the GitHub repo
        'sslverify' => true, // whether WP should check the validity of the SSL cert when getting an update, see https://github.com/jkudish/WordPress-GitHub-Plugin-Updater/issues/2 and https://github.com/jkudish/WordPress-GitHub-Plugin-Updater/issues/4 for details
        'requires' => '6.0', // which version of WordPress does your plugin require?
        'tested' => '6.4', // which version of WordPress is your plugin tested up to?
        'readme' => 'README.md', // which file to use as the readme for the version number
        'access_token' => '', // Access private repositories by authorizing under Plugins > GitHub Updates when this example plugin is installed
    ];

    new WP_GitHub_Updater($config);

    load_plugin_textdomain( 'mdn-notes', false, 'wp-admin-notes/languages' );

    // Register Post Type
    register_post_type('mdn_note', [
        'label'           => 'mdn_note',
        'show_ui'         => false,
        'show_in_menu'    => false,
        'capability_type' => 'post',
        'map_meta_cap'    => true,
        'rewrite'         => array( 'slug'         => 'notes' ),
        '_builtin'        => false,
        'query_var'       => true,
        'supports'        => array( 'title', 'editor' )
    ]);
}

add_action( 'admin_enqueue_scripts', 'mdn_enqueue_admin_scripts' );
function mdn_enqueue_admin_scripts() {
    wp_enqueue_style('mdn-admin-markdown', plugin_dir_url(__FILE__) . 'assets/css/mdn-admin-notes.min.css');
    wp_enqueue_script('mdn-admin-notes-backend', plugin_dir_url(__FILE__) . 'assets/js/mdn-admin-notes.min.js', ['jquery'], '', true);
}

// add a link to the WP Toolbar
function mdn_custom_toolbar_link($wp_admin_bar) {
    $screen = get_current_screen();

    // Only show on dashboard
    if ( 'dashboard' !== $screen->id ) {
        return;
    }
    $args = array(
        'id' => 'mdn-notes-add',
        'parent' => 'top-secondary',
        'title' => '+ Add new Note', 
        'href' => 'javascript:void(0);',
        'meta' => array(
            'class' => 'mdn-notes-add-note', 
            'title' => 'Add a new Markdown note to your Dashboard'
            )
    );
    $wp_admin_bar->add_node($args);
}
add_action('admin_bar_menu', 'mdn_custom_toolbar_link', 9998);

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
    <div class="mdn-markdown-footer-flex-end">
        <div><button type="button" class="button button-secondary mdn-delete-button" data-name="mdn-note-delete" data-note-id="<?= $note->ID ?>">delete</button></div>
        <div><button type="button" class="button button-primary" data-name="mdn-note-edit" data-note-id="<?= $note->ID ?>">edit</button></div>
    </div>

    <?php
    echo ob_get_clean();
}
