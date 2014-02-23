<?php

class XenforoEncoder extends EncoderAbstract implements EncoderInterface
{    
    private $xfClass = false;
    
    public function __construct()
    {       
        if (class_exists('XenForo_Authentication_Core12'))
            $this->xfClass = 'XenForo_Authentication_Core12'; // ver 1.2.0
        elseif (class_exists('XenForo_Authentication_Core'))
            $this->xfClass = 'XenForo_Authentication_Core'; // ver 1.1.4 or lower  
        else exit('[MCMS] Версия xenForo не поддерживается драйвером.');  
    }
    
    public function checkPass($hash, $password, $user)
    {
        global $bd_names, $bd_users;
        
        $result = getDB()->fetchRow("SELECT `data` FROM `{$bd_names['user_auth']}` "
                . "WHERE `{$bd_users['id']}`=:id", array('id' => $user->id()), 'num');

        if (!$result) return false;       
        
        $auth = new $this->xfClass;
        $auth->setData($result[0]);

        if ($auth->authenticate($user->id(), $password))
            return true;
        else
            return false;
    }
}
