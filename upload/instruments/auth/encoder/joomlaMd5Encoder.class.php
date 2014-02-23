<?php

class JoomlaEncoder extends EncoderAbstract implements EncoderInterface
{
    public function checkPass($hash, $password, $user)
    {
        $cryptPass = false;
        $parts = explode(':', $hash);
        $salt = $parts[1];
        $cryptPass = md5($password . $salt) . ":" . $salt;

        if ($hash == $cryptPass)
            return true;
        else
            return false;
    }
}
