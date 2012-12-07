<?php

if(!class_exists("\\Pomander"))
{
	try {
		$dir = dirname(dirname(__DIR__));
		require_once "{$dir}/autoload.php";
	}catch(Exception $e)
	{
		echo "Failed to load Pomander library. Is pom installed in the proper location?\n";
	}
}

// get the task list
$args = $GLOBALS['argv'];
array_shift($args);
$parser = new \Pomander\OptionParser($args);
$tasks = array();
foreach($parser->get_non_options() as $option)
	if(strpos($option, '=') === false) $tasks[] = $option;

// set our top level tasks and lib
$app = phake\Builder::$global->get_application();
$app->top_level_tasks = count($tasks)? $tasks : array('default');
$app->dir = __DIR__.'/lib';

// load Pomfile
$runfile = \Pomander::resolve_runfile(getcwd());
if(!$runfile) throw new \Exception("No Pomfile found");
phake\load_runfile($runfile);
