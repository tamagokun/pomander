<?php

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
        // go on
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

function flatten($array)
{
  if(!$array) return false;
  $flattened = array();
  foreach( new RecursiveIteratorIterator(new RecursiveArrayIterator($array)) as $value)
    $flattened[] = $value;
  return $flattened;
}

function require_once_dir($dir)
{
  foreach(glob(get_include_path().DIRECTORY_SEPARATOR.$dir) as $file) require_once $file;
}

function template($file)
{
  set_include_path(PENKAI_PATH);
  return include($file);
}

function info($status,$msg)
{
  puts(" * ".colorize("info ",32).colorize($status." ",35).$msg);
}

function warn($status,$msg)
{
  puts(" * ".colorize("warn ",31).colorize($status." ",35).$msg);
}

function puts($text)
{
  echo $text."\n";  
}

function colorize($text,$color)
{
  #31 red
  #32 green
  #35 purple
  return "\033[{$color}m{$text}\033[0m";
}

function home()
{
  if(!isset(builder()->get_application()->home))
    builder()->get_application()->home = trim(shell_exec("cd ~ && pwd"),"\r\n");
  return builder()->get_application()->home;
}
?>
