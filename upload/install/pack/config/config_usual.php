<?php
$bd_names = array(
    'users' => 'accounts',    
    'user_banning' => 'user_banning',
    'likes' => 'likes',
    'ip_banning' => 'ip_banning',
    'news' => 'news',
    'news_categorys' => 'news_categorys',
    'groups' => 'groups',
    'data' => 'data',
    'files' => 'files',
    'comments' => 'comments',
    'servers' => 'servers',
    'action_log' => 'action_log',
    'iconomy' => false,
);

$config = array(

    /* MySQL connection */

    'db_host' => 'localhost',
    'db_port' => 3306,
    'db_login' => 'root',
    'db_passw' => '',
    'db_name' => 'mcraft',
    
    /* site constants */
    
    's_name' => 'MCR ' . MCR,
    's_about' => 'Личный кабинет для онлайн сервера игры Minecraft',
    's_keywords' => 'сервер игра онлайн NC22 Minecraft',
    's_dpage' => 'news',
    's_theme' => View::DEFAULT_THEME,
    's_root' => '/',
    
    'news_by_page' => 5,
    'comm_by_page' => 5,
    'comm_revers' => false,
    'game_news' => 1,
    
    /* system */
    
    'timezone' => 'Asia/Vladivostok',
    'default_skin' => true,
    'sbuffer' => true,
    'rewrite' => true,
    'log' => false,
    'offline' => false,
    'smtp' => false,
    'remote' => false,
    
    'p_logic' => 'usual', // authorization driver
    'p_encode' => 'Md5', // encode options for driver
    
    /* action limiter */
    
    'action_log' => false, // log connect with BD times and detect some fast users, possible bots
    'action_max' => 10, // maximum exec php script's times ( server monitorings, page refresh, profile edit and etc.)
    'action_time' => 1, // per seconds. 
    'action_ban' => 60, // ban time in seconds
);

$site_ways = array(
    'style' => 'data/style/',
    'upload' => 'data/upload/',
    'tmp' => 'data/tmp/',
    'system' => 'data/system/',
    'skins' => 'data/upload/skins/',
    'cloaks' => 'data/upload/cloaks/',
    'distrib' => 'data/upload/downloads/',
);

/* iconomy or some other plugin, just check names */

$bd_money = array(
    'login' => 'username',
    'money' => 'balance',
);

$bd_users = array(
    'login' => 'login',
    'id' => 'id',
    'password' => 'password',
    'ip' => 'ip',
    'email' => 'email',
    'female' => 'female',
    'group' => 'group',
    'deadtry' => 'deadtry',
    'tmp' => 'tmp',
    'ban' => 'ban_until',
    'session' => 'session',
    'server' => 'server',
    'clientToken' => 'clientToken',
    'ctime' => 'create_time',
);
