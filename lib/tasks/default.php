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
			$cmd[] = "rm -rf {$app->env->deploy_to}";
			$cmd[] = $app->env->scm->create($app->env->deploy_to);
		}else
		{
			$deployed = run("if test -d {$app->env->current_dir}; then echo \"exists\"; fi", true);
			if(count($deployed)) return abort("setup", "application has already been setup.");
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
				$frozen = run("if test -d {$app->env->cache_dir}; then echo \"ok\"; fi", true);
				if(empty($frozen)) return abort("deploy", "remote_cache folder not found. you should run deploy:setup or deploy:cold first.");
				$cmd[] = "cd {$app->env->cache_dir}";
				$cmd[] = $app->env->scm->update();
				$cmd[] = "cp -R {$app->env->cache_dir} {$app->env->release_dir}";
			}else
			{
				$frozen = run("if test -d {$app->env->releases_dir}; then echo \"ok\"; fi", true);
				if(empty($frozen)) return abort("deploy", "releases folder not found. you should run deploy:setup or deploy:cold first.");
				$cmd[] = $app->env->scm->create($app->env->release_dir);
				$cmd[] = "cd {$app->env->release_dir}";
				$cmd[] = $app->env->scm->update();
			}
		}
		run($cmd);
		$app->can_rollback = true;
	});

	task('finalize','deploy:cleanup', function($app) {
		//if($app->env->backup === false) $cmd[] = "rm -rf {$app->env->shared_dir}/backup/{$app->env->merged}";
		$deployed_to = basename($app->env->deploy_to);
		if($app->env->releases === true)
		{
			$deployed_to = basename($app->env->release_dir);
			run(array(
				"cd {$app->env->releases_dir}",
				"current=`ls -1t | head -n 1`",
				"ln -nfs {$app->env->releases_dir}/\$current {$app->env->current_dir}"
			));
		}
		$app->env->finalized = true;
		info("complete", "deployed $deployed_to");
	});

	task('cleanup', function($app) {
		if($app->env->releases === false) return;
		if($app->env->keep_releases === false) return;
		$keep = max(1, $app->env->keep_releases);

		info('deploy', "cleaning up old releases");
		run(array(
			"cd {$app->env->releases_dir}",
			"count=`ls -1t | wc -l`",
			"old=$((count > {$keep} ? count - {$keep} : 0))",
			"ls -1t | tail -n \$old | xargs rm -rf {}"
		));
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
			$count = isset($app['releases'])? $app['releases'] : 1;
			if(count($releases) < $count + 1) return abort("rollback", "can't rollback that far.");
			if($count > 1) info("rollback", "rolling back to {$releases[$count]}.");
			info("rollback", "pointing application to previous release.");
			$cmd[] = "ln -nfs {$app->env->releases_dir}/{$releases[$count]} {$app->env->current_dir}";
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

	desc("Wipe database.");
	task('destroy','db', function($app) {
		warn("destroy","database {$app->env->database["name"]}");
		run($app->env->adapter->destroy());
	});

	desc("Perform a backup suited for merging.");
	task('backup','db', function($app) {
		info("backup","database {$app->env->database["name"]}");
		run($app->env->adapter->dump($app->env->shared_dir."/dump.sql",$app->env->db_backup_flags));
		get("{$app->env->shared_dir}/dump.sql","./tmpdump.sql");
		$app->old_url = $app->env->url;
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
		if(isset($app->env->backup) && $app->env->backup) $app->invoke("db:full");
		put("./tmpdump.sql",$file);
		run($app->env->adapter->merge($file),"rm -rf $file");
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
	{
		warn("development.php","Already exists, skipping");
		return;
	}
	if( copy($app->dir."/generators/config.php","./deploy/development.php") )
		info("config","Created deploy/development.php");
	else
		warn("config","Unable to create deploy/development.php");
});

desc("Set it up");
task('init', function($app) {
	info("init","Creating deploy directory");
	exec_cmd("mkdir -p ./deploy");
	info("init","Creating development configuration");
	$app->invoke("config");
	info("init","Done!");
	puts("    Modify deploy/development.php to get started.");
	puts("    Add other environments before running pom deploy:setup");
	puts("    Check out http://ripeworks.com/pomander for more information");
});
?>
