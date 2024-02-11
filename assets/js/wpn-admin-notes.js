jQuery(document).ready(($) => {
    $( document.body ).on( 'click', '.wpn-notes-add-note, #wpn-notes-add a', (e) => {
        $.ajax({
            url: ajaxurl,
            method: "POST",
            data: { action: 'wpn_add_new_note' },
            success: (re) => {
                re = jQuery.parseJSON( re );
                $("#normal-sortables").prepend(re.note);
                $("#" + re.textContentId).trigger("focus");
                $("#" + re.textContentId).on("keydown", (e) => {
                    if (e.ctrlKey && e.keyCode == 13) {
                        $("#" + re.textContentId).prop("disabled", true);
                        $("#" + re.textContentId).trigger("blur");
                        re.textContent = $("#" + re.textContentId).val();
                        delete re.note;
                        wpn_save_handler($, re);
                    }
                    if(e.keyCode==9 || e.which==9) {
                        let textarea = document.getElementById(re.textContentId);
                        e.preventDefault();
                        textarea.setRangeText(
                            '\t',
                            textarea.selectionStart,
                            textarea.selectionStart,
                            'end'
                        )
                    }
                });
                $("#" + re.textContentId).on("keyup", () => {
                    console.log($("#" + re.textContentId).val().length);
                });
            }
        });

        e.preventDefault();
    });
});

function wpn_save_handler($, obj) {
    const data = obj;
    $.ajax({
        url: ajaxurl,
        method: "POST",
        data: { action: 'wpn_save_note', data: data },
        success: (re) => {
            re = jQuery.parseJSON( re );
            if (re.status === 'success') {
                $("#" + obj.contentId).html(re.content);
                $('.wpn-markdown-content ul').each((_, el) => {
                    $(el).find("li").children().each((_, el) => {
                        console.log(el.nodeName);
                        if (el.nodeName === "INPUT") {
                            $(el).parent().parent().css({"list-style": "none", "margin-left": "0"});
                            $(el).css({"pointer-events": "none"});
                            el.disabled = false;
                            return;
                        }
                    });
                });
            } else if (re.status === 'large_content') {
                alert("Content exceeded ... Max. 2500 characters");
                $("#" + obj.textContentId).prop("disabled", false);
                $("#" + obj.textContentId).trigger("focus");
            }
        }
    });
}