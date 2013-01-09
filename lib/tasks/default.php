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
			$cmd[] = "mkdir -p {$app->env->releases_dir} {$app->env->shared_dir}";
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
		$app->can_rollback = true;
		run($cmd);
  });

	task('finalize', function($app) {
		if($app->env->releases === false) return;
		$releases = run("ls -1t {$app->env->releases_dir}", true);
		if(!count($releases)) return;
		run("ln -nfs {$app->env->releases_dir}/{$releases[0]} {$app->env->current_dir}");
		$app->env->finalized = true;
	});

	desc("First time deployment.");
	task('cold','deploy:setup','deploy:update','deploy:finalize');

});
task('deploy','deploy:update','deploy:finalize');

desc("Rollback to the previous release");
task('rollback','app', function($app) {

	$cmd = array();

	if($app->env->releases)
	{
		$releases = run("ls -1t {$app->env->releases_dir}", true);
		if(!count($releases)) return abort("rollback", "no releases to roll back to.");
		
		if($app->env->release_dir == $app->env->current_dir)
		{
			info("rollback", "pointing application to previous release.");
			$cmd[] = "ln -nfs {$app->env->releases_dir}/{$releases[1]} {$app->env->current_dir}";
		}else
		{
			info("rollback", "removing failed release.");
			$cmd[] = "rm -rf {$app->env->releases_dir}/{$releases[0]}";
			if($app->env->finalized)
			{
				info("rollback", "pointing application to last good release.");
				$cmd[] = "ln -nfs {$app->env->releases_dir}/{$releases[1]} {$app->env->current_dir}";
			}
		}
	}else
	{
		$revision = run("cat {$app->env->release_dir}/REVISION", true);
		if(!count($revision)) return abort("rollback", "no releases to roll back to.");

		$app->env->revision = $revision[0];
		$cmd[] = $app->scm->update();
	}

	if($app->env->merged)
	{
		//if we don't have a backup file
			// warn that we can't do a rollback

		//restore newest backup
	}

	run($cmd);
		
});

//local
desc("Create development environment configuration");
task('config', function($app) {
  if( file_exists("./deploy/development.php"))
    warn("development.php","Already exists, skipping");
  else
	{
    if( copy($app->dir."/generators/config.php","./deploy/development.php") )
      info("config","Created deploy/development.php");
    else
      warn("config","Unable to create deploy/development.php");
  }
});

desc("Set it up");
task('init', function($app) {
	info("init","Creating deploy directory");
	exec_cmd("mkdir -p ./deploy");
	info("init","Creating development configuration");
	$app->invoke("config");
	info("init","Done!");
});
?>
