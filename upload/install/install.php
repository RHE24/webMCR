<?php
header('Content-Type: text/html; charset=UTF-8');

error_reporting(E_ALL); 

include '../system.php';

/* 
 * DB_PREFIX will be used for usual table names 
 * if install in compatibility mode
 */

define('DB_PREFIX', 'mcr_');
define('MCR_ROOT', dirname(dirname(__FILE__)).'/');
define('BASE_URL', getRootUrl());
define('DEBUG', true);

loadTool('base.class.php');
loadTool('alist.class.php');

include './pack/encode/encoderPreset.class.php';

$viewer = new View();
$viewer->setViewBaseDir(MCR_ROOT . 'install/style/');

$mode = Filter::input('mode'); // cms post key
if (!$mode) $mode = Filter::input('mode', 'get');
if (!$mode) $mode = 'usual';
            
$step = Filter::input('step', 'post', 'int', true);

 /* Инициализация начальных параметров каждого режима установки */

$main_cms = false;

switch ($mode) {
    case 'xenforo': $main_cms = 'xenForo';  break;
    case 'ipb': $main_cms = 'Invision Power Board'; break; 
    case 'dle': $main_cms = 'DataLife Engine';  break; 
    case 'wp': $main_cms = 'WordPress';    break;
    case 'joomla': $main_cms = 'Joomla!';  break; 
    case 'xauth': $main_cms = 'xAuth'; break; 
    case 'authme': $main_cms = 'AuthMe'; break; 
    default : $mode = 'usual'; break;
}

configInit($mode);

define('MCR_STYLE', getWay('style'));
define('STYLE_URL', $site_ways['style']);
define('DEF_STYLE_URL', STYLE_URL . View::DEFAULT_THEME . '/');
define('CUR_STYLE_URL', DEF_STYLE_URL);

$page = 'Настройка ' . PROGNAME;

$content_advice = '';
$content_servers = ''; 
$content_js = '<script src="'. $site_ways['system'] . 'js/tools.js"></script>';
$content_side = $viewer->showPage('install_side.html');
$addition_events = '';

$info = '';  
$cErr = '';
$info_color = 'alert-error'; //alert-success

$menu = new Menu('', false);
$menu->AddItem($page, BASE_URL . 'install/install.php', true); 

$content_menu = $menu->Show();

loadTool('timezonePicker.class.php');

function configSave() 
{
    global $configWay;
    
    if (MainConfig::SaveOptions()) return;
    
    exit ('Ошибка создания \ перезаписи файла '. $configWay . '. Настройки не были сохранены. '
        . 'Папка защищена от записи или файл не доступен для записи. ');          
}

function configInit($mode) 
{    
    global $config, $bd_names, $bd_money, $bd_users, $site_ways, $configWay, $mode;
     
    $configWay = MCR_ROOT . 'data/system/config.php';
    $generate = true;
    
     if (file_exists($configWay)) {
        
        include $configWay;
        
        if (empty($config['tmp_install'])) {
            header('Location: ' . BASE_URL);
            exit;
        }
        
        $generate = false;        
        
        /* Установка была не завершена, файл существует и
         * режим установки не совпадает с выбранным - удаляем */
        
        if ($config['p_logic'] != $mode) {
            $generate = true;
            if (!unlink($configWay)) exit ('Ошибка удаления ' . $configWay );
        }
    } 
    
    if ($generate) {
        include './pack/config/config_usual.php';

        if ($mode != 'usual') {

            foreach ($bd_names as $key => $value)
                if ($value)
                    $bd_names[$key] = DB_PREFIX . $value;

            include './pack/config/config_' . $mode . '.php';
        }
        
        $config['p_logic'] = $mode;      
        $config['tmp_install'] = true;
        configSave();
    }
}

function checkBaseRequire()
{
    global $info, $site_ways, $create_ways;

    $p = '<p>'; $pe = '</p>';

    if (!extension_loaded('gd'))
        $info .= $p . 'Модуль GD не подключен (отображение изображений)' . $pe;

    if (ini_get('register_globals')) {
        exit ('Критическое нарушение безопасности, требуется задать <b>register_globals = Off</b> (php.ini или раскомментировать в .htaccess php_flag)');
    }

    if (!function_exists('fsockopen'))
        $info .= $p . 'Функция fsockopen недоступна (проверка состояния сервера)' . $pe;

    if (!function_exists('json_encode'))
        $info .= $p . 'Функция json_encode недоступна (авторизация на сайте)' . $pe;
    
    createWays();
    checkRWOut(MCR_ROOT . 'install/');
    checkRWOut(getWay('system') . 'menuItems.php');
    checkRWOut(getWay('tmp') . 'skinBuffer/default/');
    checkRWOut(getWay('tmp') . 'defaultSkins/');
    checkRWOut(getWay('upload'));
    checkRWOut(getWay('skins'));
    checkRWOut(getWay('cloaks'));
    checkRWOut(getWay('distrib'));
}

