<?php

group('composer', function() {

	desc("Install dependencies with Composer");
	task('install', function($app) {
		if($app->env->composer === false) return;
		run(array(
			"([ -e 'composer.json' ] && which composer &>/dev/null)",
			"composer install --optimize-autoloader || echo '    composer.json not found'"
		));
	});

	task('update', function($app) {
		if($app->env->composer === false) return;
		run(array(
			"([ -e 'composer.json' ] && which composer &>/dev/null)",
			"composer update --optimize-autoloader || echo '    composer.json not found'"
		));
	});

});
