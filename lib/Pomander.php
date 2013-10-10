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
	puts(" * ".ansicolor("info ",32).ansicolor("$status ",35).$msg);
}

function warn($status,$msg)
{
	puts(" * ".ansicolor("warn ",31).ansicolor("$status ",35).$msg);
}

function abort($status, $msg, $code=1)
{
	warn($status,$msg);
	die($code);
}

function ansicolor($text,$color)
{
	#31 red
	#32 green
	#35 purple
	return "\033[{$color}m{$text}\033[0m";
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
	array_walk_recursive($args, function($v) use(&$cmd) { $cmd[] = $v; });
	$cmd = implode(" && ",$cmd);
	$app = builder()->get_application();

	list($status, $output) = !isset($app->env)? run_local($cmd) : $app->env->exec($cmd);
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

function run_local($cmd)
{
	$cmd = is_array($cmd)? implode(" && ",$cmd) : $cmd;
	exec($cmd, $output, $status);
	return array($status, $output);
}

// Deprecated: use run_local()
function exec_cmd($cmd)
{
	run_local($cmd);
}

function put($what,$where)
{
	if(!isset(builder()->get_application()->env))
		return run_local("cp -R $what $where");
	builder()->get_application()->env->put($what,$where);
}

function get($what,$where)
{
	if(!isset(builder()->get_application()->env))
		return run_local("cp -R $what $where");
	builder()->get_application()->env->get($what,$where);
}
