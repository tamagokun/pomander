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

});
task('deploy','deploy:update');
task('deployed','app',function($app) {
  info("deployed","checking the current deployed revision");
  run("cd {$app->env->deploy_to}",$app->env->scm->revision());
});

//local
desc("Create default deployment config.yml for project");
task('config', function($app) {
  if( file_exists("./deploy/development.yml"))
    warn("development.yml","Already exists, skipping");
  else
    copy(PENKAI_PATH."/lib/penkai/Template/config.yml","./deploy/development.yml");
});

//build
task('_build', function($app) {
  
  $paths[] = "lib/*";
  $files = array();

  while(count($paths) != 0)
  {
    $i = array_shift($paths);
    foreach(glob($i) as $file)
    {
      if(is_dir($file))
        $paths[] = $file . '/*';
      else
        $files[] = $file;
    }
  }
  
  var_dump($files);
});

task('update', function($app) {
  $dir = dirname(__FILE__)."/../../";
  info("update","updating penkai to latest version");
  echo shell_exec("cd $dir && git pull");
  info("update","all done!");
});

?>
