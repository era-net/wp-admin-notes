jQuery(document).ready(($) => {
    $( document.body ).on( 'click', '.wpn-notes-add-note, #wpn-notes-add a', (e) => {
        $.ajax({
            url: ajaxurl,
            method: "POST",
            data: { action: 'wpn_add_new_note' },
            success: (re) => {
                re = jQuery.parseJSON( re );
                $("#normal-sortables").prepend(re.note);
            }
        });

        e.preventDefault();
    });
});