function checkRWOut($fname, $create = false)
{
    global $info;
    $is_dir = substr_count($fname, '.');

    if (!checkRW($fname, $create))
        $info .= '<p>' . ($is_dir ? 'Файл' : 'Папка') . ' ' . $fname . ' . Нет доступа Read \ Write</p>';
}

function checkRW($filename, $create = false)
{
    if (!substr_count($filename, '.')) // is dir
        if (@file_exists($filename))
            return true;
        else
            return false;

    if ($create) {

        $file = fopen($filename, 'w');

        if ($file)
            fclose($filename);
        else
            return false;

        if (is_readable($filename))
            return true;
    }

    if (is_readable($filename) and is_writable($filename))
        return true;

    return false;
}

function createWays()
{
    global $site_ways, $create_ways;

    foreach ($site_ways as $key => $value)
        if (!is_dir(getWay($key)))
            mkdir(getWay($key), 0777, true);
}

function findCMS($way)
{
    global $main_cms, $mode, $info;

    if (!TextBase::StringLen($way)) {
        $info .= 'Укажите путь до папки ' . $main_cms . '.';
        return false;
    }

    switch ($mode) {
        case 'xenforo': $file = 'library/XenForo/Autoloader.php';
            break;
        case 'ipb': $file = 'admin/sources/base/ipsController.php';
            break;
        default: return false;
            break;
    }

    if (!file_exists($way . $file))
        $info .= 'Путь до папки ' . $main_cms . ' указан неверно. Файл не доступен ' . $way . $file;
    else
        return true;

    return false;
}

function getRootUrl()
{
    $root_url = str_replace('\\', '/', $_SERVER['PHP_SELF']); 
    $root_url = explode("install/install.php", $root_url, -1);
    if (sizeof($root_url)) return $root_url[0];
    else return '/';
}

function isModeRewriteEnabled()
{	
    if (function_exists('apache_get_modules')) {

      $modules = apache_get_modules();
      return in_array('mod_rewrite', $modules);

    } else return getenv('HTTP_MOD_REWRITE')=='On' ? true : false ;	
    return false;
}

function CreateAdmin($site_user)
{
    global $config, $bd_names, $bd_users, $info, $site_ways;

    $password = Filter::input('site_password');
    $repassword = Filter::input('site_repassword');
    $result = false;

    if (!TextBase::StringLen($password))
        $info .= 'Введите пароль.';
    elseif (!TextBase::StringLen($password))
        $info .= 'Введите повтор пароля.';
    elseif (strcmp($password, $repassword))
        $info .= 'Пароли не совпадают.';
    else {
        
        loadTool('Auth.class.php', 'auth/');        
        AuthCore::setDriver($config['p_logic'], $config['p_encode']);

        getDB()->ask("INSERT INTO `{$bd_names['users']}` ("
                . "`{$bd_users['login']}`,"
                . "`{$bd_users['ip']}`,"
                . "`{$bd_users['group']}`) "
                . "VALUES('$site_user', '127.0.0.1', 3) "
                . "ON DUPLICATE KEY "
                . "UPDATE `{$bd_users['group']}`='3'");
        
        loadTool('user.class.php');
        $user = new User($site_user, $bd_users['login']);
        $user->setDefaultSkin();        
        $user->changePassword($password); 
        
        $result = true;
    }

    return $result;
}

if ($step !== false)

