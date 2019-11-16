<?php

namespace Ppalli;

define ('LIVEBLOG_PLUGINPATH', dirname( __FILE__ ));
define ('LIVEBLOG_DEFAULT_REFRESH', 5);

class Core {
    private $post;

    public function __construct(int $postID) {
        $this->post = $postID;
    }

    private function checkUserIsAble($user) {
        $userroles = (array) $user->roles;
        $userauth = ['administrator', 'editor', 'author'];
        return (count(array_intersect($userroles, $userauth)) > 0);
    }

    private function buildMoment($template, $moment, array $addClasses = []) {
        $time = (new \DateTime($moment->pub_time))->format('H:i:s'); //or H:i:s @ d/m/Y
        $author = get_user_by('id', $moment->author_id);
        if($moment->type > 0) {
        	$addClasses[] = "dvliveblog_post_type".$moment->type;
        }
        $template = str_replace('{{authorimg}}', get_avatar_url($author), $template);
        $template = str_replace('{{authorname}}', $author->display_name, $template);
        $template = str_replace('{{time}}', $time, $template);
        $template = str_replace('{{content}}', stripslashes($moment->content), $template);
        if ( $this->checkUserIsAble(wp_get_current_user()) ) {
        	$template = str_replace('{{adminactions}}', file_get_contents(LIVEBLOG_PLUGINPATH . '/html/admin_actions.html'), $template);
            $template = str_replace('{{str_remove}}', __('Remove', 'dvliveblog'), $template);
        } else {
        	$template = str_replace('{{adminactions}}', '', $template);
        }
        $template = str_replace('{{itemID}}', $moment->id, $template);
        $template = str_replace('{{additional_class}}', implode(" ", $addClasses), $template);
        return $template;
    }

    public function getAllMoments($title="", $refreshrate=5000) {
        global $wpdb;
        $blogposts = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}dvliveblog WHERE post_id = $this->post AND type >= 0 ORDER BY id DESC");
        $lastID = 0;
        $ret = '';

        $mainTemplate = file_get_contents(LIVEBLOG_PLUGINPATH . '/html/main_template.html');
        $postTemplate = file_get_contents(LIVEBLOG_PLUGINPATH . '/html/post_template.html');
        $editorTemplate = file_get_contents(LIVEBLOG_PLUGINPATH . '/html/editor_template.html');
        $content = "";
        if(count($blogposts) > 0) {
            $lastID = ($blogposts[0])->id;
        }
        foreach($blogposts as $bp) {
            $content .= $this->buildMoment($postTemplate, $bp);
        }
        $ret = $mainTemplate;
        $li = '<script>
            var dvliveblog_lastID = '.$lastID.';
            var dvliveblog_postID = '.$this->post.';
            var dvliveblog_refreshrate = '.$refreshrate.';
        </script>
        ';
        $ret = str_replace('{{JSvars}}', $li, $ret);

        if ( $this->checkUserIsAble(wp_get_current_user()) ) {
            $ret = str_replace('{{editor}}', $editorTemplate, $ret);
            $ret = str_replace('{{str_update}}', __('Update', 'dvliveblog'), $ret);
            $ret = str_replace('{{str_option0}}', __('Normal', 'dvliveblog'), $ret);
            $ret = str_replace('{{str_option1}}', __('Important', 'dvliveblog'), $ret);
            $ret = str_replace('{{str_option2}}', __('Opinion', 'dvliveblog'), $ret);
        } else {
            $ret = str_replace('{{editor}}', '', $ret);
        }
        $ret = str_replace('{{title}}', $title, $ret);
        $ret = str_replace('{{str_lastupdate}}', __('Last live refresh:', 'dvliveblog'), $ret);
        $ret = str_replace('{{content}}', $content, $ret);
        $time = (new \DateTime('now'))->format('H:i:s');
        $ret = str_replace('{{last_update}}', $time, $ret);
        return ["content" => $ret, "lastID" => $lastID];
    }

    public function getNewMoments($lastIDold) {
        global $wpdb;
        $blogposts = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}dvliveblog WHERE post_id = $this->post AND id > $lastIDold ORDER BY pub_time DESC");
        $postTemplate = file_get_contents(LIVEBLOG_PLUGINPATH . '/html/post_template.html');
        $content = '';
        $removeArray = [];
        $lastID = ($blogposts[0])->id;

        foreach($blogposts as $bp) {
            if($bp->type == -1) {
                $removeArray[] = intval($bp->content);
            } else {
                $content .= $this->buildMoment($postTemplate, $bp, ['dvliveblog_post_hid']);
            }
        }
        $content = ($content != '') ? $content : false;
        $removeArray = (count($removeArray)>0) ? $removeArray : false;
        return ["content" => $content, "lastID" => $lastID, "remove" => $removeArray];
    }

    public function newMoment($user, $content, $type = 0) {
        global $wpdb;
        if($this->checkUserIsAble($user)) {
            $wpdb->query(
                $wpdb->prepare(
                    "
                    INSERT INTO {$wpdb->prefix}dvliveblog
                    ( post_id, type, content, author_id, pub_time )
                    VALUES ( %d, %d, %s, %d, CURRENT_TIME() )
                    ",
                    $this->post,
                    $type,
                    $content,
                    $user->ID
                )
            );

            return ["result" => true];
        } else {
            return ["result" => false];
        }
    }

    public function deleteMoment($itemID) {
        global $wpdb;
        $user = wp_get_current_user();
        if($this->checkUserIsAble($user)) {
            $wpdb->delete( ($wpdb->prefix.'dvliveblog') , [
                'id' => $itemID,
                'post_id' => $this->post,
            ]);
            $wpdb->query(
                $wpdb->prepare(
                    "
                    INSERT INTO {$wpdb->prefix}dvliveblog
                    ( post_id, type, content, author_id, pub_time )
                    VALUES ( %d, %d, %s, %d, CURRENT_TIME() )
                    ",
                    $this->post,
                    -1,
                    $itemID,
                    $user->ID
                )
            );

            return ["result" => true, "lastID" => $wpdb->insert_id];
        } else {
            return ["result" => false];
        }
    }
}
