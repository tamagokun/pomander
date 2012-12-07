<?php

//deploy
group('deploy', function() {

  desc("Setup application in environment.");
  task('setup','app', function($app) {
    info("deploy","setting up environment");
		$cmd = array(
			"umask {$app->env->umask}",
			"mkdir -p {$app->env->deploy_to}"
		);

		if($app->env->releases === false)
		{
			$cmd[] = "find {$app->env->deploy_to} -type f -delete";
			$cmd[] = $app->env->scm->create($app->env->deploy_to);
		}else
		{
			$cmd[] = "mkdir -p {$app->env->current_dir} {$app->env->releases_dir} {$app->env->shared_dir}";
			if($app->env->remote_cache === true) $cmd[] = $app->env->scm->create($app->env->cache_dir);
		}
		run($cmd);
  });

  desc("Update code to latest changes.");
  task('update','app', function($app) {
    info("deploy","updating code");
		$cmd = array();
		if($app->env->releases === false)
		{
			$cmd[] = "cd {$app->env->deploy_to}";
			$cmd[] = $app->env->scm->update();
		}else
		{
			$app->env->release_dir = $app->env->releases_dir.'/'.$app->env->new_release();
			if($app->env->remote_cache === true)
			{
				$cmd[] = "cd {$app->env->cache_dir}";
				$cmd[] = $app->env->scm->update();
				$cmd[] = "cp -R {$app->env->cache_dir} {$app->env->release_dir}";
			}else
			{
				$cmd[] = $app->env->scm->create($app->env->release_dir);
				$cmd[] = "cd {$app->env->release_dir}";
				$cmd[] = $app->env->scm->update();
			}
		}
		run($cmd);
  });

	task('finalize', function($app) {
		if($app->env->releases !== false)
			run("rm -rf {$app->env->current_dir}","ln -s {$app->env->releases_dir}/`ls {$app->env->releases_dir} | sort -nr | head -1` {$app->env->current_dir}");
	});

	desc("First time deployment.");
	task('cold','deploy:setup','deploy:update','deploy:finalize');

});
task('deploy','deploy:update','deploy:finalize');

desc("Rollback to the previous revision");
task('rollback', function($app) {
	if($app->env->release_dir && $app->env->releases !== false)
	{
		$cmd = array(
			"rm -rf {$app->env->release_dir}",
			"rm -rf {$app->env->current_dir}",
			"ln -s {$app->env->releases_dir}/`ls {$app->env->releases_dir} | sort -nr | head -1` {$app->env->current_dir}"
		);
		run($cmd);
	}
});

//local
desc("Create default development.yml for project");
task('config', function($app) {
  if( file_exists("./deploy/development.yml"))
    warn("development.yml","Already exists, skipping");
  else
	{
    if( copy($app->dir."/generators/config.yml","./deploy/development.yml") )
      info("config","Created deploy/development.yml");
    else
      warn("config","Unable to create deploy/development.yml");
  }
});
?>
