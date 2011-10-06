<?php
set_include_path('lib');
require_once('Deploy.php');
global $deploy;
$deploy = new Deploy();
if( file_exists($deploy->config_path) )
  $deploy->config($deploy->config_path);
else
  warn("config","unable to locate config.yml");

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

  task('initial','app', function($app) {
    global $deploy;
    //foreach( $deploy->env->role["app"] as $target ):
    //  $deploy->env->connect($target);
    info("run","i'm running");
    

    $app->invoke('deploy:setup');
    $app->invoke('deploy:update');
  
    //endforeach;
    $app->reset();
    $app->invoke('deploy:initial');
  });
});
task('deploy','deploy:update');


task('app','environment',function($app) {
  global $deploy;
  if( $deploy->env->role("app") )
    $deploy->env->connect();
  else
    return false;
});

function app_task()
{
    
}

?>
