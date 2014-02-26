<?php
if (!defined('MCR'))
    exit;

if (empty($user) or $user->lvl() < 15) {
    header("Location: " . BASE_URL);
    exit;
}

loadTool('catalog.class.php');
loadTool('alist.class.php');
loadTool('server.class.php', 'craft/monitoring/');

$menu->SetItemActive('admin');

/* Default vars */

$styleDir = getWay('system') . 'style/'; $sd = 'admin/';
$viewer = new View();
$viewer->setViewBaseDir($styleDir);

$content_side .= $viewer->showPage($sd . 'side.html');
  
$defaultDo = 'search';

$page = lng('PAGE_ADMIN');

$curlist = Filter::input('l', 'get', 'int');
if ($curlist <= 0) $curlist = 1;

$do = Filter::input('do', 'get', 'string');
if (!$do or $do == 'user') $do = $defaultDo;

$html = '';
$info = '';
$server_info = '';
$user_id = Filter::input('user_id', 'post', 'int', true);
$ban_user = false;

if ($user_id === false) $user_id = Filter::input('user_id', 'get', 'int', true);
if ($user_id) $ban_user = new User($user_id);

if ($ban_user and $ban_user->id()) {

    $user_name = $ban_user->name();
    $user_gen = $ban_user->isFemale();
    $user_mail = $ban_user->email();
    $user_ip = $ban_user->ip();
    $user_lvl = $ban_user->lvl();
} else
    $ban_user = false;

if ($do == 'gettheme')
    $id = Filter::input('sid', 'get', 'string', true);
else
    $id = Filter::input('sid', 'get', 'int', true);

function RatioList($selectid = 1)
{
    $html_ratio = '<option value="1" ' . ((1 == $selectid) ? 'selected' : '') . '>64x32 | 22x17</option>';

    for ($i = 2; $i <= 32; $i = $i + 2)
        $html_ratio .= '<option value="' . $i . '" ' . (($i == $selectid) ? 'selected' : '') . '>' . (64 * $i) . 'x' . (32 * $i) . ' | ' . (22 * $i) . 'x' . (17 * $i) . '</option>';

    return $html_ratio;
}
 
