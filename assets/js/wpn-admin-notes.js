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
                    if (e.keyCode==9 || e.which==9) {
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
                    $("#" + re.textCountId).text($("#" + re.textContentId).val().length);
                });
            }
        });

        e.preventDefault();
    });

    mdn_init_edit_listeners($);

    mdn_init_delete_listeners($);
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
                        if (el.nodeName === "INPUT") {
                            $(el).parent().parent().css({"list-style": "none", "margin-left": "0"});
                            $(el).css({"pointer-events": "none"});
                            el.disabled = false;
                            return;
                        }
                    });
                });
                mdn_init_delete_single_listener($, data);
            } else if (re.status === 'large_content') {
                alert("Content exceeded ... Max. 2500 characters");
                $("#" + obj.textContentId).prop("disabled", false);
                $("#" + obj.textContentId).trigger("focus");
            }
        }
    });
}

function mdn_init_edit_listeners($) {
    $('button[data-name="mdn-note-edit"]').each((_, el) => {
        $(el).on("click", () => {
            $(el).prop("disabled", true);
            const noteId = $(el).data("note-id");
            mdn_handle_update_state($, noteId, el);
            return;
        });
    });
    $('div[id^="mdn_note_"]').each((_, el) => {
        $(el).on("dblclick", () => {
            const split = el.id.split("_");
            const noteId = split[split.length-1];
            mdn_handle_update_state($, noteId);
        });
    });
}

function mdn_revoke_edit_listeners($) {
    $('button[data-name="mdn-note-edit"]').each((_, el) => {
        $(el).off("click");
    });
    $('div[id^="mdn_note_"]').each((_, el) => {
        $(el).off("dblclick");
    });

    $('button[data-name="mdn-note-edit"]').each((_, el) => {
        $(el).on("click", () => {
            $(el).prop("disabled", true);
            const noteId = $(el).data("note-id");
            mdn_handle_update_state($, noteId, el);
            return;
        });
    });
    $('div[id^="mdn_note_"]').each((_, el) => {
        $(el).on("dblclick", () => {
            const split = el.id.split("_");
            const noteId = split[split.length-1];
            mdn_handle_update_state($, noteId);
        });
    });
}

function mdn_handle_update_state($, noteId, edit_btn=null) {
    const widget = $("#mdn_note_" + noteId);
    const title = $(widget).find("h2:first");
    const formBody = $(widget).find("div.inside:first");
    const prevTitle = $(title).text();
    $(title).removeClass("hndle ui-sortable-handle");
    $(title).addClass("mdn-header-edit-state");
    const titleText = $(title).text();
    const input = document.createElement("input");
    input.type = "text";
    input.value = titleText;
    
    $.ajax({
        url: ajaxurl,
        method: "POST",
        data: {action: 'mdn_update_form_content', id: noteId},
        success: (re) => {
            $(title).html("");
            $(formBody).html("");
            re = jQuery.parseJSON( re );
            if (re.status === 'success') {
                $(title).append(input);
                $(title).next().hide();
                formBody.append(re.html);
                const textArea = $('#' + re.textContentId);
                $(textArea).on("keydown", (e) => {
                    if (e.ctrlKey && e.keyCode == 13) {
                        $("#" + re.textContentId).prop("disabled", true);
                        $("#" + re.textContentId).trigger("blur");
                        $.ajax({
                            url: ajaxurl,
                            method: "POST",
                            data: {action: 'mdn_update_note', id: noteId, title: $(input).val(), content: $(textArea).val()},
                            success: (re) => {
                                formBody.html("");
                                re = jQuery.parseJSON( re );
                                formBody.html(re.content);
                                $(input).hide();
                                $(title).text($(input).val());
                                $(input).remove();
                                $(title).addClass("hndle ui-sortable-handle");
                                $(title).next().show();
                                mdn_revoke_edit_listeners($);
                                mdn_revoke_delete_listeners($);
                            }
                        });
                    }
                    if (e.keyCode==9 || e.which==9) {
                        let textarea = document.getElementById(re.textContentId);
                        e.preventDefault();
                        textarea.setRangeText(
                            '\t',
                            textarea.selectionStart,
                            textarea.selectionStart,
                            'end'
                        );
                    }
                });
                $(input).on("keydown", (e) => {
                    if (e.ctrlKey && e.keyCode == 13) {
                        $(input).prop("disabled", true);
                        $(input).trigger("blur");
                        $(textArea).prop("disabled", true);
                        $.ajax({
                            url: ajaxurl,
                            method: "POST",
                            data: {action: 'mdn_update_note', id: noteId, title: $(input).val(), content: $(textArea).val()},
                            success: (re) => {
                                formBody.html("");
                                re = jQuery.parseJSON( re );
                                formBody.html(re.content);
                                $(input).hide();
                                $(title).text($(input).val());
                                $(input).remove();
                                $(title).addClass("hndle ui-sortable-handle");
                                $(title).next().show();
                                mdn_revoke_edit_listeners($);
                                mdn_revoke_delete_listeners($);
                            }
                        });
                    }
                });
                // update charcount on keyup
                $(textArea).on("keyup", () => {
                    $("#" + re.textCountId).text($("#" + re.textContentId).val().length);
                });
            }
        }
    });

    $(input).on("click", (e) => {
        e.stopPropagation();
    });
    $(input).on("dblclick", (e) => {
        e.stopPropagation();
    });
}

function mdn_init_delete_single_listener($, obj) {
    let deleteButton = $("#" + obj.widgetId).find('button[data-name="mdn-note-delete"]')
    $(deleteButton).on("click", () => {
        $(deleteButton).prop("disabled", true);
        const noteId = obj.noteId;
        const noteTitle = $("#" + obj.titleId).text();
        if (confirm('Sure to delete "' + noteTitle + '"?') == true) {
            $.ajax({
                url: ajaxurl,
                method: "POST",
                data: {action: 'mdn_delete_note', noteId: noteId},
                success: (re) => {
                    re = jQuery.parseJSON( re );
                    if (re.status === "success") {
                        $("#" + obj.widgetId).remove();
                    } else {
                        $(deleteButton).prop("disabled", false);
                    }
                }
            });
        } else {
            $(deleteButton).prop("disabled", false);
            return;
        }
    });
}

function mdn_revoke_delete_listeners($) {
    $('button[data-name="mdn-note-delete"]').each((_, el) => {
        $(el).off("click");
    });
    mdn_init_delete_listeners($);
}

function mdn_init_delete_listeners($) {
    $('button[data-name="mdn-note-delete"]').each((_, el) => {
        $(el).on("click", () => {
            $(el).prop("disabled", true);
            const noteId = $(el).data("note-id");
            const noteTitle = $("#mdn_note_" + noteId).find("h2:first").text();
            if (confirm('Sure to delete "' + noteTitle + '"?') == true) {
                $.ajax({
                    url: ajaxurl,
                    method: "POST",
                    data: {action: 'mdn_delete_note', noteId: noteId},
                    success: (re) => {
                        re = jQuery.parseJSON( re );
                        if (re.status === "success") {
                            $("#mdn_note_" + $(el).data("note-id")).remove();
                        } else {
                            $(el).prop("disabled", false);
                        }
                    }
                });
            } else {
                $(el).prop("disabled", false);
                return;
            }
        });
    });
}