<?php
require_once plugin_dir_path(__FILE__) . '../vendor/autoload.php';

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\CommonMark\Node\Inline\Link;
use League\CommonMark\Extension\CommonMark\Node\Block\ListBlock;
use League\CommonMark\Extension\CommonMark\Node\Block\ListItem;
use League\CommonMark\Extension\DefaultAttributes\DefaultAttributesExtension;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\Extension\TaskList\TaskListItemMarker;
use League\CommonMark\MarkdownConverter;

/**
 * GETTING THE CONTENTS FOR A NEW NOTE
 */
add_action( 'wp_ajax_nopriv_mdn_add_new_note', 'mdn_add_new_note' );
add_action( 'wp_ajax_mdn_add_new_note', 'mdn_add_new_note' );
function mdn_add_new_note() {
    global $wpdb;

    header("Content-Type: application/json");

    $table_name = $wpdb->prefix . 'posts';
    $get_id = $wpdb->get_row("SHOW TABLE STATUS LIKE '$table_name'");
    $next_id = $get_id->Auto_increment;

    $note_id = absint($next_id);
    $widget_id = 'mdn_note_' . $note_id;
    $title_id = 'mdn_note_title_' . $note_id;
    $title_input = 'mdn_note_title_ipt_' . $note_id;
    $cancel_btn = 'mdn_cancel_btn_' . $note_id;
    $save_btn = 'mdn_save_btn_' . $note_id;
    $content_id = 'mdn_note_content_' . $note_id;
    $text_content_id = 'mdn_note_text_content_' . $note_id;
    $text_count_id = 'mdn_note_text_count_' . $note_id;

    ob_start(); ?>

    <div id="<?= $widget_id ?>" class="postbox">
        <div class="postbox-header">
            <h2 id="<?= $title_id ?>" class="mdn-header-edit-state">
                <input type="text" id="<?= $title_input ?>" value="<?= __( 'New Note', 'mdn-notes' ) ?>">
            </h2>
            <div>
                <button id="<?= $cancel_btn ?>" class="button button-secondary mdn-cancle-button" tabindex="-1"><?= __( 'cancel', 'mdn-notes' ) ?></button>
                <button id="<?= $save_btn ?>" class="button button-primary mdn-save-button" tabindex="-1" title="[Ctrl + Enter]"><?= __( 'save', 'mdn-notes' ) ?></button>
            </div>
        </div>
        <div id="<?= $content_id ?>" class="inside">
            <textarea id="<?= $text_content_id ?>" rows="8" placeholder="<?= __( 'Write your Markdown here ...', 'mdn-notes' ) ?>" style="width: 100%;"></textarea>
            <div class="mdn-markdown-footer-space-between">
                <div><?= __( 'Learn more about', 'mdn-notes' ) ?> <a href="<?= __( 'https://commonmark.org/help/', 'mdn-notes' ) ?>" target="_blank"><b>Markdown</b></a>.</div>
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
        'titleInput' => $title_input,
        'cancelBtn' => $cancel_btn,
        'saveBtn' => $save_btn,
        'contentId' => $content_id,
        'textContentId' => $text_content_id,
        'textCountId' => $text_count_id
    ];
    echo json_encode($rsp);
    die();
}

/**
 * SAVING A NEW NOTE
 */
