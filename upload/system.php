<?php
define('MCR', '2.42b'); 
define('PROGNAME', 'webMCR ' . MCR);
define('FEEDBACK', '<a href="http://drop.catface.ru/index.php?nid=17">' . PROGNAME . '</a> &copy; 2013-2014 NC22');  

function execute() 
{  
    global $config, 
           $bd_names, 
           $bd_money, 
           $bd_users, 
           $site_ways,
           $user,
           $mcrLocale;
    
    error_reporting(E_ALL);
    
    define('MCR_ROOT', dirname(__FILE__) . '/');
    
    /**
     * Check install
     */
    
    if (file_exists(MCR_ROOT . 'data/system/config.php')) {
        require(MCR_ROOT . 'data/system/config.php'); 
    }
    
    if (empty($config) or isset($config['tmp_install'])) {
    
        $url = str_replace('\\', '/', $_SERVER['PHP_SELF']); 
        $url = explode("index.php", $url, -1);
        if (sizeof($url)) $url = $url[0];
        else $url = '/';
        
        header('Location: ' . $url . 'install/install.php');
        exit;   
    } 
    
    /**
     * Load base classes
     */

    loadTool('base.class.php');
    loadTool('auth.class.php', 'auth/');    

    define('MCR_LANG', 'ru');
    define('MCR_STYLE', getWay('style'));
    define('STYLE_URL', $site_ways['style']); // deprecated
    define('DEF_STYLE_URL', STYLE_URL . View::DEFAULT_THEME . '/');    
    define('BASE_URL', $config['s_root']);   
    
    date_default_timezone_set($config['timezone']);
    require(getWay('system') . 'locale/' . MCR_LANG.'.php');
    
    AuthCore::setDriver($config['p_logic'], $config['p_encode']);
}

/**
 * @deprecated since v2.35
 */
function BD($query)
{
    $resultStatement = getDB()->ask($query);
    return $resultStatement->getResult();
}

/**
 * @deprecated since v2.35
 */
function BDConnect($logScript = 'default')
{
    global $link;
    
    if (empty($link)) {
        DBinit($logScript);
    }
}

 /**
  * Load DB API classes and connect to MySQL database with check ban by IP
  */ 
 
function DBinit($logScript = 'default', $accessCheck = true, $exitOnFail = true)
{
    global $link, $config;

    if (!empty($link)) return;    

    if (!isset($config['db_driver'])) {
        $config['db_driver'] = 'mysql';
    }
    
    loadTool('databaseInterface.class.php', 'database/');
    loadTool('statementInterface.class.php', 'database/');
    
    if ( $config['db_driver'] != 'pdo') {
        loadTool('mysqlDriverBase.class.php', 'database/' );
        loadTool('mysqlDriverStm.class.php', 'database/' ); 
    }
    
    loadTool('module.class.php', 'database/' . $config['db_driver'] . '/');
    loadTool('statement.class.php', 'database/' . $config['db_driver'] . '/' );
    
    $class = $config['db_driver'] . 'Driver';
    $link = new $class();

    try { 
        $link->connect(array(
            'host' => $config['db_host'], 
            'port' => $config['db_port'], 
            'login' => $config['db_login'], 
            'password' => $config['db_passw'], 
            'db' => $config['db_name']
        ));         
    } catch (Exception $e) {
        if ($exitOnFail) exit($e->getMessage());
        else return $e->getMessage();
    }

    if ($logScript and $config['action_log'])
        ActionLog($logScript);
        
    if ($accessCheck) CanAccess(2);
    return true;    
}

/**
 * 
 * @global DataBaseInterface $link
 * @return DataBaseInterface
 */
function getDB()
{
    global $link;
    
    if (empty($link)) {
        DBinit();
    }

    return $link;
}

/* Системные функции */

function loadTool($name, $subDir = '')
{
    global $mcrTools;
    
    if (!isset($mcrTools)) $mcrTools = array();
    
    if (in_array($name, $mcrTools))
        return;

    $mcrTools[] = $name;

    require( MCR_ROOT . 'instruments/' . $subDir . $name);
}

