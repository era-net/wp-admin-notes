jQuery(document).ready(($) => {
    check_version($);

    $('div[id^="mdn_note_"]').each((_, el) => {
        $(el).on("mouseenter", () => {
            const footer = $(el).find('.mdn-footer-actions')[0];
            $(footer).addClass('mdn-fadein');
            $(el).on("mouseleave", () => {
                $(footer).removeClass("mdn-fadein");
            });
        });
    });

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
                        $("#" + re.saveBtn).remove();
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

                $("#" + re.saveBtn).on("click", () => {
                    $("#" + re.cancelBtn).remove();
                    $("#" + re.saveBtn).remove();
                    $("#" + re.titleInput).prop("disabled", true);
                    $("#" + re.textContentId).prop("disabled", true);
                    re.titleContent = $("#" + re.titleInput).val();
                    re.textContent = $("#" + re.textContentId).val();
                    delete re.note;
                    mdn_save_handler($, re);
                });

                $("#" + re.titleInput).on("keydown", (e) => {
                    if (e.ctrlKey && e.keyCode == 13) {
                        $("#" + re.cancelBtn).remove();
                        $("#" + re.saveBtn).remove();
                        $("#" + re.titleInput).trigger("blur");
                        $("#" + re.titleInput).prop("disabled", true);
                        $("#" + re.textContentId).prop("disabled", true);
                        re.titleContent = $("#" + re.titleInput).val();
                        re.textContent = $("#" + re.textContentId).val();
                        delete re.note;
                        mdn_save_handler($, re);
                    }
                });

                // update charcount on keyup
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
                mdn_revoke_footer_actions($);
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
}

function mdn_revoke_edit_listeners($) {
    $('button[data-name="mdn-note-edit"]').each((_, el) => {
        $(el).off("click");
    });

    $('button[data-name="mdn-note-edit"]').each((_, el) => {
        $(el).on("click", () => {
            $(el).prop("disabled", true);
            const noteId = $(el).data("note-id");
            mdn_handle_update_state($, noteId);
        });
    });
}

function mdn_revoke_footer_actions($) {
    $('div[id^="mdn_note_"]').each((_, el) => {
        $(el).off("mouseenter");
    });

    $('div[id^="mdn_note_"]').each((_, el) => {
        $(el).on("mouseenter", () => {
            const footer = $(el).find('.mdn-footer-actions')[0];
            $(footer).addClass('mdn-fadein');
            $(el).on("mouseleave", () => {
                $(footer).removeClass("mdn-fadein");
            });
        });
    });
}

function mdn_update_handler($, obj) {
    $.ajax({
        url: ajaxurl,
        method: "POST",
        data: {action: "mdn_update_note", id: obj.noteId, title: $(obj.input).val(), content: $(obj.textArea).val()},
        success: (re) => {
            obj.formBody.html("");
            obj.formBody.html(re.content);
            $(obj.input).hide();
            $(obj.h2).text($(obj.input).val());
            $(obj.input).remove();
            $(obj.controlsDiv).remove();
            $(obj.h2).removeClass("mdn-header-edit-state");
            $(obj.h2).addClass("hndle ui-sortable-handle");
            $(obj.h2).next().show();
            mdn_revoke_edit_listeners($);
            mdn_revoke_delete_listeners($);
            mdn_handle_checkboxes($);
        }
    });
}

function mdn_handle_update_state($, noteId) {
    const widget = $("#mdn_note_" + noteId);
    $(widget).find("[data-name=mdn-note-delete]:first").prop("disabled", true);
    const title = $(widget).find("h2:first");
    const formBody = $(widget).find("div.inside:first");
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
            
            // control buttons
            const header = $(widget).find("div.postbox-header:first");
            const controlsDiv = document.createElement("div");
            const controlCancel = document.createElement("button");
            const controlSave = document.createElement("button");

            $(controlCancel).addClass("button button-secondary mdn-cancle-button");
            $(controlSave).addClass("button button-primary mdn-save-button");

            $(controlSave).prop("title", "[Ctrl + Enter]");

            $(controlCancel).text(re.controlLocale.cancel);
            $(controlSave).text(re.controlLocale.save);

            $(controlsDiv).append(controlCancel);
            $(controlsDiv).append(controlSave);

            $(header).append(controlsDiv);

            if (re.status === 'success') {
                $(title).append(input);
                $(title).next().hide();
                formBody.append(re.html);
                const textArea = $('#' + re.textContentId);

                const obj = {
                    "noteId": noteId,
                    "h2": title,
                    "input": input,
                    "textArea": textArea,
                    "formBody": formBody,
                    "controlsDiv": controlsDiv
                };

                $(textArea).on("keydown", (e) => {
                    if (e.ctrlKey && e.keyCode == 13) {
                        $(textArea).prop("disabled", true);
                        $(input).prop("disabled", true);
                        $(textArea).trigger("blur");

                        mdn_update_handler($, obj);
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

                        mdn_update_handler($, obj);
                    }
                });

                $(controlSave).on("click", () => {
                    $(input).prop("disabled", true);
                    $(textArea).prop("disabled", true);
                    $(controlCancel).prop("disabled", true);

                    mdn_update_handler($, obj);
                });

                // cancel update
                $(controlCancel).on("click", () => {
                    $(controlsDiv).remove();
                    $(title).html("");
                    $(title).text(titleText);
                    $(title).removeClass("mdn-header-edit-state");
                    $(title).addClass("hndle ui-sortable-handle");
                    $(title).next().show();
                    $(formBody).html(re.cancelContent);
                    mdn_revoke_edit_listeners($);
                    mdn_revoke_delete_listeners($);
                    mdn_handle_checkboxes($);
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
            if (confirm($(el).data("confirm-message")) == true) {
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