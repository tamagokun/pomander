<?php
set_include_path('lib');
require_once('Deploy.php');
global $deploy;
$deploy = new Deploy();
if( file_exists($deploy->config_path) )
  $deploy->config($deploy->config_path);
else
  warn("config","unable to locate config.yml");

?>
