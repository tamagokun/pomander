<?php

//deploy
group('deploy', function() {
  
  desc("Setup application directory in environment.");
  task('setup','app', function($app) {
    info("deploy","setting up environment");
    run("umask 002","rm -rf {$app->env->deploy_to}",$app->env->scm->create());
  });
  
  desc("Update code to latest changes.");
  task('update','app', function($app) {
    info("deploy","updating code");
    run("cd {$app->env->deploy_to}",$app->env->scm->update());
  });

  desc("Deploy Wordpress in environment.");
  task('wordpress','app', function($app) {
    info("fetch","Wordpress {$app->env->wordpress["version"]}");
    $cmd = array(
      "svn export http://svn.automattic.com/wordpress/tags/{$app->env->wordpress["version"]} {$app->env->deploy_to}/wordpress --force --quiet",
      "rm -rf {$app->env->deploy_to}/wordpress/public",
      "ln -s {$app->env->deploy_to}/public {$app->env->deploy_to}/wordpress/public",
      "mkdir -p {$app->env->deploy_to}/public/uploads",
      "touch {$app->env->deploy_to}/wordpress/.htaccess"
    );
    run($cmd);
  });

  desc("Deploy MSL Toolkit in environment.");
  task('toolkit',':toolkit','app', function($app) {
    info("deploy","injecting toolkit");
    put("./.toolkit/public/","{$app->env->deploy_to}/public/");
  });

  task('all','app','deploy:setup','deploy:update','deploy:wordpress','deploy:toolkit');

  desc("Complete deployment stack (1 and done)");
  task('initial','deploy:all','db:create');
});
task('deploy','deploy:update');
task('deployed','app',function($app) {
  info("deployed","checking the current deployed revision");
  run("cd {$app->env->deploy_to}",$app->env->scm->revision());
});

//db
group('db', function() {
  desc("Create database in environment if it doesn't already exist");
  task('create','db', function($app) {
    info("create","database {$app->env->wordpress["db"]}");
    //query("create database if not exists {$deploy->env->wordpress["db"]}", false);
    run("cd {$app->env->deploy_to}","./phake production db:create");
  });

  desc("Perform a backup of environment's database for use in merging");
  task('backup','db', function($app) {
    
  });

  desc("Merge a backed up database into environment");
  task('merge','db', function($app) {
    
  });
});

//wordpress uploads
group('uploads', function() {
  desc("Download uploads from environment");
  task('pull','app', function($app) {
    puts info("uploads","backing up environment uploads");
    get("{$app->env->deploy_to}/public/uploads","./public/uploads");
  });

  desc("Place all local uploads into environment");
  task('push','app', function($app) {
    puts info("uploads","deploying");
    put("./public/uploads","{$app->env->deploy_to}/public/uploads");
  });
});

//wordpress
desc("Create and deploy wp-config.php for environment");
task('wp_config','app', function($app) {
  info("config","creating wp-config.php");
  file_put_contents("./wp-config.php",include("Template/wp-config.php"));
});

desc("Wordpress task stack for local machine (1 and done)");
task('wpify','environment','config','deploy:wordpress','toolkit','db:create','wp_config', function($app) {
  
});

//local
desc("Create default deployment config.yml for project");
task('config', function($app) {
  if( file_exists("./config.yml"))
    warn("config.yml","Already exists, skipping");
  else
    put("lib/Template/config.yml","./config.yml");
});

desc("Update MSL toolkit");
task('toolkit',function($app) {
    info("git","updating toolkit");
    if( file_exists("./.toolkit") )
      shell_exec("cd ./.toolkit && git pull");
    else
      shell_exec("git clone cap@git.msltechdev.com:skeleton/toolkit.git ./.toolkit");
    info("toolkit","injecting to public/");
    if(!copy_r("./.toolkit/public","./public")) warn("copy","there was a problem injecting the toolkit");
});

?>
