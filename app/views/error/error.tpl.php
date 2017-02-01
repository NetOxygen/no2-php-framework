<?php
/*
 * some expected error status code have a more userfriendly message than the raw HTTP reason
 * phrase.
 */
$status = $this->status();
switch ($status) {
    case No2_HTTP::NOT_FOUND:
        $message = ht('app.errors.not_found');
        break;
    case No2_HTTP::UNAUTHORIZED:
        $message = ht('app.errors.unauthorized');
        break;
    default:
        $http_status_string = No2_HTTP::header_status_string($status);
        if (!is_null($http_status_string)) {
            $message = preg_replace('/HTTP\/\d+\.\d+\s*/', '', $http_status_string);
        } else {
            $message = ht('app.errors.default');
        }
}
?>

<h1><?php print ht('app.errors.title'); ?></h1>
<p><?php print h($message); ?></p>
