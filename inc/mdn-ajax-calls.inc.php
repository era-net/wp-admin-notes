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

function mdn_md_to_html($md) {
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

    $html = $converter->convert($md)->getContent();

    return stripslashes($html);
}

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

        ob_start() ?>
        <div class="mdn-markdown-content">
            <?= mdn_md_to_html($note_content) ?>
        </div>
        <div class="mdn-footer">
            <div class="mdn-footer-actions">
                <div class="mdn-footer-inside">
                    <button type="button" class="mdn-footer-action-btn" data-name="mdn-note-delete" data-confirm-message="<?= sprintf(__( 'Sure to delete &quot;%s&quot; ?', 'mdn-notes' ), $title_content ) ?>" data-note-id="<?= $note_id ?>" title="<?= __( 'delete', 'mdn-notes' ) ?>">
                        <svg width="16px" height="16px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M3 6.98996C8.81444 4.87965 15.1856 4.87965 21 6.98996" stroke="#fff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M8.00977 5.71997C8.00977 4.6591 8.43119 3.64175 9.18134 2.8916C9.93148 2.14146 10.9489 1.71997 12.0098 1.71997C13.0706 1.71997 14.0881 2.14146 14.8382 2.8916C15.5883 3.64175 16.0098 4.6591 16.0098 5.71997" stroke="#fff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M12 13V18" stroke="#fff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M19 9.98999L18.33 17.99C18.2225 19.071 17.7225 20.0751 16.9246 20.8123C16.1266 21.5494 15.0861 21.9684 14 21.99H10C8.91389 21.9684 7.87336 21.5494 7.07541 20.8123C6.27745 20.0751 5.77745 19.071 5.67001 17.99L5 9.98999" stroke="#fff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                    <button type="button" class="mdn-footer-action-btn" data-name="mdn-note-edit" data-note-id="<?= $note_id ?>" title="<?= __( 'edit', 'mdn-notes' ) ?>">
                        <svg width="16px" height="16px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M21.2799 6.40005L11.7399 15.94C10.7899 16.89 7.96987 17.33 7.33987 16.7C6.70987 16.07 7.13987 13.25 8.08987 12.3L17.6399 2.75002C17.8754 2.49308 18.1605 2.28654 18.4781 2.14284C18.7956 1.99914 19.139 1.92124 19.4875 1.9139C19.8359 1.90657 20.1823 1.96991 20.5056 2.10012C20.8289 2.23033 21.1225 2.42473 21.3686 2.67153C21.6147 2.91833 21.8083 3.21243 21.9376 3.53609C22.0669 3.85976 22.1294 4.20626 22.1211 4.55471C22.1128 4.90316 22.0339 5.24635 21.8894 5.5635C21.7448 5.88065 21.5375 6.16524 21.2799 6.40005V6.40005Z" stroke="#fff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M11 4H6C4.93913 4 3.92178 4.42142 3.17163 5.17157C2.42149 5.92172 2 6.93913 2 8V18C2 19.0609 2.42149 20.0783 3.17163 20.8284C3.92178 21.5786 4.93913 22 6 22H17C19.21 22 20 20.2 20 18V13" stroke="#fff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                </div>
            </div>
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

    ob_start(); ?>

    <div class="mdn-markdown-content">
        <?= mdn_md_to_html($content) ?>
    </div>
    <div class="mdn-footer">
        <div class="mdn-footer-actions">
            <div class="mdn-footer-inside">
                <button type="button" class="mdn-footer-action-btn" data-name="mdn-note-delete" data-confirm-message="<?= sprintf(__( 'Sure to delete &quot;%s&quot; ?', 'mdn-notes' ), $title ) ?>" data-note-id="<?= $id ?>" title="<?= __( 'delete', 'mdn-notes' ) ?>">
                    <svg width="16px" height="16px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M3 6.98996C8.81444 4.87965 15.1856 4.87965 21 6.98996" stroke="#fff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M8.00977 5.71997C8.00977 4.6591 8.43119 3.64175 9.18134 2.8916C9.93148 2.14146 10.9489 1.71997 12.0098 1.71997C13.0706 1.71997 14.0881 2.14146 14.8382 2.8916C15.5883 3.64175 16.0098 4.6591 16.0098 5.71997" stroke="#fff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M12 13V18" stroke="#fff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M19 9.98999L18.33 17.99C18.2225 19.071 17.7225 20.0751 16.9246 20.8123C16.1266 21.5494 15.0861 21.9684 14 21.99H10C8.91389 21.9684 7.87336 21.5494 7.07541 20.8123C6.27745 20.0751 5.77745 19.071 5.67001 17.99L5 9.98999" stroke="#fff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
                <button type="button" class="mdn-footer-action-btn" data-name="mdn-note-edit" data-note-id="<?= $id ?>" title="<?= __( 'edit', 'mdn-notes' ) ?>">
                    <svg width="16px" height="16px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M21.2799 6.40005L11.7399 15.94C10.7899 16.89 7.96987 17.33 7.33987 16.7C6.70987 16.07 7.13987 13.25 8.08987 12.3L17.6399 2.75002C17.8754 2.49308 18.1605 2.28654 18.4781 2.14284C18.7956 1.99914 19.139 1.92124 19.4875 1.9139C19.8359 1.90657 20.1823 1.96991 20.5056 2.10012C20.8289 2.23033 21.1225 2.42473 21.3686 2.67153C21.6147 2.91833 21.8083 3.21243 21.9376 3.53609C22.0669 3.85976 22.1294 4.20626 22.1211 4.55471C22.1128 4.90316 22.0339 5.24635 21.8894 5.5635C21.7448 5.88065 21.5375 6.16524 21.2799 6.40005V6.40005Z" stroke="#fff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M11 4H6C4.93913 4 3.92178 4.42142 3.17163 5.17157C2.42149 5.92172 2 6.93913 2 8V18C2 19.0609 2.42149 20.0783 3.17163 20.8284C3.92178 21.5786 4.93913 22 6 22H17C19.21 22 20 20.2 20 18V13" stroke="#fff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
            </div>
        </div>
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
        'controlLocale' => [
            'cancel' => __( 'cancel', 'mdn-notes' ),
            'save' => __( 'save', 'mdn-notes' )
        ],
        'html' => ob_get_clean()
    ];

    ob_start(); ?>

    <div class="mdn-markdown-content"><?= mdn_md_to_html($content) ?></div>
    <div class="mdn-footer">
        <div class="mdn-footer-actions">
            <div class="mdn-footer-inside">
                <button type="button" class="mdn-footer-action-btn" data-name="mdn-note-delete" data-confirm-message="<?= sprintf(__( 'Sure to delete &quot;%s&quot; ?', 'mdn-notes' ), $post->post_title ) ?>" data-note-id="<?= $id ?>" title="<?= __( 'delete', 'mdn-notes' ) ?>">
                    <svg width="16px" height="16px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M3 6.98996C8.81444 4.87965 15.1856 4.87965 21 6.98996" stroke="#fff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M8.00977 5.71997C8.00977 4.6591 8.43119 3.64175 9.18134 2.8916C9.93148 2.14146 10.9489 1.71997 12.0098 1.71997C13.0706 1.71997 14.0881 2.14146 14.8382 2.8916C15.5883 3.64175 16.0098 4.6591 16.0098 5.71997" stroke="#fff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M12 13V18" stroke="#fff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M19 9.98999L18.33 17.99C18.2225 19.071 17.7225 20.0751 16.9246 20.8123C16.1266 21.5494 15.0861 21.9684 14 21.99H10C8.91389 21.9684 7.87336 21.5494 7.07541 20.8123C6.27745 20.0751 5.77745 19.071 5.67001 17.99L5 9.98999" stroke="#fff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
                <button type="button" class="mdn-footer-action-btn" data-name="mdn-note-edit" data-note-id="<?= $id ?>" title="<?= __( 'edit', 'mdn-notes' ) ?>">
                    <svg width="16px" height="16px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M21.2799 6.40005L11.7399 15.94C10.7899 16.89 7.96987 17.33 7.33987 16.7C6.70987 16.07 7.13987 13.25 8.08987 12.3L17.6399 2.75002C17.8754 2.49308 18.1605 2.28654 18.4781 2.14284C18.7956 1.99914 19.139 1.92124 19.4875 1.9139C19.8359 1.90657 20.1823 1.96991 20.5056 2.10012C20.8289 2.23033 21.1225 2.42473 21.3686 2.67153C21.6147 2.91833 21.8083 3.21243 21.9376 3.53609C22.0669 3.85976 22.1294 4.20626 22.1211 4.55471C22.1128 4.90316 22.0339 5.24635 21.8894 5.5635C21.7448 5.88065 21.5375 6.16524 21.2799 6.40005V6.40005Z" stroke="#fff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M11 4H6C4.93913 4 3.92178 4.42142 3.17163 5.17157C2.42149 5.92172 2 6.93913 2 8V18C2 19.0609 2.42149 20.0783 3.17163 20.8284C3.92178 21.5786 4.93913 22 6 22H17C19.21 22 20 20.2 20 18V13" stroke="#fff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <?php

    $rsp['cancelContent'] = stripslashes(ob_get_clean());

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