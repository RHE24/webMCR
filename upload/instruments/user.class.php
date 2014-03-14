<?php
if (!defined('MCR')) exit;

/* User class | User group class */

class User 
{    
    private $db;
    private $id = false;
    private $permissions = null;
    private $tmp;
    private $ip;
    private $name;
    private $email;
    private $lvl;
    private $group;
    private $gender;
    private $female;
    private $deadtry;

    /** @const */
    public static $date_statistic = array(
        'create_time',
        'gameplay_last',
        'active_last',
    );

    /** @const */
    public static $int_statistic = array(
        'comments_num',
        'play_times',
        'undress_times',
    );

    public function __construct($input, $method = false)
    {
        global $bd_users, $bd_names;

        $this->db = $bd_names['users'];
        
        if (!$method) {
            $method = $bd_users['id'];
        }
        
        $method = $bd_users[array_search($method, $bd_users)];

        if ($method === $bd_users['id']) {
            $input = (int) $input;
        }

        if (!$input) {
            return false;
        }

        $sql = "SELECT `{$bd_users['login']}`,"
                        . "`{$bd_users['id']}`,"
                        . "`{$bd_users['tmp']}`,"
                        . "`{$bd_users['ip']}`,"
                        . "`{$bd_users['email']}`,"
                        . "`{$bd_users['deadtry']}`,"
                        . "`{$bd_users['female']}`,"
                        . "`{$bd_users['group']}` FROM `{$this->db}` " . "WHERE `$method`=:input";

        $line = getDB()->fetchRow($sql, array('input' => $input));
        if (!$line) {
            return false;
        }

        $this->permissions = null;

        $this->id = (int) $line[$bd_users['id']];
        $this->name = $line[$bd_users['login']];
        $this->group = (int) $line[$bd_users['group']];
        $this->lvl = $this->getPermission('lvl');

        $this->tmp = $line[$bd_users['tmp']];
        $this->ip = $line[$bd_users['ip']];

        $this->email = $line[$bd_users['email']];
        if (empty($this->email)) $this->email = false;
        
        $this->deadtry = (int) $line[$bd_users['deadtry']];

        /* Пол персонажа */
        $gender = $line[$bd_users['female']];

        $this->gender = (is_numeric($gender)) ? (int) $gender : (($gender == 'female' or $gender == 'male') ? (($gender == 'female') ? 1 : 0) : 10);
        $this->female = ($this->gender == 1) ? true : false;

        return true;
    }

    public function activity()
    {
        global $bd_users;

        if ($this->id) {
            getDB()->ask("UPDATE `{$this->db}` SET `active_last`= NOW() "
                    . "WHERE `{$bd_users['id']}`='" . $this->id . "'");
        }
    }

    public function isOnline()
    {
        if ($this->tmp === '0')
            return false;

        $last_active = $this->getStatisticTime('active_last');
        if (!$last_active)
            return false;

        if (time() - strtotime($last) > 300)
            return false;

        return true;
    }

    public function authenticate($pass)
    {
        global $bd_users, $config;

        if (!$this->id)
            return false;
        
        if ($config['remote']) {
            loadTool('auth.php', 'auth/remote/');
            
            $remoteAuth = new RemoteAuth();
            return $remoteAuth->authenticate($pass, $this);
        }
        
        $sql = "SELECT `{$bd_users['password']}` FROM `{$this->db}` "
                . "WHERE `{$bd_users['id']}`='" . $this->id . "'";
        $line = getDB()->fetchRow($sql, false, 'num');

        $test_pass = AuthCore::getEncoder()->checkPass($line[0], $pass, $this);

        if (!$test_pass) {

            getDB()->ask("UPDATE `{$this->db}` SET `{$bd_users['deadtry']}`= {$bd_users['deadtry']} + 1 "
                    . "WHERE `{$bd_users['id']}`='" . $this->id . "'");

            $this->deadtry++;
        }  
        
        return ($test_pass) ? true : false;
    }

