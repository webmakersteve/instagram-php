<?php

use Webmakersteve\Instagram\Client;

class ClientTest extends PHPUnit_Framework_TestCase
{

    public function setUp()
    {
        $this->client = new Client(array(
            'client_id' => INSTAGRAM_CLIENT_ID,
            'client_secret' => INSTAGRAM_CLIENT_SECRET,
            'redirect_uri' => 'asd'
        ));
    }

    public function tearDown()
    {
        $this->client = null;
    }
    // ...

    public function testClientCanBeCreated()
    {

        // Assert
        $this->assertEquals(-1,-1);
    }

    public function testEndpointsRequireAuthorization()
    {

        $this->assertEquals(-1,-1);

    }

    // ...
}
