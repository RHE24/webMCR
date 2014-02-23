<?php

class IpbEncoder extends EncoderAbstract implements EncoderInterface
{
    public function __construct()
    {
        $this->checkOnly = true;        
        parent::__construct();
    }

    public function checkPass($hash, $password, $user)
    {
        global $bd_names, $bd_users;

        $sql = "SELECT `{$bd_users['salt_pwd']}` FROM `{$bd_names['users']}` "
                . "WHERE `{$bd_users['id']}`=:id";

        $result = getDB()->fetchRow($sql, array('id' => $user->id()), 'num');
        if (!$result)
            return false;

        if ($hash == md5(md5($result[0]) . md5($password)))
            return true;
        else
            return false;
    }
}
