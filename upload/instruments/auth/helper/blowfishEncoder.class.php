<?php

/**
 * @category  Blowfish Password Encode Tool
 * @package   Kelly\Auth
 * @author    Rubchuk Vladimir <torrenttvi@gmail.com>
 * @copyright 2013-2014 Rubchuk Vladimir
 * @version   1.0
 * @license   GPLv3
 * 
 * example: 
 *     $data = BlowfishEncoder::createPassword('test');
 *     if (BlowfishEncoder::checkPassword('test', $data[0], $data[1])) exit('ok');
 */

class BlowfishEncoder
{
    /*
    * also available $2a$ cost
    * in version 5.3.7 added $2x$ and $2y$ Blowfish modes to deal with potential high-bit attacks. so $2y$ is recomended 
    */
    
    private static $cost = '$2y$';
    
    /*
    * Num of iterations
    * 09 - num of iterations (2^9) ~ 0.10 sec
    */
    
    private static $iterations = '09'; 
    
    /*
    * Num of md5 iterations for password before blowfish encode in createPassword method
    * May be helpfull if you want convert exist md5 raw data to blowfish hash, 
    * set for ex. zero if password was already hashed before by md5(password), after conversion set back to work value
    */
    
    private static $md5Iterations = 1; 
    
    /**
     * Create hash binary data from password string.
     * @param string $password input password string or md5*N hash as a 16-byte raw binary data of a real password (used for convert old password hash)
     * @return array First element of array - password hash as raw binary data length of 16,<br> 
     * second - generated salt, with 22 alphabet ./A-Za-z0-9 characters
     */
    
    public static function createPassword($password)
    {
        /* Generate the salt random bytes. Because base64 returns one character 
         * for each 6 bits, the we should generate at least 22*6/8=16.5 bytes, 
         * so generate 17. Then we get the first 22 base64 characters
         */

        $salt = substr(base64_encode(mcrypt_create_iv(17, MCRYPT_DEV_URANDOM)), 0, 22);

        /* In order blowfish standart salt must be only with the alphabet ./A-Za-z0-9 
         * So replace '+' with '. Char '=' occurs when the b64 string is padded, which 
         * is always after the first 22 characters.
         */

        $salt = str_replace('+', '.', $salt);
        
        // addition for result salt
        
        $spoonOfsugar = '';
        for($i = 0; $i < 22; $i+=2) $spoonOfsugar .= ~ $salt[$i];
        
        $raw = md5(self::md5Iteration($password, self::$md5Iterations) . md5($spoonOfsugar, true), true);
        $resultSalt = $salt . $spoonOfsugar;
                
        /*
         * Do actually blowfish "slowpoke" hashing where $09 - num of interations (2^9) ~ 0.10 sec
         */

        $hash = crypt($raw, self::$cost . self::$iterations . '$' . $salt);
        
        return array(
            self::md5Combine($resultSalt, $hash),
            $salt
        );
    }
    
    /**
     * Compare input password with password hash
     * @param string $password input password string
     * @param string $encPass password hash as raw binary data
     * @param string $salt
     * @return boolean
     */
    
    public static function checkPassword($password, $encPass, $salt)
    {
        $spoonOfsugar = '';
        for($i = 0; $i < 22; $i+=2) $spoonOfsugar .= ~ $salt[$i];
        
        $raw = md5(self::md5Iteration($password, self::$md5Iterations) . md5($spoonOfsugar, true), true);
        $hash = crypt($raw, self::$cost . self::$iterations . '$' . $salt);
        
        $salt = $salt . $spoonOfsugar;
        
        if ($encPass === self::md5Combine($salt, $hash)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Check is Blowfish module enabled
     * @return boolean
     */
    
    public static function isAvailable()
    {
        if (version_compare(PHP_VERSION, '5.3.7') < 0 and self::$cost !== '$2a' ){
            return false;
        }
        
        if (CRYPT_BLOWFISH !== 1){
            return false;
        }

        return true;
    }
    
    private static function md5Combine($str, $str2)
    {
        return md5(md5($str, true) . md5($str2, true), true);
    }
    
    private static function md5Iteration($str, $iterations = 1)
    {
        if (!$iterations) return $str;
        
        $hash = md5($str, true);
        
        for ($i = 1; $i < $iterations; $i++);
        $hash = md5($hash, true);
        
        return $hash;
    }
}
