Neutrino : Http Client component
==============================================
[![Build Status](https://travis-ci.org/pn-neutrino/http.svg?branch=master)](https://travis-ci.org/pn-neutrino/http) [![Coverage Status](https://coveralls.io/repos/github/pn-neutrino/http/badge.svg?branch=master)](https://coveralls.io/github/pn-neutrino/http)

Http Client library using Curl or HttpStream.

# Basic 
```php
$provider->get($url, $parameters, $options);
$provider->post($url, $parameters, $options);
$provider->delete($url, $parameters, $options);
$provider->put($url, $parameters, $options);
$provider->head($url, $parameters, $options);
$provider->patch($url, $parameters, $options);

$provider->request($method, $url, $parameters, $options);
```

`$url` Contain the url to call. 
`$parameters` Contain the parameters to send.
`$options` Contain the options of the request.

```php
$options = [
    // Headers to send
    'headers' => [],
    // Retrieve the full response (Header + Body)
    'full' => true,
    // Make a JsonRequest (Only for POST, PUT, PATCH methods)
    'json' => true,
];
```

# Provider

## Curl
require curl extension.

#### How use :
```php
use \Neutrino\Http\Provider\Curl as HttpCurl;
use \Neutrino\Http\Method;

$curl = new HttpCurl;

$response = $curl
    ->get('http://www.google.com', ['foo' => 'bar'], ['Accept' => 'text/plain'])
    ->send();
  
$response->code; // HTTP Status Code
```

### Curl\Streaming
Curl\Stream allows you to work with large queries, by recovers content part by part.

#### How use :
```php
use \Neutrino\Http\Provider\Curl\Streaming as HttpCurlStream;
use \Neutrino\Http\Method;

$curl = new HttpCurlStream;

$response = $curl
    ->get('http://www.google.com')
    ->on(HttpCurlStream::EVENT_START, function (HttpCurlStream $curl) {
        // Start to download response body
        // Header are fully loaded when the event are raised
    })
    ->on(HttpCurlStream::EVENT_PROGRESS, function (HttpCurlStream $curl, $content) {
        // Download progress
        // $content contain the response part
    })
    ->send();
```

Transfer huge data, without overloading the php memory :
```php
$curl
    ->get('http://www.google.com')
    ->on(HttpCurlStream::EVENT_START, function (HttpCurlStream $curl) {
        if ($curl->getResponse()->header->has('Content-Length')) {
            header('Content-Length: ' . $curl->getResponse()->header->get('Content-Length'));
        }
    })
    ->on(HttpCurlStream::EVENT_PROGRESS, function (HttpCurlStream $curl, $content) {
        echo $content;
        ob_flush();
        flush();
        // => Direct echo contents & flush the output (free memory)
    })
    ->send();
```

Download huge file, without overloading the php memory :
```php
$resource = fopen($path, 'w');

$curl
    ->get('http://www.google.com')
    ->on(HttpCurlStream::EVENT_PROGRESS, function (HttpCurlStream $curl, $content) use ($resource) {
        fwrite($resource, $content, strlen($content));
    })
    ->send();

fclose($resource);
```

## StreamContext
StreamContext make HTTP call via the php wrapper.

This require you have "allow_url_fopen" configuration value set to '1'.

#### How use :
```php
use \Neutrino\Http\Provider\StreamContext as HttpStreamCtx;
use \Neutrino\Http\Method;

$streamCtx = new HttpStreamCtx;

$response = $streamCtx
    ->get('http://www.google.com', ['foo' => 'bar'], ['headers' => ['Accept' => 'text/plain']])
    ->send();
  
$response->code; // HTTP Status Code
```

### StreamContext\Streaming
Such as Curl\Streaming, StreamContext\Streaming allows you to work with large queries, by recovers content part by part.

#### How use :
```php
use \Neutrino\Http\Provider\StreamContext\Streaming as HttpStreamCtxStreaming;
use \Neutrino\Http\Method;

$streamCtx = new HttpStreamCtxStreaming;

$response = $streamCtx
    ->get('http://www.google.com')
    ->on(HttpStreamCtxStreaming::EVENT_START, function (HttpStreamCtxStreaming $streamCtx) {
        // Start to download response body
        // Header are fully loaded when the event are raised
    })
    ->on(HttpStreamCtxStreaming::EVENT_PROGRESS, function (HttpStreamCtxStreaming $streamCtx, $content) {
        // Download progress
        // $content contain the response part
    })
    ->send();
```

# Auth
Authentication is a request component. 

## Auth\Basic
Auth\Basic provides the elements to configure a call with an Basic Authorization.

#### How use :
```php
use \Neutrino\Http\Auth\Basic as AuthBasic;
use \Neutrino\Http\Provider\StreamContext as HttpStreamCtx;
use \Neutrino\Http\Method;

$streamCtx = new HttpStreamCtx;

$response = $streamCtx
    ->get('http://www.google.com')
    ->setAuth(new AuthBasic('user', 'pass'))
    ->send();
```

## Auth\Curl
Specific for Curl provider.

Auth\Curl provides the elements to build a call with Curl Auth.

#### How use :
```php
use \Neutrino\Http\Auth\Curl as AuthCurl;
use \Neutrino\Http\Provider\Curl as HttpCurl;
use \Neutrino\Http\Method;

$curl = new HttpCurl;

$response = $curl
    ->get('http://www.google.com')
    ->setAuth(new AuthCurl(CURLAUTH_BASIC | CURLAUTH_DIGEST, 'user', 'pass'))
    ->send();
```

## Custom Auth Component
You can easily make your own Auth Component : 

```php
namespace MyLib\Http\Auth;

use Neutrino\Http\Request;
use Neutrino\Http\Contract\Request\Component;

class Hmac implements Component
{
    private $id;
    private $value;

    public function __construct($id, $value)
    {
        $this->id = $id;
        $this->value = $value;
    }

    public function build(Request $request)
    {
        $date = date('D, d M Y H:i:s', time());
        $signature = urlencode(base64_encode(hash_hmac('sha1', "date: $date", $this->value, true)));

        $request
            ->setHeader('Date', $date)
            ->setHeader('Authorization', 'Signature keyId="' . $this->id . '",algorithm="hmac-sha1",signature="' . $signature . '"');
    }
}
```
```php
use \MyLib\Http\Auth\Hmac as AuthHmac;
use \Neutrino\Http\Provider\Curl as HttpCurl;
use \Neutrino\Http\Method;

$curl = new HttpCurl;

$response = $curl
    ->get('http://www.google.com')
    ->setAuth(new AuthHmac('key_id', 'key_value'))
    ->send();
```

# Response 
## Basic
```php
$response->code;   // HTTP Status Code
$response->status; // HTTP Status Message
$response->header; // Response Headers
$response->body;   // Response Body
```
## Provider Info
```php
$response->errorCode; // Provider Error Code
$response->error;     // Provider Error Message
$response->providerDatas; // All Provider Information (if available)
```

## Parse 

```php
use \Neutrino\Http\Parser;

// Json Body => Object
$jsonObject = $response->parse(Parser\Json::class)->data;

// Xml Body => SimpleXMLElement
$xmlElement = $response->parse(Parser\Xml::class)->data;

// Xml Body => array
$xmlArray = $response->parse(Parser\XmlArray::class)->data;

// Other exemple : (PHP7)
$response->parse(new class implements Parser\Parserize
{
    public function parse($body)
    {
        return unserialize($body);
    }
});

$response->data; // Unserialized body
```
