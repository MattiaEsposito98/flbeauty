<?php

/*
| Router per il server di sviluppo del backend (usato da .claude/launch.json e start-dev.bat).
|
| Fa quello che fa `php artisan serve`, ma si può lanciare da qualsiasi cartella e permette di
| passare opzioni a PHP: così il backend locale gira con OPcache attivo e Xdebug spento, circa
| 4 volte più veloce (senza toccare il php.ini di XAMPP, usato anche da altri progetti).
|
|   php -d xdebug.mode=off -d opcache.enable=1 -d opcache.enable_cli=1 \
|       -S 127.0.0.1:8000 -t backend/public tools/dev-server.php
|
| Per fare debug serve prima un Xdebug per PHP 8.5 (ora spento), poi `php artisan serve`.
*/

$public = realpath(__DIR__.'/../backend/public');
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '');

// File veri (immagini caricate, css, js…): li serve direttamente il server di PHP.
if ($uri !== '/' && is_file($public.$uri)) {
    return false;
}

file_put_contents('php://stdout', sprintf("[%s] %s [%s] %s\n", date('D M j H:i:s Y'), $_SERVER['REMOTE_ADDR'], $_SERVER['REQUEST_METHOD'], $uri));

chdir($public);
$_SERVER['SCRIPT_FILENAME'] = $public.'/index.php';

require $public.'/index.php';
