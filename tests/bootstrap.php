<?php

$base = dirname(__DIR__);

require $base . '/bootstrap.php';

if (file_exists($base . '/.env')) {

    $dotenv = new Dotenv\Dotenv($base);
    $dotenv->load();

    // $dotenv->required(['INSTAGRAM_CLIENT_ID', 'INSTAGRAM_CLIENT_SECRET']); // ->notEmpty();

}

define('INSTAGRAM_CLIENT_ID', getenv('INSTAGRAM_CLIENT_ID'));
define('INSTAGRAM_CLIENT_SECRET', getenv('INSTAGRAM_CLIENT_SECRET'));
