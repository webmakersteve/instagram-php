<?php

namespace Webmakersteve\Instagram;

class Client {

  const VERSION = '4.0.2';

  // private methods
  private function buildHTTPClient() {

  }

  private function buildInstagramURL() {

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

  public function __construct( $params ) {



  }

  public function getLoginUrl($scopes) {

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