switch ($step) {
    case 0:    
        checkBaseRequire();  
        $step = 1;
        break;       
    case 1:     
        $mysql_port = Filter::input('mysql_port', 'post', 'int');
        $mysql_adress = Filter::input('mysql_adress');
        $mysql_bd = Filter::input('mysql_bd');
        $mysql_user = Filter::input('mysql_user');
        $mysql_password = Filter::input('mysql_password');
        $mysql_driver = Filter::input('mysql_driver');
        $mysql_rewrite = (empty($_POST['mysql_rewrite'])) ? false : true;

        if ($mysql_driver !== 'pdolite') {
            $mysql_file = null;
        } else
            $mysql_driver = 'pdo';

        if (!$mysql_port)
            $info .= 'Укажите порт для подключения к БД.';
        elseif (!TextBase::StringLen($mysql_adress))
            $info .= 'Укажите адресс сервера MySQL.';
        elseif (!TextBase::StringLen($mysql_user))
            $info .= 'Укажите пользователя для подключения к MySQL серверу.';
        else {

            $config['db_host'] = $mysql_adress;
            $config['db_port'] = $mysql_port;
            $config['db_name'] = $mysql_bd;
            $config['db_login'] = $mysql_user;
            $config['db_passw'] = $mysql_password;
            $config['db_driver'] = $mysql_driver;

            $connect_result = DBinit(false, false);

            if ($connect_result !== true) {
                $info .= 'Ошибка подключения к базе данных: ' . $connect_result;
                break;
            }
            
            $config['rewrite'] = isModeRewriteEnabled();
            $config['s_root'] = BASE_URL;

            EncoderPreset::init($mode, Filter::input('encode', 'post', 'string', true));
            
            include './pack/sql/sql_common.php';
            if (!$main_cms)
                include './pack/sql/sql_usual.php';
                       
            $passColumn = getDB()->getColumnType($bd_names['users'], $bd_users['password']);
            $encode = EncoderPreset::getOptions();

            if (EncoderPreset::getColName() and $passColumn != EncoderPreset::getColName()){ 
                $info .= 'Несовместимый тип поля '. $passColumn .' ( требуется '.  $encode['column'] .') '
                      . 'для режима шифрования. (' . $encode['name'] . ')';
                break;
            }
            
            $config['p_encode'] = EncoderPreset::getEncoder();
            
            configSave();    
            $step = 2; 
        }
        break;
    case 2:
        $site_user = Filter::input('site_user');
        $mysql_rewrite = (empty($_POST['mysql_rewrite'])) ? false : true;

        if (!TextBase::StringLen($site_user)) {

            $info .= 'Укажите имя пользователя.';
            break;
        }
       
        if (DBinit(false, false) !== true) {
            $info .= 'Ошибка настройки соединения с БД.';
            break;
        }
        
        if ($main_cms) {

            $bd_names['users'] = Filter::input('bd_accounts_mcms');
            define('DB_TOUSERS', "ALTER TABLE `{$bd_names['users']}` "); 

            include './pack/sql/sql_' . $mode . '.php';

            if (!TextBase::StringLen($bd_names['users'])) {
                $info .= 'Введите название таблицы пользователей.';
                break;
            }

            $config['p_sync'] = (empty($_POST['session_sync'])) ? false : true;

            $userId = getDB()->fetchRow("SELECT `{$bd_users['id']}` FROM `{$bd_names['users']}` WHERE `{$bd_users['login']}`='$site_user'", false, 'num');

            if ($userId === false) {
                $info .= 'Название таблицы пользователей указано неверно.';
                break;
            } elseif (!$userId) {
                $info .= 'Пользователь с таким именем не найден.';
                break;
            }

            if ($mode == 'xenforo') {

                $cms_way = Filter::input('main_cms', 'post');
                if (!findCMS($cms_way))
                    break;

                $site_ways['main_cms'] = $cms_way;
                $bd_names['user_auth'] = Filter::input('bd_auth_xenforo');

                $result = getDB()->ask("SELECT `{$bd_users['id']}` FROM `{$bd_names['users']}` WHERE `{$bd_users['id']}`='" . $userId[0] . "'");
                if ($result === false) {
                    $info .= 'Название таблицы c дополнительными данными указано неверно.';
                    break;
                }
            }

            if ($mode == 'xauth' and !CreateAdmin($site_user))
                break;

            if ($mode != 'xauth')
                getDB()->ask("UPDATE `{$bd_names['users']}` SET `{$bd_users['group']}`='3' WHERE `{$bd_users['login']}`='$site_user'");
            $step = 3;
            configSave();
        } else if (CreateAdmin($site_user))
            $step = 3;
        break;
    case 3:
        $site_name = Filter::input('site_name');
        $site_about = Filter::input('site_about');
        $keywords = Filter::input('site_keyword');
        if (TimezonePicker::isExist(Filter::input('site_timezone')))
            $timezone = Filter::input('site_timezone');
        else $timezone = false;

        $sbuffer = (!empty($_POST['sbuffer'])) ? true : false;
        $default_skin = (!empty($_POST['default_skin'])) ? true : false;
        
        if (TextBase::StringLen($keywords) > 200)
            $info .= 'Ключевые слова занимают больше 200 символов (' . TextBase::StringLen($keywords) . ').';
        elseif (!$timezone)
            $info .= 'Выберите часовой пояс.';
        else {

            $config['s_name'] = $site_name;
            $config['s_about'] = $site_about;
            $config['s_keywords'] = $keywords;
            $config['sbuffer'] = $sbuffer;
            $config['timezone'] = $timezone;
            $config['default_skin'] = $default_skin;
            
            unset($config['tmp_install']);              
            configSave();     
            $step = 4;
        }
        break;
}

ob_start(); 

switch ($step) 
{
    case 0: 
    include $viewer->getView('install_method.html'); 	
    break;
    case 1: 
    EncoderPreset::init($mode, Filter::input('encode', 'post', 'string', true));
    include $viewer->getView('install.html'); 	
    break;
    case 2: 
    switch ($mode) {
        case 'usual': include $viewer->getView('install_user.html'); break;
        case 'xenforo': 
        case 'xauth': include $viewer->getView('install_'.$mode.'.html'); break;
        case 'authme': include $viewer->getView('install_xauth.html'); break;
        case 'ipb': 
        case 'joomla':		
        case 'dle':
        case 'wp': include $viewer->getView('install_mcms.html'); break;
    } 	
    break;
    case 3: include $viewer->getView('install_constants.html'); break;
    default: include $viewer->getView('other.html'); break;
}

$install = ob_get_clean();

ob_start(); 
include $viewer->getView('install_container.html');
if ($info) include $viewer->getView('info.html'); 
$content_main = ob_get_clean();

if ($step == 4 and !DEBUG) ThemeManager::deleteDir(MCR_ROOT . 'install/');

include View::get('index.html');
