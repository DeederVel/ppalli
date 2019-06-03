<?php
/**
 * Plugin Name: Ppalli - Liveblogging plugin
 * Plugin URI: https://deedervel.com
 * Description: Micro liveblogging for Wordpress
 * Version:    2019.06.01
 * Author:     Mattia "DeederVel" Dui
 * Author URI:  https://deedervel.com
 * License:    CC BY-NC-SA 4.0
 * License URI: http://creativecommons.org/licenses/by-nc-sa/4.0/
 * Text Domain: dvliveblog
 * Domain Path: /languages
 */

define ('LIVEBLOG_PLUGINPATH', dirname( __FILE__ ));

function checkUserIsAble($user) {
    $userroles = (array) $user->roles;
    $userauth = ['administrator', 'editor', 'author'];
    return (count(array_intersect($userroles, $userauth)) > 0);
}

function liveblog_render($title, $refreshrate) {
    global $wpdb, $post;
    $blogposts = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}dvliveblog WHERE post_id = $post->ID ORDER BY id DESC");
    $mainTemplate = file_get_contents(LIVEBLOG_PLUGINPATH . '/html/main_template.html');
    $postTemplate = file_get_contents(LIVEBLOG_PLUGINPATH . '/html/post_template.html');
    $editorTemplate = file_get_contents(LIVEBLOG_PLUGINPATH . '/html/editor_template.html');
    $content = "";
    $lastID = ($blogposts[0])->id;
    if(!isset($lastID)) $lastID = 0;
    foreach($blogposts as $bp) {
        $p = $postTemplate;
        $time = (new DateTime($bp->pub_time))->format('H:i @ d/m/Y');
        $author = get_user_by('id', $bp->author_id);
        $p = str_replace('{{authorimg}}', get_avatar_url($author), $p);
        $p = str_replace('{{authorname}}', $author->display_name, $p);
        $p = str_replace('{{time}}', $time, $p);
        $p = str_replace('{{content}}', $bp->content, $p);
        if ( checkUserIsAble(wp_get_current_user()) ) {
            $p = str_replace('{{adminactions}}', file_get_contents(LIVEBLOG_PLUGINPATH . '/html/admin_actions.html'), $p);
        } else {
            $p = str_replace('{{adminactions}}', '', $p);
        }
        $p = str_replace('{{itemID}}', $bp->id, $p);
        $content .= $p;
    }
    $ret = $mainTemplate;
    $li = '<script>
        var dvliveblog_lastID = '.$lastID.';
        var dvliveblog_postID = '.$post->ID.';
        var dvliveblog_refreshrate = '.$refreshrate.';
    </script>
    ';
    $ret = str_replace('{{JSvars}}', $li, $ret);

    if ( checkUserIsAble(wp_get_current_user()) ) {
        $ret = str_replace('{{editor}}', $editorTemplate, $ret);
    } else {
        $ret = str_replace('{{editor}}', '', $ret);
    }
    $ret = str_replace('{{title}}', $title, $ret);
    $ret = str_replace('{{content}}', $content, $ret);

    return ["content" => $ret, "lastID" => $lastID];
}

function liveblog_update($lastIDrem, $postIDrem) {
    global $wpdb;
    $blogposts = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}dvliveblog WHERE post_id = $postIDrem AND id > $lastIDrem ORDER BY pub_time DESC");
    $postTemplate = file_get_contents(LIVEBLOG_PLUGINPATH . '/html/post_template_hid.html');
    $content = "";
    $lastID = ($blogposts[0])->id;
    foreach($blogposts as $bp) {
        $p = $postTemplate;
        $time = (new DateTime($bp->pub_time))->format('H:i @ d/m/Y');
        $author = get_user_by('id', $bp->author_id);
        $p = str_replace('{{authorimg}}', get_avatar_url($author), $p);
        $p = str_replace('{{authorname}}', $author->display_name, $p);
        $p = str_replace('{{time}}', $time, $p);
        $p = str_replace('{{content}}', $bp->content, $p);
        if ( checkUserIsAble(wp_get_current_user()) ) {
            $p = str_replace('{{adminactions}}', file_get_contents(LIVEBLOG_PLUGINPATH . '/html/admin_actions.html'), $p);
        } else {
            $p = str_replace('{{adminactions}}', '', $p);
        }
        $p = str_replace('{{itemID}}', $bp->id, $p);
        $content .= $p;
    }
    $contentnew = (isset($lastID));
    return ["content" => $content, "lastID" => $lastID, "refresh" => $contentnew];
}

