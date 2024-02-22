jQuery(document).ready(($) => {
    check_version($);
    mdn_handle_checkboxes($);
    $( document.body ).on( 'click', '.mdn-notes-add-note, #mdn-notes-add a', (e) => {
        $.ajax({
            url: ajaxurl,
            method: "POST",
            data: { action: 'mdn_add_new_note' },
            success: (re) => {
                $("#normal-sortables").prepend(re.note);
                $("#" + re.textContentId).trigger("focus");
                $("#" + re.textContentId).on("keydown", (e) => {
                    if (e.ctrlKey && e.keyCode == 13) {
                        $("#" + re.cancelBtn).remove();
                        $("#" + re.textContentId).trigger("blur");
                        $("#" + re.textContentId).prop("disabled", true);
                        re.titleContent = $("#" + re.titleInput).val();
                        re.textContent = $("#" + re.textContentId).val();
                        delete re.note;
                        mdn_save_handler($, re);
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
                $("#" + re.titleInput).on("keydown", (e) => {
                    if (e.ctrlKey && e.keyCode == 13) {
                        $("#" + re.cancelBtn).remove();
                        $("#" + re.titleInput).trigger("blur");
                        $("#" + re.titleInput).prop("disabled", true);
                        re.titleContent = $("#" + re.titleInput).val();
                        re.textContent = $("#" + re.textContentId).val();
                        delete re.note;
                        mdn_save_handler($, re);
                    }
                });
                $("#" + re.textContentId).on("keyup", () => {
                    $("#" + re.textCountId).text($("#" + re.textContentId).val().length);
                });
                // cancle note
                $("#" + re.cancelBtn).on("click", () => {
                    $("#" + re.widgetId).remove();
                });
            }
        });

        e.preventDefault();
    });

    mdn_init_edit_listeners($);

    mdn_init_delete_listeners($);
});

function check_version($) {
    $.ajax({
        url: ajaxurl,
        method: "POST",
        data: {action: "update_version"}
    });
}

function mdn_save_handler($, obj) {
    const data = obj;
    $("#" + obj.titleId).html($("#" + obj.titleInput).val());
    $("#" + obj.titleId).addClass("hndle ui-sortable-handle");
    $.ajax({
        url: ajaxurl,
        method: "POST",
        data: { action: 'mdn_save_note', data: data },
        success: (re) => {
            if (re.status === 'success') {
                $("#" + obj.contentId).html(re.content);
                mdn_revoke_edit_listeners($);
                mdn_revoke_delete_listeners($);
                mdn_handle_checkboxes($);
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
            mdn_handle_update_state($, noteId);
            return;
        });
    });
    $('div[id^="mdn_note_"]').each((_, el) => {
        $(el).on("dblclick", () => {
            $(el).off("dblclick");
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
            $("#mdn_note_" + $(el).data("note-id")).off("dblclick");
            $("#mdn_note_content_" + $(el).data("note-id")).off("dblclick");
            $("#mdn_note_update_text_content_" + $(el).data("note-id")).off("dblclick");
            $(el).prop("disabled", true);
            const noteId = $(el).data("note-id");
            mdn_handle_update_state($, noteId);
        });
    });
    $('div[id^="mdn_note_"]').each((_, el) => {
        $(el).on("dblclick", () => {
            $(el).off("dblclick");
            const split = el.id.split("_");
            const noteId = split[split.length-1];
            mdn_handle_update_state($, noteId);
        });
    });
}

function mdn_handle_update_state($, noteId) {
    const widget = $("#mdn_note_" + noteId);
    $(widget).off("dblclick");
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
            if (re.status === 'success') {
                $(title).append(input);
                $(title).next().hide();
                formBody.append(re.html);
                const textArea = $('#' + re.textContentId);
                $(textArea).off("dblclick");
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
                                formBody.html(re.content);
                                $(input).hide();
                                $(title).text($(input).val());
                                $(input).remove();
                                $(title).addClass("hndle ui-sortable-handle");
                                $(title).next().show();
                                mdn_revoke_edit_listeners($);
                                mdn_revoke_delete_listeners($);
                                mdn_handle_checkboxes($);
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

function mdn_handle_checkboxes($) {
    $('.mdn-markdown-content ul').each((_, el) => {
        $(el).find("li").children().each((_, el) => {
            if (el.nodeName === "INPUT") {
                el.disabled = false;
                return;
            }
        });
    });
}