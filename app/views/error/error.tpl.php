<?php
/*
 * some expected error status code have a more userfriendly message than the raw HTTP reason
 * phrase.
 */
$status = $this->status();
switch ($status) {
case No2_HTTP::NOT_FOUND:
    $message = t('404 Page not found.');
    break;
case No2_HTTP::UNAUTHORIZED:
    $message = t('You are not authorized to access this page.');
    break;
default:
    $http_status_string = No2_HTTP::header_status_string($status);
    if (!is_null($http_status_string)) {
        $message = preg_replace('/HTTP\/\d+\.\d+\s*/', '', $http_status_string);
    } else {
        $message = "An error occured.";
    }
}
?>

<h1><?php print t('Oups!'); ?></h1>
<p><?php print h($message); ?></p>
