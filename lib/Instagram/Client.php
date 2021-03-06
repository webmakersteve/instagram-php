<?php
/**
 * Licensed under the MIT license.
 */

namespace Webmakersteve\Instagram;

use Webmakersteve\Exception\Exception;
use Webmakersteve\Exception\NotFoundException;
use Webmakersteve\Exception\AuthenticationException;
use Webmakersteve\Exception\NotPermittedException;

use GuzzleHttp\Client as GuzzleClient;

use \InvalidArgumentException;

/**
 * Client class is the main class you will interface with
 */
class Client {

 /**
  * Version of the client class
  */
  const VERSION = '4.0.2';

  /**
   * API version. Used in URLS
   */
  const API_VERSION = 1;

  /**
   * Host of the instagram API
   */
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
          $redirect_uri = false,
          $limit = self::DEFAULT_LIMIT;

  // private methods

  /**
    * Creates the Instagram URL from the path. Adds access token automatically unless $raw is specified.
    *
    * @param string $path String of the path with no prefixing characters, e.g. 'users/self'
    * @param array $params Parameters to put into the URL. Prioritizes parameter replacement into the URL, and after that will add them as a query string.
    * @param boolean $raw Determines whether the URL should be returned RAW. That is, without the access token and auth params.
    * @param string $method Should be assigned to one of the method constants. Determines whether params should be placed as a query string.
    *
    * @return string
    */
  private function buildInstagramURL($path, $params = false, $raw = false, $method = self::METHOD_GET) {

      if (!$path) {
          throw InvalidArgumentException('Path needs to be set and not empty');
      }

      $first = true;

      // We need to do parameter replacement

      $oldPath = $path;

      while (preg_match('#/(?P<paramName>[:][^/]+(?P<trail>/|\z))#i', $path, $matches)) {
          $paramName = $matches['paramName'];
          $trail = $matches['trail'] ? $matches['trail'] : '';
          // Trim first character
          $specialIndex = trim($paramName, ':/');

          // Find it in params list
          if (isset($params[$specialIndex])) {
              $replacement = $params[$specialIndex];
              unset($params[$specialIndex]);
          } else {
              $replacement = '';
          }

          $path = str_replace($paramName, $replacement . $trail, $path );

      }

      $baseUrl = sprintf( '%s://%s', $this->options['protocol'], self::HOST );

      if ($raw) {
          $url = sprintf( '%s/%s', $baseUrl, $path);
      } else {
          $url = sprintf( '%s/v%d/%s?access_token=%s', $baseUrl, self::API_VERSION, $path, urlencode($this->getAccessToken()));
      }


      if (!in_array($method, [self::METHOD_POST, self::METHOD_PUT, self::METHOD_PATCH]) && $params) {
          if (!is_array($params)) {
              throw InvalidArgumentException('Params must be an array');
          }

          foreach($params as $paramKey => $paramValue) {
              if ($paramValue === false) continue;
              $value = urlencode($paramValue);
              if ($raw && $first) {
                  $first = false;
                  $url .= "?${paramKey}=${value}";
              } else {
                  $url .= "&${paramKey}=${value}";
              }
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

  /**
    * Returns a user agent for the API.
    *
    * @return string
    */
  protected function getUserAgent() {
      return 'instagram/' . $this->version . ';php';
  }

  /**
    * Returns default options for the guzzle HTTP client. Things like specifying that we want JSON.
    *
    * @return array
    */
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
    * Helper function to do a GET request. Accessible to public in case an endpoint is not implemented as a method abstraction.
    *
    * @param string $path The path that will be entered into the URL filter.
    * @param array $params The params to be entered into the URL.
    *
    * @return \Webmakersteve\Instagram\Response
    */
  public function get($path, $params = array()) {
    return $this->doRequest($path, self::METHOD_GET, $params);
  }

  /**
    * Helper function to do a POST request. Accessible to public in case an endpoint is not implemented as a method abstraction.
    *
    * @param string $path The path that will be entered into the URL filter.
    * @param array $params The params to be entered into the request body.
    *
    * @return \Webmakersteve\Instagram\Response
    */
  public function post($path, $params = array()) {
    return $this->doRequest($path, self::METHOD_POST, $params);
  }

  /**
    * Helper function to do a PUT request. Accessible to public in case an endpoint is not implemented as a method abstraction.
    *
    * @param string $path The path that will be entered into the URL filter.
    * @param array $params The params to be entered into the request body.
    *
    * @return \Webmakersteve\Instagram\Response
    */
  public function put($path, $params = array()) {
    return $this->doRequest($path, self::METHOD_PUT, $params);
  }

  /**
    * Does the request and returns the Guzzle response
    *
    * @param string $path The path to be entered into the URL filter.
    * @param string $method The method constant to be compared against to determine where the parameters go
    * @param array $params The parameters to be sent with the request.
    *
    * @throws \Webmakersteve\Instagram\AuthenticationException
    * @throws \Webmakersteve\Instagram\NotPermittedException
    * @throws \Webmakersteve\Instagram\Exception
    *
    * @return \Webmakersteve\Instagram\Response
    */
  private function doRequest($path, $method = self::METHOD_GET, $params = array()) {

      $url = false;

      try {

          $client = new GuzzleClient();
          $options = $this->getDefaultOptions();

          if (preg_match('#^http#i', $path)) {
              $url = $path;
          }

          if (!$url) $url = $this->buildInstagramURL($path, $params, false, $method); // Send method in so it knows to only use param replacement

          if (in_array($method, [self::METHOD_POST, self::METHOD_PUT, self::METHOD_PATCH])) {
              $options['form_params'] = $params;
          }

          $response = $client->request($method, $url, $options);

          return $this->parseResponse($response);

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
                    $code = $response->getProperty('meta.code', $code);
                    $type = $response->getProperty('meta.error_type', 'Unknown');

                    // Not sure why meta is sometimes used and sometimes not but let's try the other one too in case
                    if (!$response->getProperty('meta', false)) {
                        $message = $response->getProperty('error_message', 'Message is not available');
                        $code = $response->getProperty('code', $code);
                        $type = $response->getProperty('error_type', 'Unknown');
                    }

                    $message = "[${type}]: ${message}";

                    switch ($type) {

                        case 'OAuthParameterException':
                            throw new AuthenticationException($message, $code);
                            break;
                        case 'OAuthPermissionsException':
                            throw new NotPermittedException($message, $code);
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

  /**
    * Helper function to return a limit or an override if it is not a falsy value
    *
    * @param integer $override The overriding limit. If it is not null, it will return that value. Otherwise it returns the default limit.
    *
    * @return integer
    */
  private function getLimitSize($override) {
    if ($override === null) {
        return $this->limit;
    }

    return $override;
  }

  // End that

  /**
    * Options array to hold special HTTP options. These are not implemented yet, but will allow you to turn off SSL verification or change the protocol.
    */
  private $options = array();

  /**
    * Constructor function. Takes client ID, secret, and redirect URI to establish the class. Can take other options
    *
    * @param array $options Options to create the client with. Requires 'client_id', 'client_secret', and 'redirect_uri' to properly work.
    *
    * @return \Webmakersteve\Instagram\Client
    */
  public function __construct( $options = array() ) {

      $this->options = array();
      // We need to set the client id, client secret, and all that shiz
      // Check if api key is present

      $client_id = isset($options['client_id']) ? $options['client_id'] : false;
      $client_secret = isset($options['client_secret']) ? $options['client_secret'] : false;
      $redirect_uri = isset($options['redirect_uri']) ? $options['redirect_uri'] : false;

      if (is_string($client_id)) {
          $this->client_id = $client_id;
      } else {
          throw new InvalidArgumentException('Client ID must be set to communicate with Instagram');
      }

      if (is_string($client_secret)) {
          $this->client_secret = $client_secret;
      } else {
          throw new InvalidArgumentException('Client secret must be set and must be a string');
      }

      $this->redirect_uri = $redirect_uri;

      $this->options['turn_off_ssl_verification'] = (isset($this->options['turn_off_ssl_verification']) && $this->options['turn_off_ssl_verification'] == true);

      if (!isset($this->options['raise_exceptions'])) {
          $this->options['raise_exceptions'] = true;
      }

      $this->options['protocol'] = isset($options['protocol']) ? $options['protocol'] : 'https';

  }

  /**
    * Helper function to get the login URL.
    *
    * @param string $scopes An array of scopes you want to request the API authorize you for.
    *
    * @return string The URL to be used in a redirect request.
    *
    * @api
    */
  public function getLoginUrl($scopes = array('basic')) {
      // https://api.instagram.com/oauth/authorize/?client_id=CLIENT-ID&redirect_uri=REDIRECT-URI&response_type=code
      return $this->buildInstagramURL('oauth/authorize', array(
          'client_id' => $this->client_id,
          'redirect_uri' => $this->redirect_uri,
          'response_type' => 'code',
          'scope' => implode(' ', $scopes)
      ), true);
  }

  /**
    * Helper function to get the Instagram access token. If it isn't set throws an exception.
    *
    * @throws \Webmakersteve\Instagram\AuthenticationException
    * @return string API access token
    */
  private function getAccessToken() {
      $access_token = $this->access_token;
      if (!$access_token || empty($access_token)) {
          throw new AuthenticationException('Client has not been authorized for authenticated API calls');
      }
      return $access_token;
  }

  /**
    * Helper function to set the Instagram access token. Accessible publicly to allow it to be set after the OAuth flow
    *
    * @return \Webmakersteve\Instagram\Client
    *
    * @api
    */
  public function setAccessToken($access_token) {
      $this->access_token = $access_token;
      return $this;
  }

  /**
    * Converts an access code into an access token through the API
    *
    * @param string $code Code passed through a query parameter by the API
    * @param boolean $access_code_only Whether to return the entire object or just the access code.
    *
    * @return \Webmakersteve\Instagram\Response|string Returns a Response object or a string based on the second parameter
    *
    * @api
    */
  public function getOAuthToken($code, $access_code_only = false) {
      $url = $this->buildInstagramUrl('oauth/access_token', array(), true);
      $returnObj = $this->doRequest($url, self::METHOD_POST, array(
          'client_secret' => $this->client_secret,
          'client_id' => $this->client_id,
          'grant_type' => 'authorization_code',
          'code' => $code,
          'redirect_uri' => $this->redirect_uri
      ));

      if (!$access_code_only) return $returnObj;

      return $returnObj->access_token;
  }

  // User API

  /**
    * Helper function to get user data from the API
    *
    * @param string|integer $id 'self' as an ID returns the info about the current user. Otherwise, an ID returns data about that user.
    *
    * @return \Webmakersteve\Instagram|Response
    *
    * @api
    */
  public function getUser($id = 'self') {
    return $this->doRequest('users/:id', self::METHOD_GET, array(
        'id' => $id,
    ));
  }

  /**
    * Helper function to get search user data from the API
    *
    * @param string|integer $name The name as a query to search.
    * @param integer $limit The limit of results to return
    *
    * @return \Webmakersteve\Instagram|Response
    *
    * @api
    */
  public function searchUser($name, $limit = null) {
      $opts = [
          'count' => $this->getLimitSize($limit),
          'q' => $name
      ];

      return $this->doRequest('users/search', self::METHOD_GET, $opts);
  }

  /**
    * Helper function to get posts by a given user.
    *
    * @param string|integer $id 'self' as an ID returns the posts by the current user. Otherwise, an ID returns posts by that user.
    * @param integer $limit Max number of users to return. Otherwise defaults to the default limit.
    * @param integer $min The min ID of users to return. Useful for pagination.
    * @param integer $max The max ID of users to return. Useful for pagination.
    *
    * @return \Webmakersteve\Instagram|Response
    *
    * @api
    */
  public function getUserMedia($id = 'self', $limit = null, $min = false, $max = false) {

    $opts = [
        'count' => $this->getLimitSize($limit),
        'id' => $id,
        'min_id' => $min,
        'max_id' => $max
    ];

    return $this->doRequest('users/:id/media/recent', self::METHOD_GET, $opts);
  }

  // Auth
  /**
    * Helper function to get liked users of the logged in user
    *
    * @param integer $limit Max number of users to return. Otherwise defaults to the default limit.
    * @param integer $max The max ID of users to return. Useful for pagination
    *
    * @return \Webmakersteve\Instagram|Response
    *
    * @api
    */
  public function getUserLiked($limit = null, $max = false) {
      $opts = [
          'count' => $this->getLimitSize($limit),
          'max_like_id' => $max
      ];

      return $this->doRequest('users/self/media/liked', self::METHOD_GET, $opts);
  }

  /**
    * Helper function to get the currently logged in user's feed.
    *
    * @param integer $limit Max number of users to return. Otherwise defaults to the default limit.
    *
    * @return \Webmakersteve\Instagram|Response
    *
    * @api
    */
  public function getUserFeed($limit = null) {
    $limit = $this->getLimitSize($limit);
  }

  // Public
  /**
    * Searches for currently used Instagram tags.
    *
    * @param string $q The query to search
    *
    * @return \Webmakersteve\Instagram|Response
    *
    * @api
    */
  public function searchTags($q) {
      return $this->doRequest('tags/search', self::METHOD_GET, array(
          'q' => $q
      ));
  }

  /**
    * Gets information about a given tag.
    *
    * @param string $tag The tag string. Can be returned from the searchTags method
    *
    * @return \Webmakersteve\Instagram|Response
    *
    * @api
    * @see \Webmakersteve\Instagram\Client::searchTags()
    */
  public function getTag($tag) {
      return $this->doRequest('tags/:tag', self::METHOD_GET, array(
          'tag' => ltrim($tag, '# ')
      ));
  }

  /**
    * Get posts tagged with a given hashtag.
    *
    * @param string $tag A hashtag. The # will be trimmed.
    * @param integer $limit Max number of users to return. Otherwise defaults to the default limit.
    * @param integer $min The min ID of posts to return. Useful for pagination.
    * @param integer $max The max ID of posts to return. Useful for pagination.
    *
    * @return \Webmakersteve\Instagram|Response
    * @api
    */
  public function getTaggedMedia($tag, $limit = null, $min = false, $max = false) {
      $opts = [
          'tag' => ltrim($tag, '# '),
          'count' => $this->getLimitSize($limit),
          'min_tag_id' => $min,
          'max_tag_id' => $max
      ];

      return $this->doRequest('tags/:tag/media/recent', self::METHOD_GET, $opts);
  }

}
