<?php

require 'vendor/autoload.php';

// Dotenv::load(__DIR__);
// $sendgrid_apikey = getenv('SG_KEY');
$sendgrid = new Client($sendgrid_apikey);
