<?php

namespace Webmakersteve\Instagram;

use GuzzleHttp\Client as Guzzle;

class Client {

  const VERSION = '4.0.2';
  const API_VERSION = 1;
  const HOST = 'api.instagram.com';

  const METHOD_POST = 'POST';
  const METHOD_GET = 'GET';
  const METHOD_PUT = 'PUT';
  const METHOD_UPDATE = 'UPDATE';

  protected $version = self::VERSION;

  // private methods

  /**
    * Prepares the HTTP client
    *
    * @return \Guzzle\Http\Client
    */
  private function prepareHttpClient() {
      $client = new Guzzle();
      $client->setUserAgent('instagram/' . $this->version . ';php');

      return $client;
  }

  private function buildInstagramURL($path) {
      // https://api.instagram.com/v1/tags/nofilter/media/recent?access_token=ACCESS_TOKEN

      if (!$path) {
          throw InvalidArgumentException('Path needs to be set and not empty');
      }

      $url = sprintf('https://%s/v%d/%s/%s?access_token=%s', self::HOST, self::API_VERSION, $path, urlencode($this->getAccessToken()));

      return $url;

  }

  private function doRequest($path, $method = self::METHOD_GET, $params = array()) {
      $url = $this->buildInstagramURL($path);

      $client = $this->prepareHttpClient();
      $response = $client->request($method, $url, $params);

      return $response;

  }

  private function getLimitSize($override) {
    if ($override === null) {


      return 0;
    }

    return $override;
  }

  private function processUserId($id) {

  }

  // End that

  public function __construct( $options ) {

      $res = $this->doRequest('hey');
      var_dump($res);
      exit;

  }

  public function getLoginUrl($scopes) {

  }

  public function getAccessToken() {

  }

  public function setAccessToken($code) {

  }

  public function getOAuthToken($code, $access_code_only = false) {

  }

  // User API

  public function getUser($id = false) {

    if (!$id) {
      return $this->getUserAuth();
    }

  }

  public function searchUser($name, $limit = null) {
    $limit = $this->getLimitSize($limit);
  }

  public function getUserMedia($id = 'self', $limit = null) {
    $limit = $this->getLimitSize($limit);
    $userId = $this->processUserId($id);
  }

  // Auth

  public function getUserLikes($limit = null) {
    $limit = $this->getLimitSize($limit);
  }

  public function getUserFeed($limit = null) {
    $limit = $this->getLimitSize($limit);
  }

  // Relationship Methods

}
