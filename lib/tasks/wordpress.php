<?php

group('deploy',function() {
  
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

  task('all','app','deploy:setup','deploy:update','deploy:wordpress','deploy:toolkit','wp_config','htaccess');

  desc("Complete Wordpress deployment stack (1 and done)");
  task('initial','deploy:all','db:create');

});

//wordpress uploads
group('uploads', function() {
  desc("Download uploads from environment");
  task('pull','app', function($app) {
    info("uploads","backing up environment uploads");
    get("{$app->env->deploy_to}/public/uploads","./public/uploads");
  });

  desc("Place all local uploads into environment");
  task('push','app', function($app) {
    info("uploads","deploying");
    put("./public/uploads","{$app->env->deploy_to}/public/uploads");
  });
});

//wordpress
desc("Create and deploy wp-config.php for environment");
task('wp_config','app', function($app) {
  info("config","creating wp-config.php");
  file_put_contents("./tmp-wp-config",template("lib/penkai/Template/wp-config.php"));
  put("./tmp-wp-config","{$app->env->deploy_to}/wp-config.php");
  unlink("./tmp-wp-config");
});

desc("Create and deploy .htaccess for environments");
task('htaccess','app', function($app) {
  info("htaccess","creating .htaccess");
  file_put_contents("./tmp-htaccess",template("lib/penkai/Template/htaccess.php"));
  put("./tmp-htaccess","{$app->env->deploy_to}/wordpress/.htaccess");
  unlink("./tmp-htaccess");
});

desc("Wordpress task stack for local machine (1 and done)");
task('wpify','environment','config','deploy:wordpress','toolkit','db:create','wp_config', function($app) {
  info("wpify","success");
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