    public function login($save = false)
    {
        global $bd_users;

        if (!$this->id)
            return false;
        
        $ip = GetRealIp();
        $save = ($save) ? true : false;
        $tmp = randString(15);

        $sql = "UPDATE `{$this->db}` SET `{$bd_users['deadtry']}` = '0', "
                . "`{$bd_users['tmp']}`=:tmp, "
                . "`{$bd_users['ip']}`=:ip "
                . "WHERE `{$bd_users['id']}`='" . $this->id . "'";

        getDB()->ask($sql, array('ip' => $ip, 'tmp' => $tmp));

        $this->tmp = $tmp;

        if (!isset($_SESSION))
            session_start();

        $_SESSION['user_id'] = $this->id();
        $_SESSION['user_name'] = $this->name();
        $_SESSION['ip'] = $this->ip();

        if ($save)
            setcookie("webMCRCookie", $tmp, time() + 60 * 60 * 24 * 30 * 12, '/');
        
        AuthCore::getLoader()->onUserLogin($this);        
        return true;
    }

    public function logout()
    {
        global $bd_users;
        
        if (!isset($_SESSION))
            session_start();
        if (isset($_SESSION))
            session_destroy();

        $this->tmp = 0;

        $sql = "UPDATE `{$this->db}` SET `{$bd_users['tmp']}`='0' "
                . "WHERE `{$bd_users['id']}`='" . $this->id . "'";

        getDB()->ask($sql);

        if (isset($_COOKIE['webMCRCookie']))
            setcookie("webMCRCookie", "", time() - 3600);
        
        AuthCore::getLoader()->onUserLogout($this);
    }

    public function canPostComment()
    {
        global $bd_names;

        if (!$this->getPermission('add_comm'))
            return false;

        if ($this->group() == 3)
            return true;

        /* Интервал по времени 1 минута */

        $line = getDB()->fetchRow("SELECT COUNT(*) FROM `{$bd_names['comments']}` "
                . "WHERE `user_id`='" . $this->id . "' "
                . "AND `time`>NOW()-INTERVAL 1 MINUTE", false, 'num');

        if ((int) $line[0])
            return false;

        return true;
    }

    public function gameLoginConfirm()
    {
        global $bd_users;

        if (!$this->id)
            return false;

        getDB()->ask("UPDATE `{$this->db}` SET "
                . "`gameplay_last`=NOW(),"
                . "`play_times`=`play_times`+1 WHERE `{$bd_users['id']}`='" . $this->id . "'");

        return true;
    }

    public function gameLogoutConfirm()
    {
        global $bd_users;

        if (!$this->id)
            return false;

        $line = getDB()->fetchRow("SELECT COUNT(*) FROM `{$this->db}` "
                . "WHERE `{$bd_users['server']}` IS NOT NULL "
                . "AND `{$bd_users['id']}`='" . $this->id . "'", false, 'num');

        if ((int) $line[0])
            getDB()->ask("UPDATE `{$this->db}` SET `{$bd_users['server']}`=NULL "
                    . "WHERE `{$bd_users['id']}`='" . $this->id . "'");

        return true;
    }

    public function gameLoginLast()
    {
        global $bd_users;

        if (!$this->id)
            return false;

        $line = getDB()->fetchRow("SELECT `gameplay_last` FROM `{$this->db}` "
                . "WHERE `gameplay_last` <> '0000-00-00 00:00:00' "
                . "AND `{$bd_users['id']}`='" . $this->id . "'");

        if ($line)
            return $line['gameplay_last'];
        else
            return false;
    }

    public function getStatisticTime($param)
    {
        global $bd_users;

        if (!$this->id)
            return false;
        
        $param = self::$date_statistic[array_search($param, self::$date_statistic)];
 
        if ($param === 'create_time')
            $param = $bd_users['ctime'];

        $line = getDB()->fetchRow("SELECT `$param` FROM `{$this->db}` "
                . "WHERE `$param`<>'0000-00-00 00:00:00' "
                . "AND `{$bd_users['id']}`='" . $this->id . "'");

        if ($line) {

            if (is_numeric($line[$param]))
                
                return date('Y-m-d H:i:s', (int) $line[$param]); // from UNIX time		

            return $line[$param];
        } else
            return false;
    }

