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

?>