function liveblog_delete(int $itemID, int $postID) {
    global $wpdb;
    $user = wp_get_current_user();
    if(checkUserIsAble($user)) {
        $wpdb->delete( ($wpdb->prefix.'dvliveblog') , [
            'id' => $itemID,
            'post_id' => $postID,
        ]);

        return wp_send_json(["result" => true]);
    } else {
        return wp_send_json(["result" => false]);
    }
}

function liveblog_sc_handler($atts) {
    $title = $atts['title'];
    $secs = intval($atts['secs']);
    $refreshrate = ($secs > 0) ? ($secs * 1000) : 5000;
    wp_enqueue_style(
        'dvamazon',
        plugins_url( '/css/front.css', __FILE__ )
    );
    $lu = liveblog_render($title, $refreshrate);
    return $lu["content"];
}

function dvliveblog_frontend_handler() {
    $lastID = intval($_POST["lastID"]);
    $postID = intval($_POST["postID"]);
    $ret = liveblog_update($lastID, $postID);
    return wp_send_json($ret);
}

function dvliveblog_frontend_putter() {
    global $wpdb;
    $user = wp_get_current_user();
    if(checkUserIsAble($user)) {
        $content = $_POST["content"];
        $postID = intval($_POST["postID"]);
        $wpdb->query(
            $wpdb->prepare(
                "
                INSERT INTO {$wpdb->prefix}dvliveblog
                ( post_id, type, content, author_id, pub_time )
                VALUES ( %d, %d, %s, %d, CURRENT_TIME() )
                ",
                $postID,
                0,
                $content,
                $user->ID
            )
        );

        return wp_send_json(["result" => true]);
    } else {
        return wp_send_json(["result" => false]);
    }
}

function dvliveblog_frontend_deleter() {
    $item = intval($_POST['itemID']);
    $post = intval($_POST["postID"]);
    return liveblog_delete($item, $post);
}

function liveblog_loadFrontEndScript() {
    if( !is_single() ) return;
    $title_nonce = wp_create_nonce( 'dvliveblog_enqueue_refresher' );
    wp_enqueue_script( 'liveblog-frontend-refresher',
        plugins_url( '/js/frontend.js', __FILE__ ),
        array( 'jquery' )
    );
    wp_localize_script( 'liveblog-frontend-refresher', 'dvliveblog_enqueue_refresher_obj', array(
       'ajax_url' => admin_url( 'admin-ajax.php' ),
       'nonce'    => $title_nonce,
    ) );
}

function dvliveblog_initTable() {
    global $wpdb;

    $tableName = $wpdb->prefix . 'dvliveblog';
    $charset = $wpdb->get_charset_collate();

$sql = "CREATE TABLE $tableName (
  id bigint(20) NOT NULL AUTO_INCREMENT,
  post_id bigint(20) NOT NULL,
  type int(11) NOT NULL,
  content longtext NOT NULL,
  author_id bigint(20) NOT NULL,
  pub_time datetime NOT NULL,
  PRIMARY KEY  (id)
) $charset;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
}

register_activation_hook( __FILE__, 'dvliveblog_initTable' );
add_shortcode( 'liveblog', 'liveblog_sc_handler' );
add_action( 'wp_ajax_dvliveblog_frontend_handler', 'dvliveblog_frontend_handler' );
add_action( 'wp_ajax_nopriv_dvliveblog_frontend_handler', 'dvliveblog_frontend_handler' );
add_action( 'wp_ajax_dvliveblog_frontend_putter', 'dvliveblog_frontend_putter' );
add_action( 'wp_ajax_nopriv_dvliveblog_frontend_putter', 'dvliveblog_frontend_putter' );
add_action( 'wp_ajax_dvliveblog_frontend_deleter', 'dvliveblog_frontend_deleter' );
add_action( 'wp_ajax_nopriv_dvliveblog_frontend_deleter', 'dvliveblog_frontend_deleter' );
add_action( 'wp_enqueue_scripts', 'liveblog_loadFrontEndScript' );
