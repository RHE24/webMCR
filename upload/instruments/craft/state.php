<?php
require('../../system.php');
execute();

loadTool('ajax.php');
loadTool('server.class.php', 'craft/monitoring/');

$id = Filter::input('id', 'post', 'int', true) or exit;

DBinit('monitoring');

$server = new Server($id, 'serverstate/');
$server->UpdateState();
$server->ShowInfo();
