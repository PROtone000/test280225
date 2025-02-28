<?php
/**
 * Точка входа в приложение
 */
require '../application/settings.php';
set_include_path(DIR_ROOT.'application');

require_once 'entry.php';

$listconfig = new Kda_Config_List();
$listconfig->setApplication($application);
$listconfig->setSettings($settings);
$listconfig->setDB($kda_db);
$entry = new Entry();
$entry->run();