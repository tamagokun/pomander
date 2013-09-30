<?php

$env->repository('set your repository location here')
    ->url('set your application url here')
    ->releases(true)
    ->keep_releases(5)
    //->user('set your ssh user')
    //->scm('set your scm. defaults to git')
    //->revision('')
    //
    //->backup(true)
;

//
// If you are deploying your application to a remote server:
//
//$env->deploy_to('set your application location on server');
//$env->app(array(             // Your application server(s) host or IP	
//	'your app-server here'
//));

// If you are deploying your application to a remote server,
// and using a database:
//$env->db(array(              // Your database server(s) host or IP
//	'your db-server here'
//));

// If your app uses a database uncomment this:
//$env->database(array(
	//'name' => '',
	//'user' => '',
	//'password' => '',
	//'host' => '127.0.0.1',
	//'charset' => 'utf8'
//));
