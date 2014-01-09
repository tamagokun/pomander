<?php
namespace Pomander;

use phake\Application;

class Cli
{
    public $action = "invoke";
    public $trace = false;
    public $app;

    public function exec($args)
    {
        try {
            if (function_exists('pcntl_signal')) {
                declare(ticks=1);
                pcntl_signal(SIGINT, array($this, "cancel"));
                pcntl_signal(SIGTERM, array($this, "cancel"));
            }

            $parser = new OptionParser($args);
            // handle cli options
            foreach ($parser->get_options() as $option => $value) $this->handle_option($option);

            $task_args = array();
            $tasks = array();

            // handle tasks and task vars
            foreach ($parser->get_non_options() as $option) {
                if(strpos($option, '=') === false) $tasks[] = $option;
                else $task_args[] = $option;
            }
            if(empty($tasks)) $tasks = array('default');

            $this->app = new Application();
            $this->app->set_args(\phake\Utils::parse_args($task_args));
            $this->app->top_level_tasks = $tasks;
            $this->app->dir = dirname(__DIR__);

            \phake\Builder::$global = new \phake\Builder($this->app);

            $pom = new \Pomander\Builder();
            $pom->run();

            $this->app->reset();

            switch ($this->action) {
                case 'list':
                    $this->print_tasks();
                    break;
                case 'invoke':
                    foreach($tasks as $task_name) $this->app->invoke($task_name);
                    break;
            }
        } catch (\phake\TaskNotFoundException $tnfe) {
            $this->fatal($tnfe, "Don't know how to build task '$task_name'\n");
        } catch (\Exception $e) {
            $this->fatal($e, null);
        }
    }

    public function cancel()
    {
        puts("Stopping..");
        $app = builder()->get_application();
        if ($app && $app->can_rollback) {
            warn("fail","Rolling back...");
            $app->invoke('rollback');
            info("rollback","rollback complete.");
            exit(2);
        }
        abort("fail","cancelled!", 2);
    }

    public function error_handler($errno, $errstr, $errfile, $errline)
    {
        $exception = new \ErrorException($errstr, 0, $errno, $errfile, $errline);

        return $this->fatal($exception, $errstr, $errno);
    }

    public function exception_handler(\Exception $e)
    {
        return $this->fatal($e);
    }

    protected function handle_option($option)
    {
        switch ($option) {
            case "t":
            case "trace":
                $this->trace = true;
                break;
            case "T":
            case "tasks":
                $this->action = "list";
                break;
            case "V":
            case "version":
                echo "Pomander ".implode(".", \Pomander::version())."\n";
                exit;
                break;
            case "h":
            case "H":
            case "help":
                $this->print_help();
                exit;
                break;
            default:
                puts("Unknown command line option '$option'\n");
                $this->print_help();
                exit(1);
                break;
        }
    }

    protected function fatal($exception, $message = null, $status = 1)
    {
        puts("aborted!");
        if(!$message) $message = $exception->getMessage();
        if(!$message) $message = get_class($exception);
        puts("$message\n");
        if($this->trace)
            puts($exception->getTraceAsString());
        else
            puts("(See full trace by running task with --trace)");
        exit($status > 0 ? $status : 1);
    }

    protected function print_help()
    {
        echo ansicolor("Usage:\n", 33);
        echo "pom {options} tasks...\n\n";
        echo ansicolor("Options:\n", 33);
        echo "    -T, --tasks        Display the available tasks.\n";
        echo "    -t, --trace        Turn on invoke/execute tracing, enable full backtrace.\n";
        echo "    -V, --version      Display the program version.\n";
        echo "    -h, -H, --help     Display the help message.\n";
    }

    protected function print_tasks()
    {
        $task_list = $this->app->get_task_list();
        if(!count($task_list)) return;
        $max = max(array_map('strlen', array_keys($task_list)));
        foreach($task_list as $name => $desc)
            echo str_pad($name, $max + 4) . $desc . "\n";
    }
}
