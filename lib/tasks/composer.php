<?php

group('composer', function() {

	desc("Install composer dependencies");
	task('install', function($app) {
		if($app->env->composer === false) return;
		run(array(
			"([ -e 'composer.json' ] && which composer &>/dev/null) && composer install || echo '    composer.json not found'"
		));
	});

});
