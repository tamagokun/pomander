<?php
$app = builder()->get_application();
$uri = (isset($app->env->wordpress["base_uri"]))? $app->env->wordpress["base_uri"] : "";
return <<<EOT
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteBase {$uri}/
RewriteRule ^index\.php$ - [L]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . {$uri}/index.php [L]
</IfModule>
EOT;
?>
