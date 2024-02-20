jQuery(document).ready(($) => {
    skip_release_listener($);
});

/**
 * SKIPPING RELEASES
 * This method looks for the 'mdn-skip-release' button
 * and when a user clicks on it, the notice hides and
 * the database gets updated
 */
function skip_release_listener($) {
    if ($("#mdn-skip-release").length) {
        $("#mdn-skip-release").on("click", (el) => {
            $(el.target).off("click");
            $(el.target).text(skipRelease[0]);
            $(el.target).on("click", (el) => {
                const a = $(el.target).prev()[0];
                $(a).attr("disabled", true);
                $(el.target).prop("disabled", true);
                $(el.target).off("click");
                $.ajax({
                    url: ajaxurl,
                    method: "POST",
                    data: {action: "mdn_skip_release"},
                    success: (re) => {
                        if (re.status === "success") {
                            $("#mdn-admin-update-notice").remove();
                        }
                    }
                });
            });
        });
    }
}