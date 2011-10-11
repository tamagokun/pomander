<?php
set_include_path('lib');
require_once('deploy.php');
if( file_exists(builder()->get_application()->config_path) )
  config();
else
  warn("config","unable to locate config.yml");
?>