    public function getStatistic()
    {
        global $bd_users;

        if (!$this->id)
            return false;

        $line = getDB()->fetchRow("SELECT `" . implode("`, `", self::$int_statistic) . "` "
                . "FROM `{$this->db}` WHERE `{$bd_users['id']}`='" . $this->id . "'");

        if ($line)
            return $line;
        else
            return false;
    }

    public function isBanned() 
    {
        global $bd_names;
        
        if (!$this->exist()) return true;
        if ($this->lvl()) return false;
        
        RefreshBans();
        
        $line = getDB()->fetchRow("SELECT COUNT(*) FROM `{$bd_names['user_banning']}` WHERE `user_id`='{$this->id}'", false, 'num');
        if ((int) $line[0]) return true;
        else {
            $this->changeGroup(1);        
            return false;        
        }
    }
            
    
    public function setStatistic($field_name, $var)
    {
        global $bd_users;

        if (!$this->id)
            return false;

        $field_name = self::$int_statistic[array_search($field_name, self::$int_statistic)];

        $var = (int) $var;
        $dec = ( $var < 0 ) ? '-' : '+';
        $var = abs($var);

        if ($var > 0)
            $sql_var = '`' . $field_name . '`' . $dec . $var;
        else
            $sql_var = "'0'";

        getDB()->ask("UPDATE `{$this->db}` SET `" . $field_name . "`=" . $sql_var . " "
                . "WHERE {$bd_users['id']}='" . $this->id . "'");

        return true;
    }

    public function getMoney()
    {
        global $bd_names, $bd_money;

        if (!$this->id)
            return 0;

        if ($bd_names['iconomy']) {


            $line = getDB()->fetchRow("SELECT `{$bd_money['money']}` FROM `{$bd_names['iconomy']}` "
                    . "WHERE `{$bd_money['login']}`=:login", array('login' => $this->name), 'num');


            if (!$line) {

                getDB()->ask("INSERT INTO `{$bd_names['iconomy']}` (`{$bd_money['login']}`) "
                        . "VALUES (:login)", array('login' => $this->name));
                return 0;
            }

            return floatval($line[0]);
        }

        return 0;
    }

    public function addMoney($num)
    {
        global $bd_names, $bd_money;

        if (!$this->id)
            return false;
        if (!$bd_names['iconomy'])
            return false;

        $num = (int) $num;
        if (!$num)
            return $this->getMoney();

        $new_pl_money = $this->getMoney() + $num;
        if ($new_pl_money < 0)
            $new_pl_money = 0;

        getDB()->ask("UPDATE `{$bd_names['iconomy']}` SET `{$bd_money['money']}`=:money "
                . "WHERE `{$bd_money['login']}`=:login", array('money' => $new_pl_money, 'login' => $this->name));

        return $new_pl_money;
    }

    public function getSkinFName()
    {
        return getWay('skins') . $this->name . '.png';
    }

    public function getCloakFName()
    {
        return getWay('cloaks') . $this->name . '.png';
    }

    public function getGroupName()
    {
        if (!$this->id)
            return false;

        return GroupManager::GetNameByID($this->group);
    }

    public function deleteSkin()
    {
        if (file_exists($this->getSkinFName())) {
            unlink($this->getSkinFName());
            $this->deleteBuffer();
        }
    }

    public function deleteCloak()
    {
        if (file_exists($this->getCloakFName())) {
            unlink($this->getCloakFName());
            $this->deleteBuffer();
        }
    }

