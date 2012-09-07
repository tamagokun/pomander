<?php

//deploy
group('deploy', function() {

  desc("Setup application directory in environment.");
  task('setup','app', function($app) {
    info("deploy","setting up environment");
    run("umask {$app->env->umask}","mkdir -p {$app->env->deploy_to}","rm -rf {$app->env->deploy_to}/*",$app->env->scm->create());
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
  {
    if( copy(POMANDER_PATH."pomander/generators/config.yml","./deploy/development.yml") )
      info("config","Created deploy/development.yml");
    else
      warn("config","Unable to create deploy/development.yml");
  }
});
?>
