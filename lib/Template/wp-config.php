<?php
global $deploy;
$secret_keys = file_get_contents("https://api.wordpress.org/secret-key/1.1/salt/");
$cache = (isset($deploy->env->wordpress["cache"]))? "define('WP_CACHE', {$deploy->env->wordpress["cache"]});" : "";
$siteurl = (isset($deploy->env->wordpress["url"]))? "'{$deploy->env->wordpress["url"]}'":"'http://'.\$_SERVER['SERVER_NAME']";
$siteurl .= (isset($deploy->env->wordpress["base_uri"]))? ".'{$deploy->env->wordpress["base_uri"]}'" : "";
return <<<EOT
<?php
define('DB_NAME', '{$deploy->env->wordpress["db"]}');
define('DB_USER', '{$deploy->env->wordpress["db_user"]}');
define('DB_PASSWORD', '{$deploy->env->wordpress["db_password"]}');
define('DB_HOST', '{$deploy->env->wordpress["db_host"]}');
define('DB_CHARSET', '{$deploy->env->wordpress["db_charset"]}');
define('DB_COLLATE', '');
{$secret_keys}
\$table_prefix = '{$deploy->env->wordpress["db_prefix"]}';
define ('WPLANG', '');
if( !defined('ABSPATH') ) define('ABSPATH', dirname(__FILE__).'/wordpress/');
define('WP_SITEURL', {$siteurl});
define('WP_CONTENT_DIR', ABSPATH.'public');
define('WP_CONTENT_URL', WP_SITEURL.'/public');
define('WP_PLUGIN_DIR', dirname(__FILE__.'../').'/public/plugins');
define('WP_PLUGIN_URL', WP_SITEURL.'/public/plugins');
define('PLUGINDIR', WP_PLUGIN_DIR);
{$cache}
require_once(ABSPATH . 'wp-settings.php');
?>
EOT;
?>