    public function getDefSkinMD5()
    {
        if (!$this->id)
            return false;

        $def_dir = getWay('tmp') . 'defaultSkins/';

        if ($this->isFemale())
            $default_skin_md5 = $def_dir . 'md5Female.md5';
        else
            $default_skin_md5 = $def_dir . 'md5.md5';

        if (file_exists($default_skin_md5)) {

            $md5 = @file($default_skin_md5);
            if ($md5[0])
                return $md5[0];
            else {
                vtxtlog('[action.php] error while READING md5 cache file. ' . $default_skin_md5);
                return false;
            }
        }

        if ($this->isFemale())
            $default_skin = $def_dir . 'charFemale.png';
        else
            $default_skin = $def_dir . 'char.png';

        if (file_exists($default_skin)) {

            $md5 = md5_file($default_skin);
            if (!$md5) {
                vtxtlog('[action.php] md5 generate error. ' . $default_skin);
                return false;
            }

            if ($fp = fopen($default_skin_md5, 'w')) {
                if (!fwrite($fp, $md5))
                    vtxtlog('[action.php] error while SAVE cache file. ' . $default_skin_md5);
                fclose($fp);
            } else
                vtxtlog('[action.php] error while CREATE cache file. ' . $default_skin_md5);

            return $md5;
        } else {
            vtxtlog('[action.php] default skin file missing. ' . $default_skin);
            return false;
        }
    }
    
    public function setDefSkinTrg($state) 
    {
        global $bd_users, $config;
        if (!$config['default_skin']) return true;    
        if (!$this->exist()) return false;
        
        $state = ($state) ? 1 : 0;        
        if (getDB()->ask("UPDATE `{$this->db}` SET `default_skin`='$state' WHERE `{$bd_users['id']}`='{$this->id()}'")) return true;        
        return false;               
    }
 
    public function getDefSkinTrg()
    { 
        global $bd_users, $config;
        if (!$this->exist()) return false;
        if (!$config['default_skin']) return !file_exists($this->getSkinFName()); 
        
        $line = getDB()->fetchRow("SELECT `default_skin` FROM `{$this->db}` "
                . "WHERE `{$bd_users['id']}`='{$this->id()}'", false, 'num');

        $isDefault = (int) $line[0];
        if (!file_exists($this->getSkinFName())) {
            $this->setDefaultSkin();
            return true;            
        } elseif ($isDefault == 2) {      
        
            $isDefault = false;            
            if (!strcmp($this->getDefSkinMD5(), md5_file($this->getSkinFName())))
                $isDefault = true;
                
            $this->setDefSkinTrg($isDefault);
        }
        return ($isDefault) ? true : false;
    }

    public function deleteBuffer()
    {
        $mini = getWay('tmp') . 'skinBuffer/' . $this->name . 'Mini.png';
        $skin = getWay('tmp') . 'skinBuffer/' . $this->name . '.png';

        if (file_exists($mini))
            unlink($mini);
        if (file_exists($skin))
            unlink($skin);
    }

    public function setDefaultSkin()
    {
        global $config;
        
        if (!$this->id) {
            return 0;
        }
        
        $this->deleteSkin();
        
        if (!$config['default_skin']) {            
            return 1;
        }
        
        $default_skin = getWay('tmp') . 'defaultSkins/char' . (($this->isFemale()) ? 'Female' : '') . '.png';

        if (!copy($default_skin, $this->getSkinFName()))
            vtxtlog('[SetDefaultSkin] error while COPY default skin for new user.');
        else
            $this->setDefSkinTrg(true);

        return 1;
    }

