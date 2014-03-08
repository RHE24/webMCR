<?php

/**
 * Authorization throw remote account server, using configuration from 'configRemote.php'
 * methods 'authenticate' and 'create' calls when webMCR actual method calls to
 * If enabled
 * authenticate method calls when webMCR user try to login and if it fails, user cant auth in webMCR
 * create method calls when webMCR user is created, remote user will be created too.
 * 
 * @todo at current moment NOT tested and may be will be finished in future
 */

class RemoteAuth
{
    private $preset = null;
    private $link = null;
    private $encoder = null;
    
    public static function saveConfig($preset, $way = false) 
    {
        $txt .= '$configRemote = ' . var_export($preset, true) . ';' . PHP_EOL;
        if (!$way) $way = getWay('system') . 'configRemote.php';
        
        if (file_put_contents(getWay('system') . 'config.php', $txt) === false)
            return false;   

        return true;
    }
    
    public function __construct($presetFile = false) 
    {
        global $config;
        
        if (!$presetFile)
        $presetFile = getWay('system') . 'configRemote.php';
        
        if (!file_exists($presetFile)) return;
        
        include $presetFile;
        
        $this->preset = $configRemote; 

        loadTool($this->preset['encoder'] . 'Encoder.class.php', 'auth/encoder/');
        
        $class = ucfirst($this->preset['encoder']) . 'Encoder';
        $this->encoder = new $class($this->preset['encoderConfig']);
  
        $class = $config['db_driver'] . 'Driver';
        $this->link = new $class();
              
        try {
            $this->link->connect($this->preset['connect']);    
        } catch (Exception $e) {
            return $e->getMessage();
        }        
    }
    
    public function authenticate($password, $user) 
    {
        $sql = "SELECT `{$this->preset['table']['password']}`,`{$this->preset['table']['id']}` FROM `{$this->preset['tableName']}` "
             . "WHERE `{$this->preset['table']['login']}`='" . $login . "'";
                
        $line = getDB()->fetchRow($sql, false, 'num');
        return ($this->encoder->checkPass($line[0], $password, $user)) ? true : false;
    }
    
    public function сreate($password, $user) 
    {       
        $sqlCol = ''; $sqlVar = '';
        if ($this->preset['table']['ip']) {
            $sqlCol .= "`{$this->preset['table']['ip']}`,";
            $sqlVar .= ",'" . GetRealIp() . "'";
        }
        
        if ($this->preset['table']['createDate']) {
            $sqlCol .= "`{$this->preset['table']['createDate']}`,";
            $sqlVar .= ", NOW()";
        }    
        
        $password = $this->encoder->createPass($password, $user);        
        if ($password === false) return false;
        
        $result = $this->link->ask("INSERT INTO `{$this->preset['tableName']}` ("
                . "`{$this->preset['table']['login']}`,"
                . "`{$this->preset['table']['password']}`,"
                . $sqlCol 
                . "VALUES('$login',:password".$sqlVar.")", array('password' => $password));
                
        if (!$result) return false;
        return true;
    }
}
