<?php

global $wiseNotifications_logs;
$wiseNotifications_logs = [];

wiseNotifications_enableLogging();

function wiseNotifications_log($message, $obj = null, $severity = 'log')
{
    global $wiseNotifications_logs;

    if (WP_DEBUG || WP_DEBUG_LOG) {
        $logMsg = date('c') . ' - ' . $severity . ' - ' . $message . ' details: ' . json_encode($obj);
        $wiseNotifications_logs[] = $logMsg;
    }
}

function wiseNotifications_enableLogging()
{
    add_action('shutdown', 'wiseNotifications_saveLogs');
}


function wiseNotifications_saveLogs()
{
    global $wiseNotifications_logs;

    if ((WP_DEBUG || WP_DEBUG_LOG) && !empty($wiseNotifications_logs) && count($wiseNotifications_logs)) {
        try {
            foreach ($wiseNotifications_logs as $log) {
                error_log('WiseNotifications: ' . $log);
            }
        } catch (\Thrrowable $e) {
        }
    }
}
