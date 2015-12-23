#!/usr/bin/env php
<?php
/**
 * @file add_admin.php
 *
 * small script to add a new admin user to the system.
 */
require_once(dirname(__FILE__) . '/../../bootstrap.inc.php');

/* stolen from http://www.dasprids.de/blog/2008/08/22/getting-a-password-hidden-from-stdin-with-php-cli */
function getPassword($stars = false)
{
    // Get current style
    $oldStyle = shell_exec('stty -g');

    if ($stars === false) {
        shell_exec('stty -echo');
        $password = rtrim(fgets(STDIN), "\n");
    } else {
        shell_exec('stty -icanon -echo min 1 time 0');

        $password = '';
        while (true) {
            $char = fgetc(STDIN);

            if ($char === "\n") {
                break;
            } else if (ord($char) === 127) {
                if (strlen($password) > 0) {
                    fwrite(STDOUT, "\x08 \x08");
                    $password = substr($password, 0, -1);
                }
            } else {
                fwrite(STDOUT, "*");
                $password .= $char;
            }
        }
    }

    // Reset old style
    shell_exec('stty ' . $oldStyle);

    // Return the password
    return $password;
}

$root = new User(['email' => User::ROOT_EMAIL, 'role' => 'admin']);
print "email: {$root->email}\n";

print "fullname: ";
$root->fullname = rtrim(fgets(STDIN));

print "password: ";
$password  = getPassword(); print "\n";
print "password (confirmation): ";
$password_ = getPassword(); print "\n";
if (strcmp($password, $password_) !== 0) {
    fwrite(STDERR, "ERROR: password confirmation missmatch.\n");
    exit(1);
}
$root->update_password($password);

if ($root->save()) {
    print "\ndone.\n";
} else {
    fwrite(STDERR, "ERROR: something bad happened: " . print_r($root, true));
    exit(1);
}