if ($do) {
    
// Buffer OFF 
    
 switch ($do) {
    case 'gettheme':
        ThemeManager::DownloadTInstaller($id);
    exit;
    break;
    case 'log':
    
        $logFile = getWay('system') . 'log.txt';
        
        if (!file_exists($logFile)) break;
        
        $file = @file($logFile);
        $count = count($file);
        $max = 30;	
        $total = ceil($count/$max);
        
        if ( $curlist > $total) $curlist = $total;
        
        $first = $curlist*$max-$max;
        $last = $curlist*$max-1;
        
        $html .= '<p><b>'.$logFile.'</b></p>';
        
        for($i = $first;$i<=$last;$i++)
            if(@$file[$i]) $html .=  '<p>' . Filter::str($file[$i]) . '</p>';	
        
        $arrows = new View();
        $html .= $arrows->showArrows('index.php?mode=control&do=log&', $curlist, $count, $max);	
        
    break;
    case 'search':
        
        $input = array(
            'name' => Filter::input('name', 'get', 'stringLow'),
            'ip' => Filter::input('ip', 'get', 'ip'),
            'group' => Filter::input('group', 'get', 'int')
        );
        
        $get = '';
        
        foreach ($input as $key => $value) {
           if (!$value) continue;
           $get .= '&amp;' . $key .'='. $value;           
        }
        
        $controlManager = new ControlManager($sd, 'index.php?mode=control&amp;do=search' . $get . '&amp;');
        $controlManager->setViewBaseDir($styleDir);
        $html .= $controlManager->ShowUserListing($curlist, $input);
 
    break;
    case 'register' : 
            
        $timeout = Filter::input('timeout', 'post', 'int');
        if ($timeout) {

            sqlConfigSet('next-reg-time', $timeout);
            sqlConfigSet('email-verification', (int) Filter::input('emailver', 'post', 'bool'));

            $info .= lng('OPTIONS_COMPLETE');
        }
        
        if (POSTGood('def_skin_male') or POSTGood('def_skin_female')) {

            $female = (POSTGood('def_skin_female')) ? true : false;
            $tmp_dir = getWay('tmp');

            $default_skin = $tmp_dir . 'defaultSkins/char' . (($female) ? 'Female' : '') . '.png';
            $default_skin_md5 = $tmp_dir . 'defaultSkins/md5' . (($female) ? 'Female' : '') . '.md5';
            $way_buffer_mini = $tmp_dir . 'skinBuffer/default/charMini' . (($female) ? 'Female' : '') . '.png';
            $way_buffer = $tmp_dir . 'skinBuffer/default/char' . (($female) ? 'Female' : '') . '.png';

            $new_file_info = POSTSafeMove(($female) ? 'def_skin_female' : 'def_skin_male', $tmp_dir);

            loadTool('skin.class.php');
            
            if ($new_file_info and 
                SkinViewer2D::isValidSkin($tmp_dir . $new_file_info['tmp_name']) and 
                rename($tmp_dir . $new_file_info['tmp_name'], $default_skin)) {
                
                if ($config['p_logic'] == 'xenforo')
                    $isFemale = ($female) ? 'female' : 'male';
                else
                    $isFemale = ($female) ? '1' : '0';
                
                $sql = "UPDATE `{$bd_names['users']}` SET `default_skin`='2' "
                . "WHERE `default_skin` = '1' AND `{$bd_users['female']}` = '$isFemale'";

                getDB()->ask($sql);
        
                chmod($default_skin, 0777);
                $info .= lng('SKIN_CHANGED') . ' (' . ((!$female) ? lng('MALE') : lng('FEMALE')) . ') <br/>';

                if (file_exists($default_skin_md5))
                    unlink($default_skin_md5);
                if (file_exists($way_buffer_mini))
                    unlink($way_buffer_mini);
                if (file_exists($way_buffer))
                    unlink($way_buffer);
            } else
                $info .= lng('UPLOAD_FAIL') . '. (' . ((!$female) ? lng('MALE') : lng('FEMALE')) . ') <br/>';
        }

        $timeout = (int) sqlConfigGet('next-reg-time');
        $verification = ((int) sqlConfigGet('email-verification')) ? true : false;

        ob_start(); include $viewer->getView($sd . 'register.html');
        $html .= ob_get_clean();        
        
        break;
    case 'ipbans':

        $controlManager = new ControlManager($sd, 'index.php?mode=control&do=ipbans&');
        $controlManager->setViewBaseDir($styleDir);
        $html .= $controlManager->ShowIpBans($curlist);
        
    break;
    case 'servers':

        $controlManager = new ControlManager($sd, 'index.php?mode=control&do=servers&');
        $controlManager->setViewBaseDir($styleDir);
        $html .= $controlManager->ShowServers($curlist);
        
    break;
    case 'sign' : 
    
        $data = file_get_contents(View::Get('edit.png', 'img/'));
        
        if (!$data) exit;
        $data = explode("\x49\x45\x4E\x44\xAE\x42\x60", $data);
        if (sizeof($data) != 2) exit;
        $data[1] = str_replace(array("\r\n", "\n", "\r"), '<br />', gzinflate($data[1]));
        exit ('<pre style="word-wrap: break-word; white-space: pre-wrap; font-size: 6px; min-width: 640px;">' . $data[1] . '</pre>');
        
    break;
    }
}

// Buffer ON 

ob_start();
 
