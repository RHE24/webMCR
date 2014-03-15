<?php

/* WEB-APP : WebMCR (С) 2013-2014 NC22 | License : GPLv3 */

header('Content-Type: text/html; charset=UTF-8');

require ('./system.php');
execute();

DBinit('index');

loadTool('user.class.php');
$user = AuthCore::getLoader()->userLoad();

function LoadTinyMCE()
{
    global $addition_events, $content_js, $site_ways;

    if (!file_exists(getWay('system') . 'tiny_mce/tinymce.min.js'))
        return false;

    $tmce = 'tinymce.init({';
    $tmce .= 'selector: "textarea.tinymce",';
    $tmce .= 'language : "ru",';
    $tmce .= 'plugins: "code preview image link",';
    $tmce .= 'toolbar: "undo redo | bold italic | alignleft aligncenter alignright alignjustify | numlist bullist outdent indent | link image | preview",';
    $tmce .= '});';

    $addition_events .= $tmce;
    $content_js .= '<script type="text/javascript" src="' . $site_ways['system'] . 'tiny_mce/tinymce.min.js"></script>';

    return true;
}

function InitJS()
{
    global $addition_events, $site_ways;

    $initJs = '<script type="text/javascript">'
             . 'var pbm; '
             . 'var way_style = "' . DEF_STYLE_URL . '"; '
             . 'var cur_style = "' . View::GetURL() . '";'
             . 'var base_url  = "' . BASE_URL . '";'
             . 'window.onload = function () { mcr_init(); ' . $addition_events . ' } '
             . '</script>';    

    $sd = $site_ways['system'] . 'js/';
    
    $initJs .= ' <script src="'. $sd . 'ajax.js"></script>';
    $initJs .= ' <script src="'. $sd . 'monitoring.js"></script>';
    $initJs .= ' <script src="'. $sd . 'tools.js"></script>';

    return $initJs;
}

$menu = new Menu();

$content_main = '';
$content_side = '';
$addition_events = '';
$content_js = '';
$content_advice = '';

if ($config['offline'] and (!$user or $user->group() != 3))
    exit(View::ShowStaticPage('site_closed.html'));

if ($user) {

    $player = $user->name();
    $player_id = $user->id();
    $player_lvl = $user->lvl();
    $player_email = $user->email();
    if (!$player_email) $player_email = lng('NOT_SET');
    $player_group = $user->getGroupName();
    $player_money = $user->getMoney();
    
    $user->activity();
    
    if ($user->group() == 4) {
        $content_main .= View::ShowStaticPage('profile_verification.html', 'profile/', $player_email);   
    }
} 

if (Filter::input('id', 'get', 'int')){
    $mode = 'news_full';
} else {
    $mode = Filter::input('mode', 'post', 'string', true);
    if ($mode === false) $mode = Filter::input('mode', 'get', 'string', true);
    if (!$mode) $mode = $config['s_dpage'];
}

switch ($mode) {
    case 'start': $page = 'Начать игру';
        $content_main = View::ShowStaticPage('start_game.html');
        break;
    case 'register':
    case 'news': include('./location/news.php');
        break;
    case 'news_full': include('./location/news_full.php');
        break;
    case 'options': include('./location/options.php');
        break;
    case 'news_add': include('./location/news_add.php');
        break;
    case 'control': include('./location/admin.php');
        break;
    default:
        if (!preg_match("/^[a-zA-Z0-9_-]+$/", $mode) or
            !file_exists(MCR_ROOT . 'location/' . $mode . '.php')) {
            $mode = $config['s_dpage'];
        }

        include(MCR_ROOT . 'location/' . $mode . '.php');
        break;
}

include('./location/side.php');

$content_menu = $menu->Show();
$content_js .= InitJS();

include View::Get('index.html');
