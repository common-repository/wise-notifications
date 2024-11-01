<?php
function wiseNotifications_scriptWidget($url)
{
	// requires $url
    if (empty($url)) {
        trigger_error("Wise Notifications - error - missing subscribe URL");
        return;
    }
	wp_enqueue_script("wisenotifications-widget-lib", "https://notify.nowpush.app/wisenotifications-widget.js", null, false, true);

	$embedJs = '
	wisenotifications && wisenotifications.init({
		subscribeUrl: "' . $url . '"
	});';

	wp_add_inline_script("wisenotifications-widget-lib", $embedJs);
}