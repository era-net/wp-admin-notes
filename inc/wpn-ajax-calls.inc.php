<?php
require_once plugin_dir_path(__FILE__) . "../vendor/autoload.php";

use League\CommonMark\GithubFlavoredMarkdownConverter;

add_action( 'wp_ajax_nopriv_wpn_add_new_note', 'wpn_add_new_note' );
add_action( 'wp_ajax_wpn_add_new_note', 'wpn_add_new_note' );
function wpn_add_new_note() {

    // save widget

    $converter = new GithubFlavoredMarkdownConverter();

    ob_start(); ?>

    <div id='wpn_note' class='postbox'>
        <div class="postbox-header">
            <h2 class="hndle">My New Note</h2>
        </div>
        <div class="inside">
            <div>
                <p>hehe</p>
            </div>
        </div>
    </div>

    <?php
    $rsp = [
        "note" => ob_get_clean()
    ];
    echo json_encode($rsp);
    die();
}