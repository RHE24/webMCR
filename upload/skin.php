<?php
header("Content-type: image/png");

require('./system.php');
execute();

$showMini = (Filter::input('mini', 'get', 'int') or Filter::input('m', 'get', 'bool')) ? true : false;
$showByName = Filter::input('user_name', 'get', 'string', true);
$isFemale = Filter::input('female', 'get', 'int', true);   
$userId = Filter::input('user_id', 'get', 'int');
if ($showMini and !$userId) $userId = Filter::input('mini', 'get', 'int');

if ($showByName or $userId or $isFemale !== false) {

    if ($userId) {
        DBinit('skin_viewer');
        loadTool('user.class.php');
        $tmp_user = new User($userId);
        if (!$tmp_user->id()) exit;
        if (!file_exists($tmp_user->getSkinFName())) $tmp_user->setDefaultSkin(); 
        $showByName = $tmp_user->name();
    } 
    
    ShowSkin($showMini, $showByName, $isFemale, $config['sbuffer']);
}

function ShowSkin($mini = false, $name = false, $isFemale = false, $saveBuffer = false)
{   
    loadTool('skin.class.php');
    
    if ($isFemale !== false) {
        $cloak = null;
        $skin = getWay('tmp') . 'defaultSkins/char' . (($isFemale) ? 'Female' : '') . '.png';
        $buffer = getWay('tmp') . 'skinBuffer/default/char' . ($mini ? 'Mini' : '') . ($isFemale ? 'Female' : '') . '.png';
    } elseif ($name) {
        $skin = getWay('skins') . $name . (($isFemale) ? 'Female' : '') . '.png';
        $cloak = getWay('cloaks') . $name . '.png';
        $buffer = getWay('tmp') . 'skinBuffer/' . $name. ($mini ? 'Mini' : '') . '.png';
    } else exit;
    
    if (file_exists($buffer)) {
        readfile($buffer);
        exit;
    } elseif ($saveBuffer)
        $image = ($mini) ? SkinViewer2D::saveHead($buffer, $skin) : SkinViewer2D::savePreview($buffer, $skin, $cloak);
    else
        $image = ($mini) ? SkinViewer2D::createHead($skin) : SkinViewer2D::createPreview($skin, $cloak);

    if ($image) imagepng($image);
}
