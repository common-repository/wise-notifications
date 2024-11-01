<?php
/**
 * @package Wise Notifications - web push notifications for iOS
 * @version 1.1.1
 */
/*
Plugin Name: Now Push Notify - web push notifications for iOS
Description: Send push notifications to your iOS website visitors.
Author: Now Push
Version: 1.1.1
Author URI: https://notify.nowpush.app
*/

$wiseNotifications_pluginPath = plugin_dir_path(__FILE__);

include $wiseNotifications_pluginPath . 'embedjs.php';
include $wiseNotifications_pluginPath . 'logging.php';


add_action('wp_footer', 'wiseNotifications_addWidget');
function wiseNotifications_addWidget()
{
    $uagent  = strtolower($_SERVER['HTTP_USER_AGENT']);
    $iPod    = stripos($uagent, "iPod") !== false;
    $iPhone  = stripos($uagent, "iPhone") !== false;
    $iPad    = stripos($uagent, "iPad") !== false;

    // show the widget only on iOS device
    if (!$iPad && !$iPod && !$iPhone) {
        return;
    }

    $pluginSettings = get_option('wise_notifications_name');

    if (empty($pluginSettings['topicId']) || empty($pluginSettings['apiKey']) || empty($pluginSettings['subscribeLink'])) {
        return;
    }

    $url = $pluginSettings['subscribeLink'];
    wiseNotifications_scriptWidget($url);
}

if (is_admin()) {
    include $wiseNotifications_pluginPath . 'settingsPage.php';
    include $wiseNotifications_pluginPath . 'metaBox.php';
    wiseNotifications_log('check OneSignal exists', class_exists('OneSignal_Admin'));

    // hooks
    if (class_exists('OneSignal_Admin')) {
        // send when OneSignal sends
        add_filter('onesignal_send_notification', function ($fields, $new_status, $old_status, $post) {
            wiseNotifications_log('sending notification for post', $post);
            try {
                wiseNotifications_sendNotificationForPost($post);
            } catch (\Exception $e) {
                wiseNotifications_log('could not send notitication', ['err' => $e, 'post' => $post], 'error');
            }

            return $fields;
        }, 10, 4);
    } else {
        wiseNotifications_log('add filter transition_post_status');
        add_action('transition_post_status', 'wiseNotifications_postPublishedNotification', 10, 3);
    }

    function wiseNotifications_postPublishedNotification($new_status, $old_status, $post)
    {
        wiseNotifications_log('wiseNotifications_postPublishedNotification started', ['new_status' => $new_status, 'old_status' => $old_status ]);
        // Check if someone published a post for the first time.
        if ($new_status == 'publish') {
            // TODO make sure the post is new and not updated (maybe check revisions?)
            try {
                wiseNotifications_sendNotificationForPost($post);
            } catch (\Exception $e) {
                wiseNotifications_log('could not send notitication', ['err' => $e, 'post' => $post], 'error');
            }
        }
    }

    function wiseNotifications_sendNotificationForPost($post)
    {

        // do not resend of already sent
        $wasNotificationSentAlready = get_post_meta ( $post->ID, 'wisenotifications_notification_sent', true );

        if ($wasNotificationSentAlready) {
            wiseNotifications_log('notification was already sent', ['post' => $post]);
            return;
        }

        $title = $post->post_title;
        $permalink = get_permalink($post->ID);

        $pluginSettings = get_option('wise_notifications_name');

        if (empty($pluginSettings['topicId']) || empty($pluginSettings['apiKey'])) {
            wiseNotifications_log('config data is missing', $pluginSettings, 'error');
            // TODO https://premium.wpmudev.org/blog/adding-admin-notices/
            return;
        }

        // check if the post type has enabled notifications
        if (empty($pluginSettings['postTypes']) || !in_array($post->post_type, $pluginSettings['postTypes'])) {
        	wiseNotifications_log('post type not included for notifications', $pluginSettings, 'error');
            return;
        }

        // check if the checkbox was checked for this post to send notification
        if (!isset($_POST['wiseNotifications-checkbox']) || (isset($_POST['wiseNotifications-checkbox']) && $_POST['wiseNotifications-checkbox'] != 1)) {
            wiseNotifications_log('post should not have notifications sent from checkbox', $pluginSettings, 'error');
            return;
        }

        $data = [
            'title' => get_bloginfo('name'),
            'body' => $title,
            'topicId' => $pluginSettings['topicId'],
            'destinationUrl' => $permalink
        ];

        if (has_post_thumbnail($post->ID)) {
            $thumbnailId = get_post_thumbnail_id($post->ID);
            // requires WP 4.4.0
            $imageUrl = wp_get_attachment_image_url($thumbnailId, [192, 192], true);
            if ($imageUrl) {
                $data['imageUrl'] = $imageUrl;
            }
        }

        wiseNotifications_log('sending notifiations with data', $data);

        // $url = 'https://skdlxfssth.execute-api.eu-central-1.amazonaws.com/dev/';
        $url = 'https://api.wisenotifications.com/v1/';
        $isNotificationSent = wiseNotifications_postData($url, $data, $pluginSettings['apiKey']);

        if ($isNotificationSent) {
            update_post_meta( $post->ID, 'wisenotifications_notification_sent', true );
        }
    }

    // https://stackoverflow.com/questions/11319520/php-posting-json-via-file-get-contents
    function wiseNotifications_postData($url, $data, $apiKey)
    {
        $postdata = json_encode($data);
        $args = [
                'blocking' => true,
                'headers' => [
                    'Content-type' => 'application/json',
                    'X-Api-Key' => $apiKey,
                ],
                'body' => $postdata
        ];


        $result = wp_remote_post($url, $args);

        wiseNotifications_log('notification sending args', $args);
        wiseNotifications_log('notification sending received headers', $result);

        return 1|| $result['http_response']->get_response_object()->success;
    }
}
