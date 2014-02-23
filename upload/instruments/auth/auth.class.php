<?php

/**
 * Authentication tools
 */

class AuthCore
{
    private static $driver = null;
    private static $encoder = null;

    public static function setDriver($driver, $encoder) 
    {
        loadTool($driver . 'Auth.class.php', 'auth/');

        $class = ucfirst($driver) . 'Auth';
        self::$driver = new $class();

        loadTool($encoder . 'Encoder.class.php', 'auth/encoder/');
        $class = ucfirst($encoder) . 'Encoder';

        self::$encoder = new $class();
    }
    
    /**
     * @return AuthInterface
     */
    
    public static function getLoader()
    {        
        return self::$driver;
    }
    
    /**
     * @return EncoderInterface
     */
    
    public static function getEncoder()
    {
        return self::$encoder;
    }
}

interface AuthInterface
{
    /**
     * Initialaize user object of connected client if he authored
     * @return User|null Return <b>null</b> if user is not authored
     */
    
    public function userLoad();
    
    /**
     * Calls after user logout
     */
    
    public function onUserLogout($user);
    
    /**
     * Calls after user login
     */
    
    public function onUserLogin($user);
    
    /**
     * Calls after user create
     */
    
    public function onUserCreate($user);
}

abstract class AuthAbstract 
{    
    public function userLoad()
    {
        global $bd_users;

        $user = null;
        $check_ip = GetRealIp();
        $session = Filter::input('session_id', 'get');

        if (!session_id() and !empty($session) and preg_match('/^[a-zA-Z0-9]{26,40}$/', $session))
            session_id($session);

        if (!isset($_SESSION))
            session_start();

        if (isset($_SESSION['user_name']))
            $user = new User($_SESSION['user_name'], $bd_users['login']);

        if (isset($_COOKIE['webMCRCookie']) and empty($user)) {

            $user = new User($_COOKIE['webMCRCookie'], $bd_users['tmp']);
            if ($user->id()) {

                $_SESSION['user_name'] = $user->name();
                $_SESSION['ip'] = $check_ip;
            }
        }

        if (!empty($user)) {

            if ((!$user->id()) or
                    ($user->lvl() <= 0) or
                    ($check_ip != $user->ip())
            ) {

                if ($user->id())
                    $user->logout();

                setcookie("webMCRCookie", "", time(), '/');
                $user = null;
            }
        }

        return $user;
    }

    public function onUserLogin($user)
    {
        return true;
    }

    public function onUserLogout($user)
    {
        return true;
    }
    
    public function onUserCreate($user) 
    {
        return true;
    }
}

interface EncoderInterface
{
    /**
     * Create hash-password from string
     * input array should contain keys:<br>
     * @param string $password password<br>
     * @param User $user User instance
     * @return string|bool Return <b>false</b> if hash generation not supported
     */
    
    public function createPass($password, $user);

    /**
     * Check password<br>
     * input array should contain keys:<br>
     * @param string $hash password hash<br>
     * @param string $password string that will be converted to hash to compare with input hash<br>
     * @param User $user User instance that associate with $password
     * @return bool
     */
    
    public function checkPass($hash, $password, $user);

    /**
     * Initialaize user object of connected client if he authored
     * @return User|null Return <b>null</b> if user is not authored
     */
    
    public function isCheckOnly();
}

abstract class EncoderAbstract 
{    
    protected $checkOnly = true;
    
    public function isCheckOnly() 
    {
        return $this->checkOnly;
    }    
    
    public function createPass($password, $user) 
    {
        return false;
    }
    
    public function checkPass($hash, $password, $user)
    {
        $pass = $this->createPass($password); 
        
        if ($pass and $hash == $pass)
            return true;
        else
            return false;
    }
}