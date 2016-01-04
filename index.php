<?php

use Webmakersteve\Instagram\Client;
use Webmakersteve\Errors\Error;

require 'vendor/autoload.php';

// Dotenv::load(__DIR__);
// $sendgrid_apikey = getenv('SG_KEY');


$x = new Client();
$data = $x->getUser();

print_r($data);
