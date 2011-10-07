<?php

//deploy
group('deploy', function() {
  
  desc("Setup application directory in environment.");
  task('setup','app', function() {
    global $deploy;
    info("deploy","setting up environment");
    run("umask 002","rm -rf {$deploy->env->deploy_to}",$deploy->env->scm->create());
  });
  
  desc("Update code to latest changes.");
  task('update','app', function($app) {
    global $deploy;
    info("deploy","updating code");
    run("cd {$deploy->env->deploy_to}",$deploy->env->scm->update());
  });

  desc("Deploy Wordpress in environment.");
  task('wordpress','app', function() {
    global $deploy;
    info("fetch","Wordpress {$deploy->env->wordpress["version"]}");
    $cmd = array(
      "svn export http://svn.automattic.com/wordpress/tags/{$deploy->env->wordpress["version"]} {$deploy->env->deploy_to}/wordpress --force --quiet",
      "rm -rf {$deploy->env->deploy_to}/wordpress/public",
      "ls -s {$deploy->env->deploy_to}/public {$deploy->env->deploy_to}/wordpress/public"
    );
    run($cmd);
  });

  desc("Deploy MSL Toolkit in environment.");
  task('toolkit','app', function() {
    
  });

  desc("Complete deployment stack (1 and done)");
  task('initial','app','deploy:setup','deploy:update','deploy:wordpress','deploy:toolkit');
});
task('deploy','deploy:update');

//db
group('db', function() {
  task('backup','db', function($app) {
    
  });

  task('merge','db', function($app) {
    
  });
});

//wordpress uploads
group('uploads', function() {
  desc("Download uploads from environment")
  task('pull','app', function($app) {
    
  });

  desc("Place all local uploads into environment")
  task('push','app', function($app) {
    
  });
});

//wordpress
desc("Create and deploy wp-config.php for environment")
task('wp_config','app', function($app) {
  
});

desc("Wordpress task stack for local machine (1 and done)")
task('wpify', function($app) {
  
});

//local
desc("Create default deployment config.yml for project")
task('config', function($app) {
  
});

?>
