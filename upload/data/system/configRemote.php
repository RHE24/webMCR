<?php
/**
 * Options for remote authorization server
 * @todo At current moment disabled by default and NOT tested
 */

$configRemote = array(
    'encoder' => 'xauth',
    'encoderConfig' => false, // @todo remove encoder configs, use extend method for exist encoders
    
    'tableName' => 'xauth',
    'tableColumns' => array(
        'id' => 'id',
        'login' => 'playername',
        'password' => 'password',   
        'ip' => false,        
        'createDate' => 'registerdate',
    ),
    
    'connect' => array(
        'default' => false,
        'host' => 'localhost',
        'port' => 3306,
        'login' => 'root', 
        'password' => '', 
        'db' => 'xauth',
    ),
);

