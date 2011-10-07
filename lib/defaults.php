<?php

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

    $app->invoke('deploy:setup');
    $app->invoke('deploy:update');
  
  });
});
task('deploy','deploy:update');

?>
