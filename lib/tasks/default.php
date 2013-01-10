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
			$deployed = run("ls {$app->env->deploy_to} | grep current", true);
			if(count($deployed)) return abort("setup", "application has already been deployed.");
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
			$cmd[] = "{$app->env->scm->revision()} > REVISION";
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
		$cmd = array();
		//if($app->env->backup === false) $cmd[] = "rm -rf {$app->env->shared_dir}/backup/{$app->env->merged}";		
		if($app->env->releases === true)
		{
			$releases = run("ls -1t {$app->env->releases_dir}", true);
			if(count($releases)) $cmd[] = "ln -nfs {$app->env->releases_dir}/{$releases[0]} {$app->env->current_dir}";
		}
		run($cmd);
		$app->env->finalized = true;
	});

	desc("First time deployment.");
	task('cold','deploy:setup','deploy:update','deploy:finalize');

});
task('deploy','deploy:update','deploy:finalize');

//rollback
desc("Rollback to the previous release");
task('rollback','app', function($app) {

	$cmd = array();

	if($app->env->releases)
	{
		$releases = run("ls -1t {$app->env->releases_dir}", true);
		if(count($releases) < 2) return abort("rollback", "no releases to roll back to.");
		
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

	//if($app->env->merged)
	//{
		//info("rollback", "restoring database to before merge.");
		//$backup = "{$app->env->shared_dir}/backup/{$app->env->merged}";
		//$cmd[] = $app->env->adapter->restore($backup);
		//if($app->env->backup === false) $cmd[] = "rm -rf $backup";
	//}

	run($cmd);

});

//db
group('db', function() {
	desc("Create database.");
	task('create','db', function($app) {
		info("create","database {$app->env->database["name"]}");
		run($app->env->adapter->create());
	});

	desc("Perform a backup suited for merging.");
	task('backup','db', function($app) {
		info("backup",$app->env->database["name"]);
		run($app->env->adapter->dump($app->env->shared_dir."/dump.sql",$app->env->db_backup_flags));
		info("fetch","{$app->env->shared_dir}/dump.sql");
		get("{$app->env->shared_dir}/dump.sql","./tmpdump.sql");
		$app->old_url = $app->env->url;
		info("clean","dump.sql");
		run("rm {$app->env->shared_dir}/dump.sql");
	});

	desc("Merge a backup into environment.");
	task('merge','db', function($app) {
		info("merge","database {$app->env->database["name"]}");
		$file = $app->env->shared_dir."/dump.sql";
		if(!file_exists("./tmpdump.sql"))
			warn("merge","i need a backup to merge with (tmpdump.sql). Try running db:backup first");
		if(isset($app->old_url) && $app->env->db_swap_url)
		{
			info("premerge","replace {$app->old_url} with {$app->env->url}");
			$handle = fopen("./tmpdump.sql", 'rb');
			$sql = fread($handle, filesize("./tmpdump.sql"));
			fclose($handle);
			$sql = preg_replace("|http://{$app->old_url}|", "http://{$app->env->url}", $sql);
			$handle = fopen("./tmpdump.sql", 'w');
			fwrite($handle, $sql);
			fclose($handle);
		}
		if(isset($app->env->backup) && $app->env->backup)
			$app->invoke("db:full");
		info("merge","dump.sql");
		put("./tmpdump.sql",$file);
		run($app->env->adapter->merge($file),"rm -rf $file");
		info("clean","tmpdump.sql");
		unlink("./tmpdump.sql");
	});

	desc("Perform a full database backup.");
	task('full','db', function($app) {
		$file = $app->env->database["name"]."_".@date('Ymd_His').".bak.sql.bz2";
		info("full backup",$file);
		$cmd = array(
			"umask 002",
			"mkdir -p {$app->env->shared_dir}/backup",
			$app->env->adapter->backup($app->env->shared_dir."/backup/".$file, "--add-drop-table")
		);
		run($cmd);
	});
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