function getWay($id) 
{
    global $site_ways;
    
    return isset($site_ways[$id]) ? MCR_ROOT . $site_ways[$id] : false;
}

function lng($key, $lang = false)
{
    global $mcrLocale;
    
    return isset($mcrLocale[$key]) ? $mcrLocale[$key] : $key;
}

function POSTGood($post_name, $format = array('png'))
{
    if (empty($_FILES[$post_name]['tmp_name']) or
            $_FILES[$post_name]['error'] != UPLOAD_ERR_OK or
            !is_uploaded_file($_FILES[$post_name]['tmp_name']))
        return false;

    $extension = strtolower(substr($_FILES[$post_name]['name'], 1 + strrpos($_FILES[$post_name]['name'], ".")));

    if (is_array($format) and !in_array($extension, $format))
        return false;

    return true;
}

function POSTSafeMove($post_name, $tmp_dir = false)
{
    if (!POSTGood($post_name, false))
        return false;

    if (!$tmp_dir)
        $tmp_dir = getWay('tmp');

    if (!is_dir($tmp_dir))
        mkdir($tmp_dir, 0777);

    $tmp_file = tempnam($tmp_dir, 'tmp');
    
    if ($tmp_file === false or 
        !move_uploaded_file($_FILES[$post_name]['tmp_name'], $tmp_file)) {
        
        if ($tmp_file) unlink($tmp_file);
        vtxtlog('[POSTSafeMove] --> "' . $tmp_file . '" <-- ' . lng('WRITE_FAIL'));
        return false;
    }

    return array(
        'tmp_name' => basename($tmp_file), 
        'tmp_way' => $tmp_file,
        'name' => $_FILES[$post_name]['name'], 
        'size_mb' => round($_FILES[$post_name]['size'] / 1024 / 1024, 2)
    );
}

function randString($pass_len = 50)
{
    $allchars = "abcdefghijklmnopqrstuvwxyz0123456789";
    $string = "";

    mt_srand((double) microtime() * 1000000);

    for ($i = 0; $i < $pass_len; $i++)
        $string .= $allchars{ mt_rand(0, strlen($allchars) - 1) };

    return $string;
}

function sqlConfigGet($type)
{
    global $bd_names;

    if (!in_array($type, ItemType::$SQLConfigVar))
        return false;

    $line = getDB()->fetchRow("SELECT `value` FROM `{$bd_names['data']}` "
            . "WHERE `property`=:type", array('type' => $type), 'num');

    return ($line) ? $line[0] : false;
}

function sqlConfigSet($type, $value)
{
    global $bd_names;

    if (!in_array($type, ItemType::$SQLConfigVar))
        return false;

    $result = getDB()->ask("INSERT INTO `{$bd_names['data']}` (value,property) "
            . "VALUES (:value, :type) "
            . "ON DUPLICATE KEY UPDATE `value`=:value2", array(
        'value' => $value,
        'type' => $type,
        'value2' => $value
    ));

    return true;
}

function InputGet($key, $method = 'post', $type = 'string') {
    return Filter::input($key, $method, $type);
}

function GetRealIp()
{

    if (!empty($_SERVER['HTTP_CLIENT_IP']))
        $ip = $_SERVER['HTTP_CLIENT_IP'];

    elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    else
        $ip = $_SERVER['REMOTE_ADDR'];

    return substr($ip, 0, 16);
}

function RefreshBans()
{
    global $bd_names;

    /* Default ban until time */
    getDB()->ask("DELETE FROM {$bd_names['ip_banning']} "
            . "WHERE (ban_until='0000-00-00 00:00:00') "
            . "AND (time_start<NOW()-INTERVAL " . ((int) sqlConfigGet('next-reg-time')) . " HOUR)");

    getDB()->ask("DELETE FROM {$bd_names['ip_banning']} "
            . "WHERE (ban_until<>'0000-00-00 00:00:00') "
            . "AND (ban_until<NOW())");
    
    getDB()->ask("DELETE FROM {$bd_names['user_banning']} "
        . "WHERE (ban_until<>'0000-00-00 00:00:00') "
        . "AND (ban_until<NOW())");
}

