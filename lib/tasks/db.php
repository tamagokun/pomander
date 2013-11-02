<?php

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
