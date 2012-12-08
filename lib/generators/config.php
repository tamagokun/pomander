<?php

$env->repository('set your repository location here')
    ->url('set your application url here')
		->deploy_to('set your application location on server')
		//->user('set your ssh user')
		//->scm('set your scm. defaults to git')
		//->revision('')
		//
		//->backup(true)
;

$env->app(array(             // Your application server(s) host or IP	
	'your app-server here'
));

$env->db(array(              // Your database server(s) host or IP
	'your db-server here'
));

// If your app uses a database uncomment this:
//$env->database(array(
	//'name' => '',
	//'user' => '',
	//'password' => '',
	//'host' => '127.0.0.1',
	//'charset' => 'utf8'
//));
