# Instagram PHP [![Build Status](https://travis-ci.org/webmakersteve/instagram-php.svg?branch=master)](https://travis-ci.org/webmakersteve/instagram-php) [![Latest Stable Version](https://poser.pugx.org/webmakersteve/instagram/version)](https://packagist.org/packages/webmakersteve/instagram) [![Total Downloads](https://poser.pugx.org/webmakersteve/instagram/downloads)](https://packagist.org/packages/webmakersteve/instagram)

Yet another Instagram library. The old one I depended on became outdated so I began the process of rewriting it.

## Contributing

So far it has the user endpoints. Happy to take any pull requests. Adding new endpoints is super easy. Take a look at the code for one:

```php
public function getUserMedia($id = 'self', $limit = null, $min = false, $max = false) {
  return $this->doRequest('users/:id/media/recent', self::METHOD_GET, [
    'count' => $this->getLimitSize($limit),
    'id' => $id,
    'min_id' => $min,
    'max_id' => $max
  ]);
}
```

That's right. As of `1/5` the route declarations even support PARAMETER REPLACEMENT. Any parameters found in the route will be stripped from the rest of the request building. It also strips falsy parameters so you can write less logic. It does strict testing, that is, `===`.

WOAH. So, feel free to pull request with more methods of the API. I am trying to make parameters for the endpoint parameters for the function. I know there may be situations where it doesn't make sense to have so many parameters. In situations where there are a **lot** of optional parameters, I will likely be making an associative array to deal with that.

## Installation

```
curl -s http://getcomposer.org/installer | php
php composer.phar require webmakersteve/instagram
```

## Example

Well, anyway - typical OAuth API flow

```php
$instagram = new Webmakersteve\Instagram\Client([
  'client_id' => 'KINDA SECRET',
  'client_secret' => 'ACTUALLY SECRET',
  'redirect_uri' => 'http://yourapp.com' // This is necessary to use the OAuth flow.
]);
```

When we have our beautiful client the API allows you to do public methods. There used to be more, but now it seems only the OAuth transactions are "public"

```php
// Redirect them to the login url
$instagram->getLoginUrl(['basic', 'public_content']); // You can add additional scopes in here like this

// And when they return...

$code = $_GET['code'];
$token = $instagram->getOAuthToken($code, true); // True param means don't return the rest of the data

$instagram->setAccessToken($token); // Now we have access to special endpoints

var_dump($instagram->getUser()); // Should return the user information

```

Simple is that. Of course, you need to store the access token somewhere and shove it in there so they don't need to authorize individual requests. But that's up to whatever app infrastructure you're using.

All API endpoints, or the `doRequest` method more importantly, return `Response` classes. These nifty little loaders take Response objects and convert them into something a bit more easy to work with for a lot of situations.

Firstly, they can be used like `stdClass`es, so if you just want to do it that way go ahead. If there was JSON data they will automatically decode it. If the Data wasn't JSON, accessing it like an object will just return `false`. So, no nasty PHP errors.

You also get a little abstraction for those situations where you need to access nested properties. Naturally, if the first thing returned null and you try to chain something it is going to error. I know APIs get messy sometimes, so I made a `getProperty` function. This is the way I would recommend interacting with it.

Instead of `$data->username` you would do `$data->getProperty('username')`. If the property exists, you'll get it. If not, it will return the default, which is `''`. You can set your own default as the second parameter.

In even brighter news, you can do this in the case of nested properties. `$data->getProperty('user.name.first_name')` If the property is there you'll get it. This doesn't support any array indexing or anything yet, so you'll need to deal with that yourself, but I felt that isn't as large a problem.

There are a few exception classes. There is some Isntagram-sourced data in them. It doesn't just blindly shove the JSON into the message - it tries to actually parse it out and give pertinent information. So, enjoy that.

Let me know what you think. Stars are great. Pull requests are better.

## License

Licensed under the MIT license. Copy enclosed in repo.

:)