add_action( 'wp_ajax_nopriv_mdn_save_note', 'mdn_save_note' );
add_action( 'wp_ajax_mdn_save_note', 'mdn_save_note' );
function mdn_save_note() {

    header("Content-Type: application/json");

    $data = $_POST['data'];
    $title_content = sanitize_text_field($data["titleContent"]);
    $note_content = sanitize_textarea_field($data['textContent']);
    $char_count = strlen($note_content);

    if ($char_count <= 5000) {

        $args = [
            'post_status'  => 'publish',
            'post_type'    => 'mdn_note',
            'post_title'   => $title_content,
            'post_content' => $note_content
        ];

        $note_id = wp_insert_post($args);

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

        $converter = new MarkdownConverter($env);
        $html = $converter->convert($note_content)->getContent();
        ob_start() ?>
        <div class="mdn-markdown-content">
            <?= $html ?>
        </div>
        <div class="mdn-markdown-footer-flex-end">
            <div><button type="button" class="button button-secondary mdn-delete-button" data-name="mdn-note-delete" data-confirm-message="<?= sprintf(__( 'Sure to delete %s ?', 'mdn-notes' ), $title_content ) ?>" data-note-id="<?= $note_id ?>"><?= __( 'delete', 'mdn-notes' ) ?></button></div>
            <div><button type="button" class="button button-primary" data-name="mdn-note-edit" data-note-id="<?= $note_id ?>"><?= __( 'edit', 'mdn-notes' ) ?></button></div>
        </div>
        <?php
        $rsp = [
            'status' => 'success',
            'title' => $title_content,
            'content' => stripslashes(ob_get_clean()),
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

/**
 * UPDATING A NOTE
 */
add_action( 'wp_ajax_nopriv_mdn_update_note', 'mdn_update_note' );
add_action( 'wp_ajax_mdn_update_note', 'mdn_update_note' );
function mdn_update_note() {

    header("Content-Type: application/json");

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

    $converter = new MarkdownConverter($env);
    $html = $converter->convert($content)->getContent();

    ob_start(); ?>

    <div class="mdn-markdown-content">
        <?= $html ?>
    </div>
    <div class="mdn-markdown-footer-flex-end">
        <div><button type="button" class="button button-secondary mdn-delete-button" data-name="mdn-note-delete" data-confirm-message="<?= sprintf(__( 'Sure to delete %s ?', 'mdn-notes' ), $title ) ?>" data-note-id="<?= $id ?>"><?= __( 'delete', 'mdn-notes' ) ?></button></div>
        <div><button type="button" class="button button-primary" data-name="mdn-note-edit" data-note-id="<?= $id ?>"><?= __( 'edit', 'mdn-notes' ) ?></button></div>
    </div>

    <?php
    $rsp = [
        'status' => 'success',
        'content' => stripslashes(ob_get_clean())
    ];

    echo json_encode($rsp);
    die();
}

/**
 * GETTING THE CONTENTS TO UPDATE A NOTE
 */
add_action( 'wp_ajax_nopriv_mdn_update_form_content', 'mdn_update_form_content' );
add_action( 'wp_ajax_mdn_update_form_content', 'mdn_update_form_content' );
function mdn_update_form_content() {

    header("Content-Type: application/json");

    $id = absint($_POST['id']);

    $post = get_post($id);
    $content = $post->post_content;

    $text_content_id = 'mdn_note_update_text_content_' . $id;
    $text_count_id = 'mdn_note_update_text_count_' . $id;

    ob_start(); ?>

    <div class="mdn-markdown-header-flex-end">
        <div><?= __( 'Press', 'mdn-notes' ) ?> <code>CTRL</code> + <code>ENTER</code> <?= __( 'to save', 'mdn-notes' ) ?></div>
    </div>
    <textarea id="<?= $text_content_id ?>" rows="8" placeholder="<?= __( 'Write your Markdown here ...', 'mdn-notes' ) ?>" style="width: 100%;"><?= $content ?></textarea>
    <div class="mdn-markdown-footer-space-between">
        <div><?= __( 'Learn more about', 'mdn-notes' ) ?> <a href="<?= __( 'https://commonmark.org/help/', 'mdn-notes' ) ?>" target="_blank"><b>Markdown</b></a>.</div>
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

/**
 * DELETING A NOTE
 */
add_action( 'wp_ajax_nopriv_mdn_delete_note', 'mdn_delete_note' );
add_action( 'wp_ajax_mdn_delete_note', 'mdn_delete_note' );
function mdn_delete_note() {

    header("Content-Type: application/json");

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

/**
 * UPDATE NEWEST VERSION
 * Any time a user visits the dashboard the database option
 * 'mdn_latest' is updated to the latest version.
 */
add_action( 'wp_ajax_nopriv_update_version', 'update_version' );
add_action( 'wp_ajax_update_version', 'update_version' );
function update_version() {

    header("Content-Type: application/json");

    $url = "https://era-kast.ch/updater/wp-admin-notes.php";

    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($curl);
    curl_close($curl);

    $rsp = json_decode($response, true);

    update_option("mdn_latest", $rsp["version"]);

    $succ = [
        'status' => "success"
    ];

    echo json_encode($succ);
    die();
}

/**
 * SKIP RELEASE
 * When a user clicks and confirms the skip release button
 */
add_action( 'wp_ajax_nopriv_mdn_skip_release', 'mdn_skip_release' );
add_action( 'wp_ajax_mdn_skip_release', 'mdn_skip_release' );
function mdn_skip_release() {
    
    header("Content-Type: application/json");

    $latest = get_option("mdn_latest");

    update_option("mdn_release_skip", $latest);

    $succ = [
        'status' => "success"
    ];

    echo json_encode($succ);
    die();
}