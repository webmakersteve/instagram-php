<?php

namespace Webmakersteve\Instagram;

use Webmakersteve\Exception\Exception;
use Webmakersteve\Exception\NotFoundException;
use Webmakersteve\Exception\AuthenticationException;

use GuzzleHttp\Client as GuzzleClient;

class Client {

  const VERSION = '4.0.2';
  const API_VERSION = 1;
  const HOST = 'api.instagram.com';

  const METHOD_POST = 'POST';
  const METHOD_GET = 'GET';
  const METHOD_PUT = 'PUT';
  const METHOD_UPDATE = 'UPDATE';
  const METHOD_PATCH = 'PATCH';

  const DEFAULT_LIMIT = 20;

  protected $version = self::VERSION;

  private $access_token,
          $client_id,
          $client_secret,
          $limit = self::DEFAULT_LIMIT;

  // private methods

  /**
    * Creates the Instagram URL from the path. Adds access token.
    *
    * @return String
    */
  private function buildInstagramURL($path, $params = false) {
      // https://api.instagram.com/v1/tags/nofilter/media/recent?access_token=ACCESS_TOKEN

      if (!$path) {
          throw InvalidArgumentException('Path needs to be set and not empty');
      }

      $url = sprintf('https://%s/v%d/%s?access_token=%s', self::HOST, self::API_VERSION, $path, urlencode($this->getAccessToken()));

      if ($params) {
          if (!is_array($params)) {
              throw InvalidArgumentException('Params must be an array');
          }

          foreach($params as $paramKey => $paramValue) {
              $value = urlencode($paramValue);
              $url .= "&${paramKey}=${value}";
          }
      }

      return $url;

  }

  /**
    * Reads response information and returns the format best suited to the body. Usually JSON
    *
    * @return \Webmakersteve\Instagram\Response
    */
  private function parseResponse($response) {
      return new Response($response);
  }

  protected function getUserAgent() {
      return 'instagram/' . $this->version . ';php';
  }

  protected function getDefaultOptions() {
      $opts = [
          'headers' => [
              'User-Agent' => $this->getUserAgent(),
              'Accept' => 'application/json'
          ]
      ];

      return $opts;
  }

  /**
    * Does the request and returns the Guzzle response
    *
    * @return \Guzzle\Http\Response
    */
  private function doRequest($path, $method = self::METHOD_GET, $params = array()) {

      try {

          $client = new GuzzleClient();
          $options = $this->getDefaultOptions();

          if (in_array($method, [self::METHOD_POST, self::METHOD_PUT, self::METHOD_PATCH])) {
              $url = $this->buildInstagramURL($path);
              $options['body'] = http_build_query($params);
          } else {
              $url = $this->buildInstagramURL($path, $params);
          }

          $response = $client->request($method, $url, $options);

          $status = $response->getStatusCode();

          return $response;

      } catch (\GuzzleHttp\Exception\ClientException $e) {
          $code = $e->getCode();

          switch ($code) {
            case 404:
                throw new NotFoundException($e->getMessage(), 404);
                break;
            default:
                // Seems instagram uses this response type for quite a lot of information
                if ($e->hasResponse()) {
                    $response = $this->parseResponse($e->getResponse());

                    $message = $response->getProperty('meta.error_message', 'Message not available');
                    $code = $response->getProperty('meta.code', 0);
                    $type = $response->getProperty('meta.error_type');

                    switch ($type) {

                        case 'OAuthParameterException':
                            throw new AuthenticationException($message, $code);
                            break;
                        default:
                            throw new Exception($message, $code);

                    }

                } else {
                    // It does not have a response. We should just throw a generic exception here
                    throw new Exception($e->getMessage(), $code);
                }
                break;
          }
      } catch (\GuzzleHttp\Exception\ConnectException $e) {
          throw new Exception($e->getMessage());
          return false;
      }

      return false;

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

  public function __construct( $options = array() ) {

      $res = $this->doRequest('tags/nofilter/media/recent', self::METHOD_GET);

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
