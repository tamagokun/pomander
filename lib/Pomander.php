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
			if($directory == '/')
				throw new \Exception("No Pomfile found");
			$directory = dirname($directory);
		} while (true);
	}
}

//utils
function require_once_dir($dir)
{
	foreach(glob(POMANDER_PATH.$dir) as $file) require_once $file;
}

function info($status,$msg)
{
	puts(" * ".colorize("info ",32).colorize($status." ",35).$msg);
}

function warn($status,$msg)
{
	puts(" * ".colorize("warn ",31).colorize($status." ",35).$msg);
}

function colorize($text,$color)
{
	#31 red
	#32 green
	#35 purple
	return "\033[{$color}m{$text}\033[0m";
}

function puts($text) { echo $text."\n"; }

function home()
{
	if(!isset(builder()->get_application()->home))
		builder()->get_application()->home = trim(shell_exec("cd ~ && pwd"),"\r\n");
	return builder()->get_application()->home;
}

function run()
{
	$args = array();
	foreach( new RecursiveIteratorIterator(new RecursiveArrayIterator(func_get_args())) as $value)
		$args[] = $value;
	$cmd = implode(" && ",$args);
	if(!isset(builder()->get_application()->env))
		echo shell_exec($cmd);
	else
		echo builder()->get_application()->env->exec($cmd);
}

function put($what,$where)
{
	if(!isset(builder()->get_application()->env))
		return shell_exec("cp -r $what $where");
	builder()->get_application()->env->put($what,$where);
}

function get($what,$where)
{
	if(!isset(builder()->get_application()->env))
		return shell_exec("cp -r $what $where");
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
