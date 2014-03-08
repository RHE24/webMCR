<?php
/**
 * Presets for configurate encoder drivers
 * Array key = POST key that implements what encode to use
 * If for cms available only one encoder, than list of encoders don't shows
 * and preset choose automaticaly
 * If one cms use two or more encoders you MUST set password column type for all used encoders
 */

$preset = array(
    'blowfish' => array(
        'name' => 'BlowfishEncoder v1.0',
        'column' => 'binary(38)',
        'cms' => array('usual')
    ),
    
    'md5Dle' => array(
        'name' => 'DLE',
        'column' => 'char(32)',
        'cms' => array('usual', 'dle')
    ),
    
    'md5' => array(
        'name' => 'webMCR Md5v1 (<= v2.4b)',
        'column' => 'char(32)',
        'cms' => array('usual')
    ),
    
    'md5Mcr' => array(
        'name' => 'webMCR Md5v2',
        'column' => 'binary(32)',
        'cms' => array('usual')
    ),
    
    // compatible with webMCR, but require set Column type    
    
    'authmeSha256' => array( 
        'name' => 'AuthMe SHA 256',
        'cms' => array('authme')
    ),
    
    // compatible with webMCR, but require set Column type
    
    'xauthWhirlpool' => array( 
        'name' => 'xAuth Whirlpool',
        'cms' => array('xauth')
    ),
    
    'xenforoApi' => array(
        'name' => 'Xenforo API Authenticate',
        'cms' => array('xenforo')
    ),
    
    'wpMd5' => array(
        'name' => 'WordPress Md5',
        'cms' => array('wp')
    ),
    
    'joomlaMd5' => array(
        'name' => 'Joomla! Md5',
        'cms' => array('joomla')
    ),
    
    'ipbMd5' => array(
        'name' => 'Invision Power Board Md5',
        'cms' => array('ipb')
    ),
);

/**
 * Default associations for $preset
 * key = cms post key
 */

$default = array(
    'usual' => 'md5Mcr',
    'ipb' => 'ipbMd5',
    'joomla' => 'joomlaMd5',
    'wp' => 'wpMd5',
    'xenforo' => 'xenforoApi',
    'xauth' => 'xauthWhirlpool',
    'authme' => 'authmeSha256',
    'dle' => 'md5Dle',
);
