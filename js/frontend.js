jQuery(document).ready(function($) {
    var waitUpdate = false;

    function refreshLiveBlog() {
        $.post(dvliveblog_enqueue_refresher_obj.ajax_url, {
            _ajax_nonce: dvliveblog_enqueue_refresher_obj.nonce,
            action: "dvliveblog_frontend_handler",
            lastID: dvliveblog_lastID,
            postID: dvliveblog_postID
        }, function(data) {
            console.log(data);
            if (data.refresh) {
                $("#dvliveblog_post_cont").prepend(data.content);
                $(".dvliveblog_post_hid").each(function() {
                    $(this).show(250);
                    $(this).toggleClass('dvliveblog_post_hid');
                    $(this).toggleClass('dvliveblog_post');
                })
                $(".dvliveblog_deleteBT").click(function() {
                    deleteLiveBlog($(this));
                });
                dvliveblog_lastID = data.lastID;
            }
        });
    }

    function updateLiveBlog() {
        waitUpdate = true;
        $.post(dvliveblog_enqueue_refresher_obj.ajax_url, {
            _ajax_nonce: dvliveblog_enqueue_refresher_obj.nonce,
            action: "dvliveblog_frontend_putter",
            postID: dvliveblog_postID,
            content: $("#dvliveblog_editor_text").val()
        }, function(data) {
            if(data.result) {
                $("#dvliveblog_editor_text").val("");
            } else {
                alert("Errore nell'invio dell'aggiornamento");
            }
            waitUpdate = false;
        });
    }

    function deleteLiveBlog(el) {
        waitUpdate = true;
        $.post(dvliveblog_enqueue_refresher_obj.ajax_url, {
            _ajax_nonce: dvliveblog_enqueue_refresher_obj.nonce,
            action: "dvliveblog_frontend_deleter",
            postID: dvliveblog_postID,
            itemID: el.data('ppalli-itemid')
        }, function(data) {
            if(data.result) {
                el.closest(".dvliveblog_post").remove();
            } else {
                alert("Errore nella cancellazione");
            }
            waitUpdate = false;
        });
    }

    $("#dvliveblog_editor_submit").click(function () {
        if (!waitUpdate) { updateLiveBlog(); }
    });

    $(".dvliveblog_deleteBT").click(function() {
        deleteLiveBlog($(this));
    });

    $("#dvliveblog_editor_text").focus(function() {
        $(document).on('keydown',function(e) {
            if(!waitUpdate && e.ctrlKey && e.which == 13) {
                updateLiveBlog();
            }
        });
    })

    refreshLiveBlogTimer = setInterval(refreshLiveBlog, dvliveblog_refreshrate);

});
