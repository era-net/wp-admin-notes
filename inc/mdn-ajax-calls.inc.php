<?php
require_once plugin_dir_path(__FILE__) . '../vendor/autoload.php';

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\CommonMark\Node\Inline\Link;
use League\CommonMark\Extension\DefaultAttributes\DefaultAttributesExtension;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\MarkdownConverter;

add_action( 'wp_ajax_nopriv_mdn_add_new_note', 'mdn_add_new_note' );
add_action( 'wp_ajax_mdn_add_new_note', 'mdn_add_new_note' );
function mdn_add_new_note() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'posts';
    $last_post = $wpdb->get_results('SELECT * FROM $table_name ORDER BY ID DESC LIMIT 1')[0];

    $note_id = absint($last_post->ID + 1);
    $widget_id = 'mdn_note_' . $note_id;
    $title_id = 'mdn_note_title_' . $note_id;
    $content_id = 'mdn_note_content_' . $note_id;
    $text_content_id = 'mdn_note_text_content_' . $note_id;
    $text_count_id = 'mdn_note_text_count_' . $note_id;

    ob_start(); ?>

    <div id="<?= $widget_id ?>" class="postbox">
        <div class="postbox-header">
            <h2 id="<?= $title_id ?>" class="hndle" contenteditable="true">New Note</h2>
        </div>
        <div id="<?= $content_id ?>" class="inside">
            <div class="mdn-markdown-header-flex-end">
                <div>Press <code>CTRL</code> + <code>ENTER</code> to save</div>
            </div>
            <textarea id="<?= $text_content_id ?>" rows="8" placeholder="Write your Markdown here ..." style="width: 100%;"></textarea>
            <div class="mdn-markdown-footer-space-between">
                <div>Learn more about <a href="https://commonmark.org/help/" target="_blank"><b>Markdown</b></a>.</div>
                <div class="mdn-text-muted"><span id="<?= $text_count_id ?>">0</span> / 5000</div>
            </div>
        </div>
    </div>

    <?php
    $rsp = [
        'noteId' => $note_id,
        'note' => ob_get_clean(),
        'widgetId' => $widget_id,
        'titleId' => $title_id,
        'contentId' => $content_id,
        'textContentId' => $text_content_id,
        'textCountId' => $text_count_id
    ];
    echo json_encode($rsp);
    die();
}

add_action( 'wp_ajax_nopriv_mdn_save_note', 'mdn_save_note' );
add_action( 'wp_ajax_mdn_save_note', 'mdn_save_note' );
function mdn_save_note() {
    $data = $_POST['data'];
    $note_content = sanitize_textarea_field($data['textContent']);
    $char_count = strlen($note_content);

    if ($char_count <= 5000) {

        $args = [
            'post_status'  => 'publish',
            'post_type'    => 'mdn_note',
            'post_title'   => 'New Note',
            'post_content' => $note_content
        ];

        $note_id = wp_insert_post($args);

        $env_config = [
            'default_attributes' => [
                Link::class => [
                    'target' => '_blank',
                ],
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

        $converter = new MarkdownConverter($env);
        $html = $converter->convert($note_content)->getContent();
        ob_start() ?>
        <div class="mdn-markdown-content">
            <?= $html ?>
        </div>
        <div class="mdn-markdown-footer-flex-end">
            <div><button type="button" class="button button-secondary mdn-delete-button" data-name="mdn-note-delete" data-note-id="<?= $note_id ?>">delete</button></div>
            <div><button type="button" class="button button-primary" data-name="mdn-note-edit" data-note-id="<?= $note_id ?>">edit</button></div>
        </div>
        <?php
        $rsp = [
            'status' => 'success',
            'content' => ob_get_clean(),
            'charCount' => $char_count,
            'trigger' => ($_POST['trigger'] === 'undefined') ? 'undefined' : $_POST['trigger']
        ];
    } else {
        $rsp = [
            'status' => 'large_content'
        ];
    }
    
    echo json_encode($rsp);
    die();
}

add_action( 'wp_ajax_nopriv_mdn_update_note', 'mdn_update_note' );
add_action( 'wp_ajax_mdn_update_note', 'mdn_update_note' );
function mdn_update_note() {
    $id = absint($_POST['id']);

    if (get_post_type($id) != 'mdn_note') {
        die();
    }

    $title = sanitize_text_field( $_POST['title'] );
    $content = sanitize_textarea_field( $_POST['content'] );

    $post = [
        'ID' => $id,
        'post_title' => $title,
        'post_content' => $content
    ];

    wp_update_post($post);

    $env_config = [
        'default_attributes' => [
            Link::class => [
                'target' => '_blank',
            ],
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

    $converter = new MarkdownConverter($env);
    $html = $converter->convert($content)->getContent();

    ob_start(); ?>

    <div class="mdn-markdown-content">
        <?= $html ?>
    </div>
    <div class="mdn-markdown-footer-flex-end">
        <div><button type="button" class="button button-secondary mdn-delete-button" data-name="mdn-note-delete" data-note-id="<?= $id ?>">delete</button></div>
        <div><button type="button" class="button button-primary" data-name="mdn-note-edit" data-note-id="<?= $id ?>">edit</button></div>
    </div>

    <?php
    $rsp = [
        'status' => 'success',
        'content' => ob_get_clean()
    ];

    echo json_encode($rsp);
    die();
}

add_action( 'wp_ajax_nopriv_mdn_update_form_content', 'mdn_update_form_content' );
add_action( 'wp_ajax_mdn_update_form_content', 'mdn_update_form_content' );
function mdn_update_form_content() {
    $id = absint($_POST['id']);

    $post = get_post($id);
    $content = $post->post_content;

    $text_content_id = 'mdn_note_update_text_content_' . $id;
    $text_count_id = 'mdn_note_update_text_count_' . $id;

    ob_start(); ?>

    <div class="mdn-markdown-header-flex-end">
        <div>Press <code>CTRL</code> + <code>ENTER</code> to save</div>
    </div>
    <textarea id="<?= $text_content_id ?>" rows="8" placeholder="Write your Markdown here ..." style="width: 100%;" spellcheck="false"><?= $content ?></textarea>
    <div class="mdn-markdown-footer-space-between">
        <div>Learn more about <a href="https://commonmark.org/help/" target="_blank"><b>Markdown</b></a>.</div>
        <div class="mdn-text-muted"><span id="<?= $text_count_id ?>"><?= strlen($content); ?></span> / 5000</div>
    </div>

    <?php
    $rsp = [
        'status' => 'success',
        'textContentId' => $text_content_id,
        'textCountId' => $text_count_id,
        'html' => ob_get_clean()
    ];

    echo json_encode($rsp);
    die();
}

add_action( 'wp_ajax_nopriv_mdn_delete_note', 'mdn_delete_note' );
add_action( 'wp_ajax_mdn_delete_note', 'mdn_delete_note' );
function mdn_delete_note() {
    $post_id = absint($_POST['noteId']);
    if (get_post_type($post_id) != 'mdn_note') {
        die();
    }
    wp_delete_post($post_id, true);

    $rsp = [
        'status' => 'success'
    ];

    echo json_encode($rsp);
    die();
}