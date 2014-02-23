<?php
/**
 * Presets for configurate encoder drivers
 * Array key = POST key that implements what encode to use
 * If for cms available only one encoder, than list of encoders don't shows
 * and preset choose automaticaly
 * If one cms use two or more encoders you MUST set password column type for all used encoders
 */

$preset = array(
    'BlowfishV1' => array(
        'name' => 'BlowfishEncoder v1.0',
        'column' => 'binary(38)',
        'config' => array(
            'p_encode' => 'Blowfish',
        ),
        'cms' => array('usual')
    ),
    'DLE' => array(
        'name' => 'DLE',
        'column' => 'char(32)',
        'config' => array(
            'p_encode' => 'Md5',
            'p_md5bin' => false,
            'p_md5salt' => false,
            'p_md5iterations' => 2,
        ),
        'cms' => array('usual', 'dle')
    ),
    'Md5V1' => array(
        'name' => 'webMCR Md5v1 (<= v2.4b)',
        'column' => 'char(32)',
        'config' => array(
            'p_encode' => 'Md5',
            'p_md5bin' => false,
            'p_md5salt' => false,
            'p_md5iterations' => 1,
        ),
        'cms' => array('usual')
    ),
    'Md5V2' => array(
        'name' => 'webMCR Md5v2',
        'column' => 'binary(32)',
        'config' => array(
            'p_encode' => 'Md5',
            'p_md5bin' => true,
            'p_md5salt' => true,
            'p_md5iterations' => 256,
        ),
        'cms' => array('usual')
    ),
    
    // compatible with webMCR, but require set Column type    
    
    'AuthMeSha256' => array( 
        'name' => 'AuthMe SHA 256',
        'config' => array(
            'p_encode' => 'authmeSha256',
        ),
        'cms' => array('authme')
    ),
    
    // compatible with webMCR, but require set Column type
    
    'xAuthWhirlpool' => array( 
        'name' => 'xAuth Whirlpool',
        'config' => array(
            'p_encode' => 'xauthWhirlpool',
        ),
        'cms' => array('xauth')
    ),
    'xenForoApi' => array(
        'name' => 'Xenforo API Authenticate',
        'config' => array(
            'p_encode' => 'xenforoApi',
        ),
        'cms' => array('xenforo')
    ),
    'WpMd5' => array(
        'name' => 'WordPress Md5',
        'config' => array(
            'p_encode' => 'wpMd5',
        ),
        'cms' => array('wp')
    ),
    'JoomlaMd5' => array(
        'name' => 'Joomla! Md5',
        'config' => array(
            'p_encode' => 'joomlaMd5',
        ),
        'cms' => array('joomla')
    ),
    'IpbMd5' => array(
        'name' => 'Invision Power Board Md5',
        'config' => array(
            'p_encode' => 'ipbMd5',
        ),
        'cms' => array('ipb')
    ),
);

/**
 * Default associations for $preset
 * key = cms post key
 */

$default = array(
    'usual' => 'Md5V2',
    'ipb' => 'IpbMd5',
    'joomla' => 'JoomlaMd5',
    'wp' => 'WpMd5',
    'xenforo' => 'xenForoApi',
    'xauth' => 'xauthWhirlpool',
    'authme' => 'AuthMeSha256',
    'dle' => 'DLE',
);
