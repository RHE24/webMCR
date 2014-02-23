<?php
if (!defined('MCR')) exit;

$page = 'Страница не найдена';
$sub_dir = '';
$route = Filter::input('route', 'get', 'string', true);
if ($route and 
    (strpos($route, $site_ways['skins']) !== false or
    strpos($route, $site_ways['cloaks']) !== false or
    strpos($route, $site_ways['distrib']) !== false)) 

	$sub_dir = 'launcher/';

$content_main = View::ShowStaticPage('404.html', $sub_dir );
if ($sub_dir) exit($content_main); 
