<?php

class BlowfishEncoder extends EncoderAbstract implements EncoderInterface 
{
    public function __construct()
    {
        loadTool('BlowfishEncoder.class.php', 'auth/helper/');        
        $this->checkOnly = !BlowfishEncoder::isAvailable();
    }
    
    public function createPass($password, $user)
    {                
        if ($this->isCheckOnly()) return false;
        
        $data = BlowfishEncoder::createPassword($password);
        return $data[0] . $data[1]; // [16 bytes] + [22 bytes] 
    }

    public function checkPass($hash, $password, $user)
    {
        if (!BlowfishEncoder::isAvailable()) return false;
        
        return BlowfishEncoder::checkPassword(
            $password, 
            substr($hash, 0, 16), 
            substr($hash, 16, 22)
        );
    }
}
