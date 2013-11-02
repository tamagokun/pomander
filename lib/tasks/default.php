<?php

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
	run_local("mkdir -p ./deploy");
	info("init","Creating development configuration");
	$app->invoke("config");
	info("init","Done!");
	puts("    Modify deploy/development.php to get started.");
	puts("    Add other environments before running pom deploy:setup");
	puts("    Check out http://ripeworks.com/pomander for more information");
});
?>