    public function changeName($newname)
    {
        global $bd_users;

        if (!$this->id)
            return 0;

        $newname = Filter::str($newname);

        if (!preg_match("/^[a-zA-Z0-9_-]+$/", $newname))
            return 1401;

        $line = getDB()->fetchRow("SELECT COUNT(*) FROM `{$this->db}` "
                . "WHERE `{$bd_users['login']}`=:login", array('login' => $newname), 'num');

        if ((int) $line[0])
            return 1402;

        if ((strlen($newname) < 4) or (strlen($newname) > 15))
            return 1403;

        getDB()->ask("UPDATE `{$this->db}` SET `{$bd_users['login']}`=:login "
                . "WHERE `{$bd_users['id']}`='{$this->id}'", array('login' => $newname));

        if (!empty($_SESSION['user_name']) and $_SESSION['user_name'] == $this->name)
            $_SESSION['user_name'] = $newname;

        /* Переименование файла скина и плаща */

        $way_tmp_old = $this->getSkinFName();
        $way_tmp_new = getWay('skins') . $newname . '.png';

        if (file_exists($way_tmp_old) and !file_exists($way_tmp_new))
            rename($way_tmp_old, $way_tmp_new);

        $way_tmp_old = $this->getCloakFName();
        $way_tmp_new = getWay('cloaks') . $newname . '.png';

        if (file_exists($way_tmp_old) and !file_exists($way_tmp_new))
            rename($way_tmp_old, $way_tmp_new);

        $buff_mini = getWay('tmp') . 'skinBuffer/' . $this->name . 'Mini.png';
        $buff_mini_new = getWay('tmp') . 'skinBuffer/' . $newname . '.png';
        $buff_skin = getWay('tmp') . 'skinBuffer/' . $this->name . '.png';
        $buff_skin_new = getWay('tmp') . 'skinBuffer/' . $newname . '.png';

        if (file_exists($buff_mini))
            rename($buff_mini, $buff_mini_new);
        if (file_exists($buff_skin))
            rename($buff_skin, $buff_skin_new);

        $this->name = $newname;

        return 1;
    }

    public function changePassword($newpass, $repass = false, $pass = false)
    {
        global $bd_users;

        if (!$this->id)
            return 0;

        if ($repass !== false) {

            if (strcmp($repass, $newpass))
                return 1504;

            $regular = "/^[a-zA-Z0-9_-]+$/";

            if (!preg_match($regular, $pass) or !preg_match($regular, $newpass))
                return 1501;

            $line = getDB()->fetchRow("SELECT `{$bd_users['password']}` FROM `{$this->db}` "
                    . "WHERE `{$bd_users['id']}`='{$this->id}'", false, 'num');

            if ($line == null or
                !AuthCore::getEncoder()->checkPass($line[0], $pass, $this))
                return 1502;            

            $minlen = 4;
            $maxlen = 15;
            $len = strlen($newpass);

            if (($len < $minlen) or ($len > $maxlen))
                return 1503;
        }
        
        $password = AuthCore::getEncoder()->createPass($newpass, $this);
        
        getDB()->ask("UPDATE `{$this->db}` "
                . "SET `{$bd_users['password']}`=:password "
                . "WHERE `{$bd_users['id']}`='{$this->id}'", array('password' => $password));

        return 1;
    }

    public function changeGroup($newgroup)
    {
        global $bd_users, $bd_names;

        $newgroup = (int) $newgroup;
        if ($newgroup < 0)
            return false;
        if ($newgroup == $this->group)
            return false;

        $line = getDB()->fetchRow("SELECT COUNT(*) FROM `{$bd_names['groups']}` "
                . "WHERE `id`=:group", array('group' => $newgroup), 'num');

        if (!(int) $line[0])
            return false;

        getDB()->ask("UPDATE {$this->db} SET `{$bd_users['group']}`=:group "
                . "WHERE `{$bd_users['id']}`='{$this->id}'", array('group' => $newgroup));

        $this->group = $newgroup;
        $this->permissions['lvl'] = null;
        $this->lvl = $this->getPermission('lvl');

        return true;
    }

    public function changeGender($female)
    {
        global $bd_users, $config;

        $female = (int) $female;

        if ($config['p_logic'] == 'xenforo')
            $isFemale = ($female == 1) ? 'female' : 'male';
        else
            $isFemale = ($female == 1) ? 1 : 0;

        if ((int) $this->gender() == $female)
            return false;

        getDB()->ask("UPDATE {$this->db} SET `{$bd_users['female']}`='$isFemale' "
                . "WHERE `{$bd_users['id']}`='{$this->id}'");

        $this->gender = $female;
        $this->female = ($female) ? true : false;

        $this->setDefaultSkin();
        return true;
    }

