<?php
require_once plugin_dir_path(__FILE__) . "../vendor/autoload.php";

use League\CommonMark\GithubFlavoredMarkdownConverter;

add_action( 'wp_ajax_nopriv_wpn_add_new_note', 'wpn_add_new_note' );
add_action( 'wp_ajax_wpn_add_new_note', 'wpn_add_new_note' );
function wpn_add_new_note() {

    $note_id = 5;
    $widget_id = "wpn_note_" . $note_id;
    $title_id = "wpn_note_title_" . $note_id;
    $content_id = "wpn_note_content_" . $note_id;
    $text_content_id = "wpn_note_text_content_" . $note_id;

    ob_start(); ?>

    <div id='<?= $widget_id ?>' class='postbox'>
        <div class="postbox-header">
            <h2 id="<?= $title_id ?>" class="hndle">My New Note</h2>
        </div>
        <div id="<?= $content_id ?>" class="inside">
            <textarea id="<?= $text_content_id ?>" rows="8" placeholder="Write your Markdown here ..." style="width: 100%;"></textarea>
            <div>
                <span style="float: right;">Learn more about <a href="https://commonmark.org/help/" target="_blank"><b>Markdown</b></a>.</span>
                <span>Press <code>CTRL</code> + <code>ENTER</code> to save!</span>
            </div>
        </div>
    </div>

    <?php
    $rsp = [
        "noteId" => $note_id,
        "note" => ob_get_clean(),
        "widgetId" => $widget_id,
        "titleId" => $title_id,
        "contentId" => $content_id,
        "textContentId" => $text_content_id
    ];
    echo json_encode($rsp);
    die();
}

add_action( 'wp_ajax_nopriv_wpn_save_note', 'wpn_save_note' );
add_action( 'wp_ajax_wpn_save_note', 'wpn_save_note' );
function wpn_save_note() {
    $data = $_POST["data"];
    $note_content = $data["textContent"];
    $char_count = strlen($note_content);

    if ($char_count <= 5000) {
        $conv_config = [
            'html_input' => 'escape',
            'allow_unsafe_links' => false,
            'max_nesting_level' => 12,
            'renderer' => [
                "soft_break" => "</br>"
            ]
        ];
        $converter = new GithubFlavoredMarkdownConverter($conv_config);
        $html = $converter->convert($note_content)->getContent();
        ob_start() ?>
        <div class="wpn-markdown-content">
            <?= $html ?>
        </div>
        <ul class="subsubsub">
            <li>hehe</li>
            <li>haha</li>
            <li>hoho</li>
        </ul>
        <?php
        $rsp = [
            "status" => "success",
            "content" => ob_get_clean(),
            "charCount" => $char_count
        ];
    } else {
        $rsp = [
            "status" => "large_content"
        ];
    }
    
    echo json_encode($rsp);
    die();
}