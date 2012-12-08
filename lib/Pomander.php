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
	global $trace;
	if($trace)
	{
		$exception = new \ErrorException($errstr, 0, $errno, $errfile, $errline);
		puts($exception->getTraceAsString());
	}else
	{
		puts("(See full trace by running task with --trace");
	}
	exit($errno);
});

//utils
function require_once_dir($dir)
{
	foreach(glob(builder()->get_application()->root.'/'.$dir) as $file) require_once $file;
}

function info($status,$msg)
{
	puts(" * ".colorize("info ",32).colorize($status." ",35).$msg);
}

function warn($status,$msg)
{
	puts(" * ".colorize("warn ",31).colorize($status." ",35).$msg);
}

function abort($status, $msg, $code=1)
{
	warn($status,$msg);
	die($code);
}

function colorize($text,$color)
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
	$args = array();
	foreach( new RecursiveIteratorIterator(new RecursiveArrayIterator(func_get_args())) as $value)
		$args[] = $value;
	$cmd = implode(" && ",$args);
	if(!isset(builder()->get_application()->env))
		echo exec_cmd($cmd);
	else
		echo builder()->get_application()->env->exec($cmd);
}

function exec_cmd($cmd)
{
	$cmd = is_array($cmd)? implode(" && ",$cmd) : $cmd;
	exec($cmd,$out,$status);
	$app = builder()->get_application();
	if($status > 0 && $app->resolve('deploy:update')->resolve(array('deploy:update'))->has_run) {
		warn("fail","Deployment failed. Rolling back...");
		$app->invoke('rollback');
		abort("complete","Rolled back.");
	}
	return implode("\n",$out);
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

//To Deprecate
function copy_r( $path, $dest )
{
  if( is_dir($path) )
  {
    @mkdir( $dest );
    $objects = scandir($path);
    if( sizeof($objects) > 0 )
    {
      foreach( $objects as $file )
      {
        if( $file == "." || $file == ".." )
          continue;
        if( is_dir( "$path/$file" ) )
          copy_r( "$path/$file", "$dest/$file" );
        else
          copy( "$path/$file", "$dest/$file" );
      }
    }
    return true;
  }
  elseif( is_file($path) )
    return copy($path, $dest);
  else
    return false;
}
