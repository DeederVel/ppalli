<?php
/**
 * Plugin Name: Ppalli - Micro liveblogging plugin
 * Plugin URI: https://deedervel.com
 * Description: Micro liveblogging for Wordpress
 * Version:    2019.06.01
 * Author:     Mattia "DeederVel" Dui
 * Author URI:  https://deedervel.com
 * License:    CC BY-NC-SA 4.0
 * License URI: http://creativecommons.org/licenses/by-nc-sa/4.0/
 * Text Domain: dvliveblog
 * Domain Path: /lang
 */

require_once(dirname( __FILE__ ).'/Ppalli.php');
use DeederVel\Ppalli as Ppalli;

function liveblog_render($title, $refreshrate) {
    global $post;
    $refreshrate = ($refreshrate > 0) ? ($refreshrate * 1000) : 5000;
    $ppalli = new Ppalli($post->ID);
    return ($ppalli->getAllMoments($title, $refreshrate));
}

function liveblog_update($lastID, $postID) {
    $ppalli = new Ppalli($postID);
    return wp_send_json($ppalli->getNewMoments($lastID));
}

function liveblog_delete(int $itemID, int $postID) {
    $ppalli = new Ppalli($postID);
    return wp_send_json($ppalli->deleteMoment($itemID));
}

function dvliveblog_sc_handler($atts) {
    wp_enqueue_style(
        'dvliveblog',
        plugins_url( '/css/front.css', __FILE__ )
    );
    $lu = liveblog_render($atts['title'], intval($atts['secs']));
    return $lu["content"];
}

function dvliveblog_frontend_handler() {
    $lastID = intval($_POST["lastID"]);
    $postID = intval($_POST["postID"]);
    $ret = liveblog_update($lastID, $postID);
    return wp_send_json($ret);
}

function dvliveblog_frontend_putter() {
    $user = wp_get_current_user();
    $content = $_POST["content"];
    $postID = intval($_POST["postID"]);
    $type = intval($_POST["type"]);
    $ppalli = new Ppalli($postID);
    return wp_send_json($ppalli->newMoment($user, $content, $type));
}

function dvliveblog_frontend_deleter() {
    $item = intval($_POST['itemID']);
    $post = intval($_POST["postID"]);
    return liveblog_delete($item, $post);
}

function dvliveblog_loadFrontEndScript() {
    if( !is_single() ) return;
    $title_nonce = wp_create_nonce( 'dvliveblog_enqueue_refresher' );
    wp_enqueue_script( 'dvliveblog-frontend-editor',
        plugins_url( '/js/tinymce/tinymce.min.js', __FILE__ )
    );
    wp_enqueue_script( 'dvliveblog-frontend-refresher',
        plugins_url( '/js/frontend.js', __FILE__ ),
        array( 'jquery' )
    );
    wp_enqueue_script( 'dvliveblog-fontawesome', 'https://kit.fontawesome.com/74a8ff8d09.js');
    wp_localize_script( 'dvliveblog-frontend-refresher', 'dvliveblog_enqueue_refresher_obj', array(
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

function dvliveblog_load_textdomain() {
    load_plugin_textdomain( 'dvliveblog', FALSE, basename( dirname( __FILE__ ) ) . '/lang/' );
}

register_activation_hook( __FILE__, 'dvliveblog_initTable' );
add_shortcode( 'liveblog', 'dvliveblog_sc_handler' );
add_action( 'wp_ajax_dvliveblog_frontend_handler', 'dvliveblog_frontend_handler' );
add_action( 'wp_ajax_nopriv_dvliveblog_frontend_handler', 'dvliveblog_frontend_handler' );
add_action( 'wp_ajax_dvliveblog_frontend_putter', 'dvliveblog_frontend_putter' );
add_action( 'wp_ajax_nopriv_dvliveblog_frontend_putter', 'dvliveblog_frontend_putter' );
add_action( 'wp_ajax_dvliveblog_frontend_deleter', 'dvliveblog_frontend_deleter' );
add_action( 'wp_ajax_nopriv_dvliveblog_frontend_deleter', 'dvliveblog_frontend_deleter' );
add_action( 'wp_enqueue_scripts', 'dvliveblog_loadFrontEndScript' );
add_action( 'plugins_loaded', 'dvliveblog_load_textdomain' );
