<?php

//deploy
group('deploy', function() {

  desc("Setup application directory in environment.");
  task('setup','app', function($app) {
    info("deploy","setting up environment");
    run("umask {$app->env->umask}","mkdir -p {$app->env->deploy_to}","find {$app->env->deploy_to} -type f -delete",$app->env->scm->create());
  });

  desc("Update code to latest changes.");
  task('update','app', function($app) {
    info("deploy","updating code");
    run("cd {$app->env->deploy_to}",$app->env->scm->last_revision(),$app->env->scm->update());
  });

});
task('deploy','deploy:update');
task('deployed','app',function($app) {
  info("deployed","checking the current deployed revision");
  run("cd {$app->env->deploy_to}",$app->env->scm->revision());
});

desc("Rollback to the previous revision");
task('rollback', function($app) {
	if($app->resolve('update','deploy')->has_run || in_array('rollback',$app->top_level_tasks))
		run("cd {$app->env->deploy_to}",$app->env->scm->rollback());	
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
