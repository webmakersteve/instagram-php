<?php

use Webmakersteve\Instagram\Client;
use Webmakersteve\Errors\Error;

require 'vendor/autoload.php';

// Dotenv::load(__DIR__);
// $sendgrid_apikey = getenv('SG_KEY');

$client = new Client(array(
    'client_id' => 'hey',
    'client_secret' => 'sa',
    'redirect_uri' => 'asd'
));

$client->getTaggedMedia('#hey');

$client->getUser();
