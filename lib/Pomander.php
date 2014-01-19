<?php

use \phake\Builder;

require dirname(__FILE__).'/spyc.php';

// set default date
if(function_exists('date_default_timezone_set')) date_default_timezone_set('UTC');

class Pomander
{
    public static function version()
    {
        return array(0,4,0);
    }
}

// phake helpers
function builder()
{
    if(!isset(Builder::$global)) Builder::$global = new Builder;

    return Builder::$global;
}

function task()
{
    $deps = func_get_args();
    $name = array_shift($deps);
    if($deps[count($deps) - 1] instanceof Closure)
        $work = array_pop($deps);
    else
        $work = null;
    builder()->add_task($name, $work, $deps);
}

function group($name, $lambda = null)
{
    $thrown = null;
    builder()->push_group($name);
    try {
        if ($lambda instanceof Closure) $lambda();
    } catch (\Exception $e) {
        $thrown = $e;
    }
    builder()->pop_group();
    if ($thrown) throw $e;
}

function before($task, $lambda) { builder()->before($task, $lambda); }

function after($task, $lambda) { builder()->after($task, $lambda); }

function desc($description) { builder()->desc($description); }

//utils
function info($status,$msg)
{
    puts(" * ".ansicolor("info ",32).ansicolor("$status ",35).$msg);
}

function warn($status,$msg)
{
    puts(" * ".ansicolor("warn ",33).ansicolor("$status ",35).$msg);
}

function abort($status, $msg, $code=1)
{
    puts(" * ".ansicolor("abort ",31).ansicolor("$status ",35).$msg);
    die($code);
}

function ansicolor($text,$color)
{
    #31 red
    #32 green
    #33 yellow
    #35 purple

    return "\033[{$color}m{$text}\033[0m";
}

function puts($text) { echo $text.PHP_EOL; }

function home()
{
    $app = builder()->get_application();
    if(!isset($app->home))
        $app->home = trim(shell_exec("cd && pwd"),"\r\n");

    return $app->home;
}

function run()
{
    $cmd = array();
    $silent = false;
    $args = func_get_args();
    if(is_bool($args[count($args)-1])) $silent = array_pop($args);
    array_walk_recursive($args, function ($v) use (&$cmd) { $cmd[] = $v; });
    $cmd = implode(" && ",$cmd);
    $app = builder()->get_application();

    list($status, $output) = !isset($app->env)? run_local($cmd) : $app->env->exec($cmd);
    if(!$silent && count($output)) puts(implode("\n", $output));

    if ($status > 0) {
        if ($app->can_rollback) {
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
    return run_local($cmd);
}

function put($what,$where)
{
    if(!isset(builder()->get_application()->env)) return run_local("cp -R $what $where");
    builder()->get_application()->env->put($what,$where);
}

function get($what,$where)
{
    if(!isset(builder()->get_application()->env)) return run_local("cp -R $what $where");
    builder()->get_application()->env->get($what,$where);
}