    public function changeEmail($email, $verification = false)
    {
        global $bd_users;

        $email = filter_var($email, FILTER_VALIDATE_EMAIL);
        if (!$email)
            return 1901;

        if ($email === $this->email) {

            if (!$verification)
                return 1;
        } else {

            $sql = "SELECT COUNT(*) FROM {$this->db} "
                    . "WHERE `{$bd_users['email']}`=:email "
                    . "AND `{$bd_users['id']}` != '{$this->id}'";

            $line = getDB()->fetchRow($sql, array('email' => $email), 'num');

            if ((int) $line[0])
                return 1902;
        }

        if ($verification) {

            $subject = lng('REG_CONFIRM') . ' ' . $_SERVER['SERVER_NAME'];
            $http_link = 'http://' . $_SERVER['SERVER_NAME'] . BASE_URL . 'register.php?id=' . $this->id() . '&verificate=' . $this->getVerificationStr();
            $message = '<html><body><p>' . lng('REG_CONFIRM_MES') . '. <a href="' . $http_link . '">' . lng('OPEN') . '</a></p></body></html>';

            $send_result = EMail::Send($email, $subject, $message);

            if ($verification and !$send_result)
                return 1903;
        }

        if ($email != $this->email)
            getDB()->ask("UPDATE {$this->db} "
                    . "SET `{$bd_users['email']}`=:email "
                    . "WHERE `{$bd_users['id']}`='{$this->id}'", array('email' => $email));

        $this->email = $email;

        return 1;
    }

    public function getSkinLink($mini = false, $amp = '&amp;', $refresh = false)
    {
        global $config;
        if ($this->exist() === false) return '';
        
        $female = ($this->isFemale()) ? '1' : '0';
        $get_p = '?';

        if ($mini == true) $get_p .= 'm=1' . $amp;
        
        if ($this->getDefSkinTrg() and ($mini or !file_exists($this->getCloakFName())))
            $get_p .= 'female=' . $female;
        else
            $get_p .= 'user_name=' . $this->name();

        if ($refresh)
            $get_p .= $amp . 'refresh=' . rand(1000, 9999);

        return $get_p;
    }

    public function changeVisual($post_name, $type = 'skin')
    {
        global $bd_users;

        if (!$this->id or !$this->getPermission(($type == 'skin') ? 'change_skin' : 'change_cloak'))
            return 1605;

        if (!POSTGood($post_name))
            return 1604;
        
        $new_file_info = POSTSafeMove($post_name, getWay('tmp'));
        if (!$new_file_info)
            return 1610;

        $way = $new_file_info['tmp_way'];

        if ((int) $this->getPermission('max_fsize') < $new_file_info['size_mb'] * 1024) {

            unlink($way);
            return 1601;
        }

        loadTool('skin.class.php');

        $newImgInfo = ($type == 'skin') ? SkinViewer2D::isValidSkin($way) : SkinViewer2D::isValidCloak($way);
        if (!$newImgInfo['scale'] or $newImgInfo['scale'] > (int) $this->getPermission('max_ratio')) {
            vtxtlog($newImgInfo['scale']);
            unlink($way);
            return 1602;
        }

        ($type == 'skin') ? $this->deleteSkin() : $this->deleteCloak();
        $new_way = ($type == 'skin') ? $this->getSkinFName() : $this->getCloakFName();

        if (rename($way, $new_way))
            chmod($new_way, 0644);
        else {

            unlink($way);
            vtxtlog('[Ошибка модуля загрузки] Ошибка копирования [' . $way . '] в [' . $new_way . '] . Проверьте доступ на ЧТЕНИЕ \ ЗАПИСЬ соответствующих папок.');
            return 1611;
        }

        if ($type == 'skin') {

            if (!strcmp($this->getDefSkinMD5(), md5_file($this->getSkinFName())))
                $this->setDefSkinTrg(true);
            else
                $this->setDefSkinTrg(false);
        }

        $this->deleteBuffer();

        getDB()->ask("UPDATE `{$this->db}` SET `undress_times`=`undress_times`+1 "
                . "WHERE `{$bd_users['id']}`='{$this->id}'");
        return 1;
    }

