<?php

class XauthEncoder extends EncoderAbstract implements EncoderInterface
{
    public function createPass($password)
    {
        $salt = substr(hash('whirlpool', uniqid(rand(), true)), 0, 12);
        $hash = hash('whirlpool', $salt . $password);
        $saltPos = (strlen($password) >= strlen($hash)) ? strlen($hash) : strlen($password);

        return substr($hash, 0, $saltPos) . $salt . substr($hash, $saltPos);
    }
}