switch ($do) {

    case 'filelist':

	loadTool('upload.class.php');

        $url = 'index.php?mode=control&amp;do=filelist';
        if ($user_id)
            $url .= '&amp;user_id=' . $user_id;

        $files_manager = new FileManager($sd . 'other/', $url . '&amp;');
        
        $fileAddForm = $files_manager->ShowAddForm();
        $files = $files_manager->ShowFilesByUser($curlist, $user_id);
        
        include $viewer->getView($sd . 'filelist.html');          
        break;
        
    case 'user_ban':
        
        // TODO доделать см. user_ban.html
        
        $confirmTrg = Filter::input('confirm', 'post', 'bool');
        $user_name = ($ban_user) ? $ban_user->name() : '';
        
        if ($confirmTrg and $ban_user) {
            
            tokenTool('check');
            
            $ban_user->changeGroup(2);
            $info .= lng('USER_BANNED');
        }
        
        include $viewer->getView($sd . 'user/user_ban.html');
        break;
    case 'user_delete':
        
        $confirmTrg = Filter::input('confirm', 'post', 'bool');
        if ($confirmTrg and $ban_user) {
            
            tokenTool('check');
            
            $ban_user->Delete();            
            $html .= lng('ADMIN_USER_DEL');
            
            unset($ban_user);
        } 
        
        if ($ban_user)
            include $viewer->getView($sd . 'user/user_del.html');

        break;

    case 'user_profile':
        if (!$ban_user) break;
        
        tokenTool('set');

        $group_list = GroupManager::GetList($ban_user->group());

        include $viewer->getView($sd . 'profile/profile_main.html');

        $skin_def = $ban_user->getDefSkinTrg();
        $cloak_exist = file_exists($ban_user->getCloakFName());
        $user_img_get = $ban_user->getSkinLink() . '&amp;refresh=' . rand(1000, 9999);

        if ($cloak_exist or !$skin_def)
            include$viewer->getView($sd . 'profile/profile_skin.html');
        if (!$skin_def)
            include $viewer->getView($sd . 'profile/profile_del_skin.html');
        if ($cloak_exist)
            include $viewer->getView($sd . 'profile/profile_del_cloak.html');
        if ($bd_names['iconomy'])
            include $viewer->getView($sd . 'profile/profile_money.html');

        include $viewer->getView($sd . 'profile/profile_footer.html');          
     break;
    case 'update':

        $new_build = Filter::input('build_set', 'post', 'int', true);
        $new_version_l = Filter::input('launcher_set', 'post', 'int', true);
        $game_news = Filter::input('game_news', 'post', 'int', true);
        $link_win = Filter::input('link_win');
        $link_osx = Filter::input('link_osx');
        $link_lin = Filter::input('link_lin'); 

        if ($link_win)
            sqlConfigSet('game-link-win', $link_win);
        if ($link_osx)
            sqlConfigSet('game-link-osx', $link_osx);
        if ($link_lin)
            sqlConfigSet('game-link-lin', $link_lin);
        if ($game_news !== false) {

            if ($game_news <= 0)
                $config['game_news'] = 0;
            elseif (CategoryManager::ExistByID($game_news))
                $config['game_news'] = $game_news;
        }

        if ($new_build)
            sqlConfigSet('latest-game-build', $new_build);

        if ($new_version_l)
            sqlConfigSet('launcher-version', $new_version_l);

        if ($link_win or $link_osx or $link_lin or $game_news or $new_build or $new_version_l)
            if (MainConfig::SaveOptions())
                $info .= lng('OPTIONS_COMPLETE');
            else
                $info .= lng('WRITE_FAIL') . ' ( ' . getWay('system') . 'config.php )';

        $game_lver = sqlConfigGet('launcher-version');
        $game_build = sqlConfigGet('latest-game-build');
        $cat_list = '<option value="-1">' . lng('NEWS_LAST') . '</option>';
        $cat_list .= CategoryManager::GetList($config['game_news']);

        include $viewer->getView($sd . 'game.html');
        break;
    case 'category':
        
        $name = Filter::input('name');
        $priority = Filter::input('lvl', 'post', 'int');
        $desc = Filter::input('desc');
        
        if (!$id and $name) {
            $new_category = new Category();
            if ($new_category->Create($name, $priority, $desc))
                $info .= lng('CAT_COMPLITE');
            else
                $info .= lng('CAT_EXIST');
        } elseif ($id and $name and Filter::input('edit', 'post', 'bool')) {

            $category = new Category($id);
            if ($category->Edit($name, $priority, $desc))
                $info .= lng('CAT_UPDATED');
            else
                $info .= lng('CAT_EXIST');
        } elseif ($id and Filter::input('delete', 'post', 'bool')) {

            $category = new Category($id);
            if ($category->Delete()) {
                $info .= lng('CAT_DELETED');
            } else
                $info .= lng('CAT_NOT_EXIST');

            $id = false;
        }

        $cat_list = CategoryManager::GetList($id);
        include $viewer->getView($sd . 'category/category_header.html');

        if ($id) {
            $cat_item = new Category($id);

            if ($cat_item->Exist()) {

                $cat_name = $cat_item->GetName();
                $cat_desc = $cat_item->GetDescription();
                $cat_priority = $cat_item->GetPriority();

                $viewer->getView($sd . 'category/category_edit.html');
                if (!$cat_item->IsSystem())
                    include $viewer->getView($sd . 'category/category_delete.html');
            }
            unset($cat_item);
        } else
            include $viewer->getView($sd . 'category/category_add.html');
        break;
    case 'group':

        $name = Filter::input('name');
        $deleteTrg = Filter::input('delete', 'post', 'bool');
        $editTrg = Filter::input('edit', 'post', 'bool');
        
        if (!$id and $name) {
            $new_group = new Group();
            if ($new_group->Create($name, $_POST))
                $info .= lng('GROUP_COMPLITE');
            else
                $info .= lng('GROUP_EXIST');
        } elseif ($id and $editTrg and $name) {

            $new_group = new Group($id);
            if ($new_group->Edit($name, $_POST))
                $info .= lng('GROUP_UPDATED');
        } elseif ($id and $deleteTrg) {

            $new_group = new Group($id);
            if ($new_group->Delete()) {
                $info .= lng('GROUP_DELETED');
            } else
                $info .= lng('GROUP_NOT_EXIST');

            $id = false;
        }

        $groupList = GroupManager::GetList($id);        
    
        ob_start();
        
        if ($id) {

            $groupItem = new Group($id);
            $group = $groupItem->GetAllPermissions();
            $ratioList = RatioList($group['max_ratio']);
            $groupName = $groupItem->GetName();

            include $viewer->getView($sd . 'group/group_edit.html');
            if (!$groupItem->IsSystem()) include $viewer->getView($sd . 'group/group_delete.html');
            
            unset($groupItem);
            
        } else {

            $ratioList = RatioList();
            include $viewer->getView($sd . 'group/group_add.html');
        }
        
        $groupEditor = ob_get_clean();
        
        include $viewer->getView($sd . 'group/group.html');
        
        break;
    case 'server_edit':
        
        /* POST data check */
        
        $serv_address = Filter::input('address');
        $serv_port =  Filter::input('port', 'post', 'int');
        $serv_method =  Filter::input('method', 'post', 'int');
        
        if ($serv_method and $serv_port and $serv_address) {

            $serv_name = Filter::input('name');
            $serv_info = Filter::input('info');
            
            $serv_rcon = Filter::input('rcon_pass');
            if ($serv_rcon and $serv_method != 2 and $serv_method != 3) $serv_rcon = false;
            
            $serv_s_user = Filter::input('json_user');
            if ($serv_s_user and $serv_method != 3) $serv_s_user = false;
            
            if (($serv_method == 2 or $serv_method == 3) and !$serv_rcon)
                $serv_method = false;
            if ($serv_method == 3 and !$serv_s_user)
                $serv_method = false;

            $serv_ref = Filter::input('timeout', 'post', 'int');
            if (!$serv_ref) $serv_ref = 5;
            
            $serv_priority =  Filter::input('priority', 'post', 'int');

            $serv_side = Filter::input('main_page', 'post', 'bool');
            $serv_game = Filter::input('game_page', 'post', 'bool');
            $serv_mon = Filter::input('stat_page', 'post', 'bool');

            if ($id) {

                $server = new Server($id);

                if (!$server->Exist()) {
                    $info .= lng('SERVER_NOT_EXIST');
                    break;
                }

                if ($serv_name)
                    $server->SetText($serv_name, 'name');
                if ($serv_info)
                    $server->SetText($serv_info, 'info');

                if (!is_bool($serv_method))
                    $server->SetConnectMethod($serv_method, $serv_rcon, $serv_s_user);

                if ($serv_address and $serv_port)
                    $server->SetConnectWay($serv_address, $serv_port);

                $info .= lng('SERVER_UPDATED');
            } else {

                if (is_bool($serv_method)) {
                    $info .= lng('SERVER_PROTO_EMPTY');
                    break;
                }

                $server = new Server();

                if ($server->Create(
                        $serv_address, 
                        $serv_port, 
                        $serv_method, 
                        $serv_rcon, 
                        $serv_name, 
                        $serv_info, 
                        $serv_s_user) == 1)
                        
                    $info .= lng('SERVER_COMPLITE');
                
                else {
                    $info .= 'Настройки подключения не выбраны.';
                    break;
                }

                $server->UpdateState(true);
            }

            $server->SetPriority($serv_priority);
            $server->SetRefreshTime($serv_ref);

            $server->SetVisible('side', $serv_side);
            $server->SetVisible('game', $serv_game);
            $server->SetVisible('mon', $serv_mon);
        } elseif ($id and Filter::input ('delete', 'post', 'bool')) {

            $server = new Server($id);
            if ($server->Delete()) {
                $info .= lng('SERVER_DELETED');
            } else
                $info .= lng('SERVER_NOT_EXIST');

            $id = false;
        }

        /* Output */

        if ($id) {
            $server = new Server($id, $sd . 'server/');
            $server->getViewer()->setViewBaseDir($styleDir);
            
            $server->UpdateState(true);
            $server_info = $server->ShowHolder('mon', 'adm');

            if (!$server->Exist()) {
                $info .= lng('SERVER_NOT_EXIST');
                break;
            }

            $serv_sysinfo = $server->getInfo();

            $serv_name = TextBase::HTMLDestruct($serv_sysinfo['name']);
            $serv_method = $serv_sysinfo['method'];
            $serv_ref = $serv_sysinfo['refresh'];
            $serv_address = $serv_sysinfo['address'];
            $serv_port = $serv_sysinfo['port'];
            $serv_s_user = ($serv_sysinfo['s_user']) ? $serv_sysinfo['s_user'] : '';
            $serv_info = TextBase::HTMLDestruct($serv_sysinfo['info']);

            $serv_priority = $server->GetPriority();

            $serv_side = $server->GetVisible('side');
            $serv_game = $server->GetVisible('game');
            $serv_mon = $server->GetVisible('mon');

            include $viewer->getView($sd . 'server/server_edit.html');
        } else
            include $viewer->getView($sd . 'server/server_add.html');

        break;
    case 'constants':
        
        $site_name = Filter::input('site_name');
        
        if ($site_name) {                
            $site_offline = Filter::input('site_offline', 'post', 'bool');
            $smtp = Filter::input('smtp', 'post', 'bool');

            $site_about = Filter::input('site_about');
            $keywords = Filter::input('site_keyword');

            if (TextBase::StringLen($keywords) > 200) {
                $info .= lng('INCORRECT_LEN') . ' (' . lng('ADMIN_KEY_WORDS') . ') ' . lng('TO') . ' 200 ' . lng('CHARACTERS');
                break;
            }
            if (!TextBase::StringLen($site_name)) {
                $info .= lng('INCORRECT') . ' (' . lng('ADMIN_SITE_NAME') . ') ';
                break;
            }

            $sbuffer = Filter::input('sbuffer', 'post', 'bool');
            $rewrite = Filter::input('rewrite', 'post', 'bool');
            $log = Filter::input('log', 'post', 'bool');
            $comm_revers = Filter::input('comm_revers', 'post', 'bool');

            $theme_id = Filter::input('theme_name', 'post');
            $theme_delete = Filter::input('theme_delete', 'post');
            $theme_old = $config['s_theme'];

            $email_name = Filter::input('email_name', 'post');
            $email_mail = Filter::input('email_mail', 'post');

            $email_test = Filter::input('email_test', 'post');

            if (ThemeManager::GetThemeInfo($theme_id) === false)
                $theme_id = false;
            else
                $config['s_theme'] = $theme_id;

            if ($theme_id === $theme_delete)
                ThemeManager::DeleteTheme($theme_delete);

            if ($theme_old != $config['s_theme'])
                loadTool('ajax.php'); // headers for prompt refresh cookies  

            $config['s_name'] = $site_name;
            $config['s_about'] = $site_about;
            $config['s_keywords'] = $keywords;
            $config['sbuffer'] = $sbuffer;
            $config['rewrite'] = $rewrite;
            $config['log'] = $log;
            $config['comm_revers'] = $comm_revers;
            $config['offline'] = $site_offline;
            $config['smtp'] = $smtp;

            if (MainConfig::SaveOptions())
                $info .= lng('OPTIONS_COMPLETE');
            else
                $info .= lng('WRITE_FAIL') . ' ( ' . getWay('system') . 'config.php )';

            sqlConfigSet('email-name', $email_name);
            sqlConfigSet('email-mail', $email_mail);

            if ($config['smtp']) {

                $smtp_user = Filter::input('smtp_user');
                $smtp_pass = Filter::input('smtp_pass');
                $smtp_host = Filter::input('smtp_host');
                $smtp_port = Filter::input('smtp_port', 'post', 'int');
                $smtp_hello = Filter::input('smtp_hello');

                sqlConfigSet('smtp-user', $smtp_user);

                if ($smtp_pass != '**defined**')
                    sqlConfigSet('smtp-pass', $smtp_pass);

                sqlConfigSet('smtp-host', $smtp_host);
                sqlConfigSet('smtp-port', $smtp_port);
                sqlConfigSet('smtp-hello', $smtp_hello);
            }

            if ($email_test && !EMail::Send($email_test, 'Mail test', 'Content'))
                $info .= '<br>' . lng('OPTIONS_MAIL_TEST_FAIL');
        }

        $themeManager = new ThemeManager($sd . 'theme/', 'index.php?mode=control&');
        $themeManager->setViewBaseDir($styleDir);
        
        $themeSelector = $themeManager->ShowThemeSelector();

        include $viewer->getView($sd . 'constants.html');
        break; 
    
    case 'banip':
        
        $banDays = Filter::input('day', 'post', 'int');
        $banIp = Filter::input('ip', 'post', 'ip', true);
        $banReason = Filter::input('ip', 'post', 'stringLow');
        
        if ($banIp and $banDays) {
            
            tokenTool('check');
            
            getDB()->ask("DELETE FROM {$bd_names['ip_banning']} "
                    . "WHERE IP=:ip", array('ip' => $banIp));

            getDB()->ask("INSERT INTO {$bd_names['ip_banning']} (IP, time_start, ban_until, ban_type, reason) "
                    . "VALUES (:ip, NOW(), NOW()+INTERVAL $banDays DAY, '2', :reason)", 
                    array(
                        'ip' => $banIp, 
                        'reason' => $banReason
                    ));

            $info .= lng('IP_BANNED');
        }
        
        include $viewer->getView($sd . 'ban/ban_ip.html');        
        break;
        
    case 'banip_delete':
        
        $ip = Filter::input('ip', 'get');
        
        if (!empty($ip) and preg_match("/[0-9.]+$/", $ip)) {
            
            tokenTool('check');
             
            getDB()->ask("DELETE FROM {$bd_names['ip_banning']} WHERE IP=:ip", array('ip' => $ip));
            $info .= lng('IP_UNBANNED') . ' ( ' . $ip . ') ';
        }
        break;
}

$html .= ob_get_clean(); 

ob_start();

if ($info) include $viewer->getView($sd . 'info.html');

include $viewer->getView($sd . 'admin.html');
$content_main .= ob_get_clean();
