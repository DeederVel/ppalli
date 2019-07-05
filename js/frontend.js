jQuery(document).ready(function($) {
    var waitUpdate = false;

    function refreshLiveBlog() {
        $(".dvliveblog_post_spinner").toggleClass('fa-spin');
        $.post(dvliveblog_enqueue_refresher_obj.ajax_url, {
            _ajax_nonce: dvliveblog_enqueue_refresher_obj.nonce,
            action: "dvliveblog_frontend_handler",
            lastID: dvliveblog_lastID,
            postID: dvliveblog_postID
        }, function(data) {
            if (data.remove != false) {
                data.remove.forEach(function(v,k) {
                    $(".dvliveblog_post[data-ppalli-itemid="+v+"]").remove();
                });
                dvliveblog_lastID = data.lastID;
            }
            if (data.content != false) {
                $("#dvliveblog_post_cont").prepend(data.content);
                $(".dvliveblog_post_hid").each(function() {
                    $(this).show(250);
                })
                $(".dvliveblog_deleteBT").click(function() {
                    deleteLiveBlog($(this));
                });
                dvliveblog_lastID = data.lastID;
            }
            d = new Date();
            updateTimestamp = d.getHours()+":"+d.getMinutes()+":"+d.getSeconds();
            $("#dvliveblog_post_lastup").html(updateTimestamp);
            $(".dvliveblog_post_spinner").toggleClass('fa-spin');
        });
    }

    function changeIconEditor() {
        $(".dvliveblog_editor_submit_ic").toggleClass("fa-sync");
        $(".dvliveblog_editor_submit_ic").toggleClass("fa-check-circle");
    }

    function updateLiveBlog() {
        waitUpdate = true;
        $(".dvliveblog_editor_submit_ic").toggleClass("fa-spin");
        tinymce.triggerSave();
        $.post(dvliveblog_enqueue_refresher_obj.ajax_url, {
            _ajax_nonce: dvliveblog_enqueue_refresher_obj.nonce,
            action: "dvliveblog_frontend_putter",
            postID: dvliveblog_postID,
            type: $("#dvliveblog_editor_type").val(),
            content: $("#dvliveblog_editor_text").val()
        }, function(data) {
            $(".dvliveblog_editor_submit_ic").toggleClass("fa-spin");
            if(data.result) {
                changeIconEditor();
                setTimeout(changeIconEditor, 2000);
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
            }
            waitUpdate = false;
        });
    }

    $("#dvliveblog_editor_submit").click(function () {
        if (!waitUpdate) { updateLiveBlog(); }
    });

    $(".dvliveblog_deleteBT").click(function() {
        if (!waitUpdate) { deleteLiveBlog($(this)); }
    });

    $("#dvliveblog_editor_text").focus(function() {
        $(document).on('keydown',function(e) {
            if(!waitUpdate && e.ctrlKey && e.which == 13) {
                updateLiveBlog();
            }
        });
    })

    try {
        refreshLiveBlogTimer = setInterval(refreshLiveBlog, dvliveblog_refreshrate);
    } catch(dvliveblog_err) {
        
    }

    tinymce.init({
        selector: '#dvliveblog_editor_text',
        plugins: [
            'advlist autolink link image lists charmap anchor ',
            'wordcount visualblocks media',
            'save table contextmenu directionality emoticons paste textcolor'
        ],
        toolbar: 'undo redo | alignleft aligncenter alignright | cut copy paste selectall | bold italic underline strikethrough | subscript superscript removeformat formats | link image',

    });
});
