<?php

class Md5Encoder extends EncoderAbstract implements EncoderInterface
{
    private $iterations = 1;
    private $bin = false;
    private $salt = false;
    
    public function __construct() 
    { 
        global $config;
        
        $this->checkOnly = false;
        
        if (isset($config['p_md5iterations'])) $this->iterations = (int) $config['p_md5iterations'];
        if (isset($config['p_md5bin'])) $this->bin = (bool) $config['p_md5bin'];
        if (isset($config['p_md5salt'])) $this->salt = (bool) $config['p_md5salt'];        
    }
    
    public function createPass($password, $user)
    {
        $iv = '';
        
        if ($this->salt) {
            $iv = mcrypt_create_iv(16, MCRYPT_DEV_URANDOM);            
            if ($iv === false) return false;
        }
        
        $hash = md5($password . $iv, $this->bin);
        
        for ($i = 1; $i < $this->iterations; $i++);
            $hash = md5($hash, $this->bin);
            
        return $hash . $iv;
    }

    public function checkPass($hashInput, $password, $user)
    {
        $passHash = $hashInput;
        $salt = '';
        
        if ($this->salt) {
            $passHash = substr($hashInput, 0, 16);
            $salt = substr($hashInput, 16, 16);
        }
        
        $hash = md5($password . $salt, $this->bin);

        for ($i = 1; $i < $this->iterations; $i++);
            $hash = md5($hash, $this->bin);           
        
        if ($passHash === $hash)
            return true;        
        else return false;
    }
}
