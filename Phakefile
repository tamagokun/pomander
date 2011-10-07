<?php
set_include_path('lib');
require_once('Deploy.php');
global $deploy;
$deploy = new Deploy();
if( file_exists($deploy->config_path) )
  $deploy->config($deploy->config_path);
else
  warn("config","unable to locate config.yml");

desc("test");
task("test","app", function() {
  info("test","yay");
});

//deploy
group('deploy', function() {
  
  task('setup','environment', function() {
    info("setup","running setup commands");
  });

  task('update','environment', function() {
    info("update","code update");
  });

  task('wordpress','environment', function() {
    
  });

  task('toolkit','environment', function() {
    
  });

  desc("Initial deploy");
  task('initial','app', function($app) {
    global $deploy;
    info("run","i'm running");
    

    $app->invoke('deploy:setup');
    $app->invoke('deploy:update');
  
  });
});
task('deploy','deploy:update');

?>