    public function Delete()
    {
        global $bd_users, $bd_names;

        if (!$this->id)
            return false;

        loadTool('catalog.class.php');

        $this->deleteCloak();
        $this->deleteSkin();
        $this->deleteBuffer();

        $result = getDB()->ask("SELECT `id` FROM `{$bd_names['comments']}` "
                . "WHERE `user_id`='{$this->id}'");

        while ($line = $result->fetch('num')) {

            $comment_del = new Comments_Item($line[0]);
            $comment_del->Delete();
            unset($comment_del);
        }

        getDB()->ask("DELETE FROM `{$this->db}` WHERE `{$bd_users['id']}`= '{$this->id}'");

        $this->id = false;
        return true;
    }

    public function getVerificationStr()
    {
        if (!$this->id)
            return false;

        $salt = sqlConfigGet('email-verification-salt');

        if (!$salt) {
            $salt = randString();
            sqlConfigSet('email-verification-salt', $salt);
        }

        return md5($this->id() . $salt);
    }

    public function getPermission($param)
    {
        if (isset($this->permissions[$param]))
            return $this->permissions[$param];
        if (!$this->id)
            return false;

        $group = new Group($this->group);
        $value = $group->GetPermission($param);

        unset($group);

        if ((int) $value == -1)
            return false;

        $this->permissions[$param] = $value;

        return $value;
    }

    public function isFemale()
    {
        return $this->female;
    }

    public function gender()
    {
        return $this->gender;
    }

    public function exist()
    {
        if ($this->id)
            return true;
        return false;
    }

    public function id()
    {
        return $this->id;
    }

    public function lvl()
    {
        return $this->lvl;
    }

    public function tmp()
    {
        return $this->tmp;
    }

    public function ip()
    {
        return $this->ip;
    }

    public function email()
    {
        return $this->email;
    }

    public function group()
    {
        return $this->group;
    }

    public function auth_fail_num()
    {
        return $this->deadtry;
    }

    public function name()
    {
        return $this->name;
    }
}

class Group extends TextBase
{   
    public static $permissions = array(
        "change_skin" => 'bool',
        "change_pass" => 'bool',
        "lvl" => 'int',
        "change_cloak" => 'bool',
        "change_login" => 'bool',
        "max_fsize" => 'int',
        "max_ratio" => 'int',
        "add_news" => 'bool',
        "adm_comm" => 'bool',
        "add_comm" => 'bool'
    );
    
    private $db;
    private $id;

    public function Group($id = false)
    {
        global $bd_names;

        $this->db = $bd_names['groups'];
        $this->id = (int) $id;
    }

    public function GetPermission($param)
    {
        if (!$this->id or !isset(self::$permissions[$param]))
            return -1;
        
        $line = getDB()->fetchRow("SELECT `$param` FROM `{$this->db}` WHERE `id`='" . $this->id . "'", false, 'num');

        if ($line) {

            $value = (int) $line[0];

            if (self::$permissions[$param] == 'bool'){
                $value = ($line[0]) ? true : false;
            }
            
            return $value;
        } else
            return -1;
    }

    public function GetAllPermissions()
    {
        $sql_names = '';        
        foreach (self::$permissions as $key => $value) {
             $sql_names .= ($sql_names) ? ",`$key`" : "`$key`";
        }
        
        $line = getDB()->fetchRow("SELECT $sql_names FROM `{$this->db}` WHERE `id`='" . $this->id . "'");
        return $line;
    }

    public function Exist()
    {
        if (!$this->id)
            return false;

        $line = getDB()->fetchRow("SELECT COUNT(*) FROM `{$this->db}` WHERE `id`='" . $this->id . "'", false, 'num');

        if ((int)$line[0])
            return true;

        $this->id = false;
        return false;
    }