function vtxtlog($string)
{
    global $config;

    if (!$config['log'])
        return;

    $log_file = getWay('system') . 'log.txt';

    if (file_exists($log_file) and round(filesize($log_file) / 1048576) >= 50)
        unlink($log_file);

    if (!$fp = fopen($log_file, 'a'))
        exit('[vtxtlog]  --> ' . $log_file . ' <-- ' . lng('WRITE_FAIL'));

    fwrite($fp, date("H:i:s d-m-Y") . ' < ' . $string . PHP_EOL);
    fclose($fp);
}

function tokenTool($mode = 'set', $exit = true)
{
    global $content_js,
           $mcrToken;

    if (!isset($_SESSION)) {
        session_start();
    }

    if ($mode == 'check') {
        
        $token = Filter::input('token_data');
        if (!$token) $token = Filter::input('token_data', 'get');

        if (empty($_SESSION['token_data']) or
            $_SESSION['token_data'] !== $token) {
            
            if ($exit) exit(lng('TOKEN_FAIL'));
            return false;
        }
        
        return true;
    } 
    
    if (!isset($mcrToken)) { 
        $_SESSION['token_data'] = randString(32); 
        $mcrToken = $_SESSION['token_data'];
    }
    
    if ($mode == 'set') {
        $content_js .= '<script type="text/javascript">var token_data = "' . $mcrToken . '";</script>';
        return true;
    } elseif ($mode == 'setinput') {
        return '<input type="hidden" name="token_data" id="token_data" value="' . $mcrToken . '" />';
    } 
    
    return $_SESSION['token_data'];
}

function ActionLog($last_info = 'default_action')
{
    global $config, $bd_names;

    $ip = GetRealIp();
    getDB()->ask("DELETE FROM `{$bd_names['action_log']}` "
            . "WHERE `first_time` < NOW() - INTERVAL {$config['action_time']} SECOND");

    $sql = "INSERT INTO `{$bd_names['action_log']}` (IP, first_time, last_time, query_count, info) "
            . "VALUES (:ip, NOW(), NOW(), 1, :info) "
            . "ON DUPLICATE KEY UPDATE "
            . "`last_time` = NOW(), "
            . "`query_count` = `query_count` + 1, "
            . "`info` = :info2";

    getDB()->ask($sql, array('info' => $last_info, 'ip' => $ip, 'info2' => $last_info));

    $line = getDB()->fetchRow("SELECT `query_count` FROM `{$bd_names['action_log']}` "
            . "WHERE `IP`=:ip", array('ip' => $ip), 'num');

    $query_count = (int) $line[0];
    if ($query_count > $config['action_max']) {

        getDB()->ask("DELETE FROM `{$bd_names['action_log']}` WHERE `IP`=:ip", array('ip' => $ip));

        RefreshBans();

        $sql = "INSERT INTO {$bd_names['ip_banning']} (IP, time_start, ban_until, ban_type, reason) "
                . "VALUES (:ip, NOW(), NOW()+INTERVAL {$config['action_ban']} SECOND, '2', 'Many BD connections (" . $query_count . ") per time') "
                . "ON DUPLICATE KEY UPDATE `ban_type` = '2', `reason` = 'Many BD connections (" . $query_count . ") per time' ";

        getDB()->ask($sql, array('ip' => $ip));
    }

    return $query_count;
}

function CanAccess($ban_type = 1)
{
    global $bd_names;

    $ip = GetRealIp();
    $ban_type = (int) $ban_type;

    $line = getDB()->fetchRow("SELECT COUNT(*) FROM `{$bd_names['ip_banning']}` "
            . "WHERE `IP`=:ip AND `ban_type`='" . $ban_type . "' "
            . "AND `ban_until` <> '0000-00-00 00:00:00' AND `ban_until` > NOW()", array('ip' => $ip), 'num');

    $num = (int) $line[0];

    if ($num) {

        getDB()->close();

        if ($ban_type == 2)
            exit('(-_-)zzZ <br>' . lng('IP_BANNED'));
        return false;
    }
    return true;
}
