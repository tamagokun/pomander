<?php

//deploy
group('deploy', function() {

  desc("Setup application directory in environment.");
  task('setup','app', function($app) {
    info("deploy","setting up environment");
		$cmd = array(
			"umask {$app->env->umask}",
			"mkdir -p {$app->env->deploy_to}",
			"mkdir {$app->env->current_dir} {$app->env->releases_dir} {$app->env->shared_dir}",
			$app->env->scm->create()
		);
		run($cmd);
  });

  desc("Update code to latest changes.");
  task('update','app', function($app) {
    info("deploy","updating code");
		$app->env->release_dir = $app->env->deploy_to.$app->env->new_release();
		$cmd = array(
			"cd {$app->env->cache_dir}",
			$app->env->scm->update(),
			"cp -R {$app->env->shared_dir}cached_copy {$app->env->release_dir}",
			"ln -s {$app->env->releases_dir}`ls {$app->env->releases_dir} | sort -nr | head -1` {$app->env->current_dir}"
		);
		run($cmd);
  });

});
task('deploy','deploy:update');

desc("Rollback to the previous revision");
task('rollback', function($app) {
	if($app->env->release_dir)
	{
		$cmd = array(
			"rm -rf {$app->env->release_dir}",
			"ln -s {$app->env->releases_dir}`ls {$app->env->releases_dir} | sort -nr | head -1` {$app->env->current_dir}"
		);
		run($cmd);
	}
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