    public function Create($name, &$permissions)
    {
        if ($this->id)
            return false;

        if (!$name or !TextBase::StringLen($name))
            return false;

        $line = getDB()->fetchRow("SELECT COUNT(*) FROM `{$this->db}` "
                                . "WHERE `name`=:name", array('name' => $name), 'num');
        
        if ((int)$line[0])
            return false;

        $sqlNames = '`name`,';
        $sqlVars = '?';
        $sqlData = array($name);
        
        foreach ($permissions as $key => $value) {

            if (!isset(self::$permissions[$key])) continue;

            if (self::$permissions[$key] == 'bool')
                $sqlData[] = ($value) ? 1 : 0;
            else
                $sqlData[] = (int) $value;

                $sqlNames .= ",`$key`";
                $sqlVars .= ",?";
        }

        $result = getDB()->ask("INSERT INTO `{$this->db}` ($sqlNames) "
                             . "VALUES ($sqlVars)", $sqlData);
        if ($result and $result->rowCount())
            $this->id = getDB()->lastInsertId();
        else
            return false;

        return true;
    }

    public function GetName()
    {
        $line = getDB()->fetchRow("SELECT `name` FROM `{$this->db}` "
                        . "WHERE `id`='" . $this->id . "'", false, 'num');

        if (!$line)
            return false;

        return $line[0];
    }

    public function IsSystem()
    {
        $line = getDB()->fetchRow("SELECT `system` FROM `{$this->db}` WHERE `id`='" . $this->id . "'", false, 'num');

        if (!$line)
            return false;

        return ((int)$line[0]) ? true : false;
    }

    public function Edit($name, &$permissions)
    {
        if (!$this->id)
            return false;
        if (!$name or !TextBase::StringLen($name))
            return false;

        $line = getDB()->fetchRow("SELECT COUNT(*) FROM `{$this->db}` "
        . "WHERE `name`=:name and `id`!='{$this->id}'", array('name' => $name), 'num');

        if ((int)$line[0])
            return false;

        $sql = '`name`=?';
        $sqlData = array($name);

        foreach (self::$permissions as $key => $value) {
            if ($value == 'bool')
                $sqlData[] = (isset($permissions[$key]) and $permissions[$key]) ? 1 : 0;
            elseif (isset($permissions[$key]))
                $sqlData[] = (int) $permissions[$key];
            else continue;

            $sql .= ",`$key`=?";
        }
        
        $result = getDB()->ask("UPDATE `{$this->db}` SET $sql WHERE `id`='{$this->id}'", $sqlData);
        if ($result and $result->rowCount())
            return true;

        return false;
    }

    public function Delete()
    {
        global $bd_names;

        if (!$this->id)
            return false;
        if ($this->IsSystem())
            return false;

        $result = getDB()->ask("SELECT `id` FROM `{$bd_names['users']}` "
                   . "WHERE `group`='" . $this->id . "'");
        
        if ($result) {
            
            while ($line = $result->fetch('num')) {

                $user_del = new User($line[0]);
                $user_del->Delete();
                unset($user_del);
            }
        }

        $result = getDB()->ask("DELETE FROM `{$this->db}` WHERE `id` = '" . $this->id . "' AND `system` = '0'");

        $this->id = false;
        if ($result and $result->rowCount())
            return true;

        return false;
    }
}

class GroupManager
{
    public static function GetList($selected)
    {
        global $bd_names;

        $result = getDB()->ask("SELECT `id`, `name` FROM `{$bd_names['groups']}` ORDER BY `name` DESC LIMIT 0,90");
        $group_list = '';

        while ($line = $result->fetch())
            $group_list .= '<option value="' . $line['id'] . '" ' . (($selected == $line['id']) ? 'selected' : '') . '>' . $line['name'] . '</option>';

        return $group_list;
    }

    public static function GetNameByID($id)
    {
        if (!$id or $id < 0)
            return 'Удаленный';

        $grp_item = new Group($id);
        $grp_name = $grp_item->GetName();

        if (!$grp_name)
            return 'Удаленный';
        else
            return $grp_name;
    }
}
