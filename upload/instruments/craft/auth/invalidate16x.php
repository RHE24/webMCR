<?php
require('../../../system.php');
execute();

function generateSessionId() {
    srand(time());
    $randNum = rand(1000000000, 2147483647) . rand(1000000000, 2147483647) . rand(0, 9);
    return $randNum;
}

function logExit($text, $output = "Bad login") {
    vtxtlog($text);
    exit($output);
}

if (($_SERVER['REQUEST_METHOD'] == 'POST' ) && (stripos($_SERVER["CONTENT_TYPE"], "application/json") === 0)) {
    $json = json_decode($HTTP_RAW_POST_DATA);
} else {
    logExit("Bad request method. POST/json required", "Bad request method. POST/json required");
}

if (empty($json->accessToken) or empty($json->clientToken))
    logExit("[invalidate16x.php] invalidate process [Empty input] [ " . ((empty($json->accessToken)) ? 'Session ' : '') . ((empty($json->clientToken)) ? 'clientToken ' : '') . "]");

loadTool('user.class.php');
DBinit('auth');

$sessionid = $json->accessToken;
$clientToken = $json->clientToken;

if (!preg_match("/^[a-f0-9-]+$/", $sessionid) or
        !preg_match("/^[a-f0-9-]+$/", $clientToken))
    logExit("[invalidate16x.php] login process [Bad symbols] Session [$sessionid] clientToken [$clientToken]");

$sql    = "SELECT `{$bd_names['email']}` FROM `{$bd_names['users']}` "
        . "WHERE `{$bd_users['session']}`=:sessionid AND `{$bd_users['clientToken']}`=:token";

$result = getDB()->fetchRow($sql, array('sessionid' => $sessionid, 'token' => $clientToken), 'num');

if (!$result)
    logExit("[invalidate16x.php] invalidate process, wrong accessToken/clientToken pair");

$login = $result[0];

$auth_user = new User($login, $bd_users['email']);

$sql = "UPDATE `{$bd_names['users']}` SET `{$bd_users['session']}`='' "
     . "WHERE `{$bd_users['email']}`=:email";

getDB()->ask($sql, array('email' => $login));

vtxtlog("[invalidate16x.php] refresh process [Success] User [$login] Invalidate Session [$sessionid] clientToken[$clientToken]");

exit();
