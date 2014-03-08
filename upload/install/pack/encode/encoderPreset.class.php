<?php

class EncoderPreset
{
    private static $options = false;
    private static $encoder = false;
    
    private static $cms = false;
    private static $list;
    
    public static function init($cms, $encoderKey = false) 
    {
        include MCR_ROOT . 'install/pack/encode/presets.php';        
        self::$cms = $cms;
        
        if (array_key_exists(self::$cms, $default)){
            self::$encoder = $default[self::$cms];
        }     
                
        if ($encoderKey !== false and array_key_exists($encoderKey, $preset)) {
            self::$encoder = $encoderKey;
        }
        
        if (!self::$encoder) {
            exit('Fatal error: Encoder ' . $encoderKey . ' unexist');
        }
        
        self::$options = $preset[self::$encoder];
        
        if (array_search(self::$cms, self::$options['cms']) === false) {
            exit('Fatal error: Encoder ' . $encoderKey . ' not available for ' . self::$cms);
        } 
        
        foreach ($preset as $encoder => $data) {       
            if (array_search(self::$cms, $data['cms']) === false) unset($preset[$encoder]);
        }
        
        self::$list = $preset;
    }
    
    public static function getEncoderNum() 
    {
        return sizeof(self::$list);
    }
    
    public static function getEncoder() 
    {
        return self::$encoder;
    }
    
    public static function getOptions() 
    {
        return self::$options;
    }
    
    public static function getColName()
    {
        if (self::getEncoderNum() != 1 and empty(self::$options['column']))
            exit('Fatal error: Require set column type ' . self::$encoder);        
        elseif (empty(self::$options['column'])) return false;
        return self::$options['column'];
    }
    
    public static function showList($name = '', $class = '', $selected = false) 
    {
        $html = '';
        if (!$selected) $selected = self::$encoder;

        foreach (self::$list as $encoder => $data) {  
            $select = '';
            if ($selected and !strcmp($encoder, $selected)) $select = 'selected="selected"';
            $html .= '<option value="' . $encoder . '" ' . $select . '>' . $data['name'] . '</option>' . PHP_EOL;
        }
            
        return '<select name="' . $name . '" class="' . $class . '">' . $html . '</select>';
    }
}
