<?php

require dirname(__FILE__).'/spyc.php';

class Pomander
{
	public static function resolve_runfile($directory)
	{
		$runfiles = array('Phakefile','Phakefile.php','Pomfile','Pomfile.php');
		do
		{
			foreach($runfiles as $r)
			{
				$candidate = $directory.'/'.$r;
				if(file_exists($candidate)) return $candidate;
			}
			if($directory == '/') return false;
			$directory = dirname($directory);
		} while (true);
	}
}

set_error_handler(function($errno,$errstr,$errfile,$errline) {
	puts("aborted!");
	puts("$errstr\n");
	if($errno <= 0) $errno = 1;
	global $trace;
	if($trace)
	{
		$exception = new \ErrorException($errstr, 0, $errno, $errfile, $errline);
		puts($exception->getTraceAsString());
	}else
	{
		puts("(See full trace by running task with --trace)");
	}
	exit($errno);
});

//utils
function info($status,$msg)
{
	puts(" * ".green("info ").purple("$status ").$msg);
}

function warn($status,$msg)
{
	puts(" * ".red("warn ").purple("$status ").$msg);
}

function abort($status, $msg, $code=1)
{
	warn($status,$msg);
	die($code);
}

function puts($text) { echo $text.PHP_EOL; }

function home()
{
	if(!isset(builder()->get_application()->home))
		builder()->get_application()->home = trim(shell_exec("cd && pwd"),"\r\n");
	return builder()->get_application()->home;
}

function run()
{
	$cmd = array();
	$silent = false;
	$args = func_get_args();
	if(is_bool($args[count($args)-1])) $silent = array_pop($args);
	foreach( new RecursiveIteratorIterator(new RecursiveArrayIterator($args)) as $value)
		$cmd[] = $value;
	$cmd = implode(" && ",$cmd);
	$app = builder()->get_application();

	list($status, $output) = !isset($app->env)? exec_cmd($cmd) : $app->env->exec($cmd);
	if(!$silent && count($output)) puts(implode("\n", $output));

	if($status > 0)
	{
		if($app->can_rollback)
		{
			warn("fail","Rolling back...");
			$app->invoke('rollback');
			info("rollback","rollback complete.");
			exit($status);
			return;
		}
		abort("fail","aborted!",$status);
	}
	return $output;
}

function exec_cmd($cmd)
{
	$cmd = is_array($cmd)? implode(" && ",$cmd) : $cmd;
	exec($cmd, $output, $status);
	return array($status, $output);	
}

function put($what,$where)
{
	if(!isset(builder()->get_application()->env))
		return exec_cmd("cp -R $what $where");
	builder()->get_application()->env->put($what,$where);
}

function get($what,$where)
{
	if(!isset(builder()->get_application()->env))
		return exec_cmd("cp -R $what $where");
	builder()->get_application()->env->get($what,$where);
}
