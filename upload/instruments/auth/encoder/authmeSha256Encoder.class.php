<?php

class AuthmeEncoder extends EncoderAbstract implements EncoderInterface
{
    public function createPass($password)
    {
        $salt = substr(hash('sha256', uniqid(rand())), 0, 16);
        $hash = hash('sha256', hash('sha256', $password . $salt));
        $rpass = ('$SHA$' . $salt . '$' . hash('sha256', hash('sha256', $password) . $salt));

        return $rpass;
    }

    public function checkPass($hash, $password, $user)
    {
        $tmp = explode('$', $hash);
        $result = false;
        if (hash('sha256', hash('sha256', $password) . $tmp[2]) == $tmp[3])
            $result = true;

        return $result;
    }
}
