Neutrino : Http Client component
==============================================
[![Build Status](https://travis-ci.org/pn-neutrino/http.svg?branch=master)](https://travis-ci.org/pn-neutrino/http) [![Coverage Status](https://coveralls.io/repos/github/pn-neutrino/http/badge.svg?branch=master)](https://coveralls.io/github/pn-neutrino/http)

Http Client library using Curl or HttpStream.

# Provider

## Curl
require curl extension.
### Curl\Standard
Standard curl request.

#### How use :
```php
use \Neutrino\Http\Provider\Curl as HttpCurl;
use \Neutrino\Http\Method;

$curl = new HttpCurl;

$response = $curl
  ->setMethod(Method::GET)
  ->setUrl('http://www.google.com')
  ->setParams(['foo' => 'bar'])
  ->addParam('bar', 'baz')
  ->addHeader('Accept', 'text/plain')
  ->call();
  
$response->code; // HTTP Status Code
$response->
```

### Curl\Stream
Curl\Stream allows you to work with large queries, by recovers content part by part.

#### How use :
```php
use \Neutrino\Http\Provider\Curl\Stream as HttpCurlStream;
use \Neutrino\Http\Method;

$curl = new HttpCurlStream;

$response = $curl
    ->setMethod(Method::GET)
    ->setUrl('http://www.google.com')
    ->on(HttpCurlStream::EVENT_START, function (HttpCurlStream $curl) {
        // Start to download response body
        // Header are fully loaded when the event are raised
    })
    ->on(HttpCurlStream::EVENT_PROGRESS, function (HttpCurlStream $curl, $content) {
        // Download progress
        // $content contain the response part
    })
    ->call();
```

Transfer huge data, without overloading the php memory :
```php
$curl
    ->setMethod(Method::GET)
    ->setUrl('http://www.google.com')
    ->on(HttpCurlStream::EVENT_START, function (HttpCurlStream $curl) {
        if ($curl->response->header->has('Content-Length')) {
            header('Content-Length: ' . $curl->response->header->get('Content-Length'));
        }
    })
    ->on(HttpCurlStream::EVENT_PROGRESS, function (HttpCurlStream $curl, $content) {
        echo $content;
        ob_flush();
        flush();
        // => Direct echo contents & flush the output (free memory)
    })
    ->call();
```

Download huge file, without overloading the php memory :
```php
$resource = fopen($path, 'w');

$curl
    ->setMethod(Method::GET)
    ->setUrl('http://www.google.com')
    ->on(HttpCurlStream::EVENT_PROGRESS, function (HttpCurlStream $curl, $content) use ($resource) {
        fwrite($resource, $content, strlen($content));
    })
    ->call();

fclose($resource);
```

# Response 
## Basic
```php
$response->code;   // HTTP Status Code
$response->status; // HTTP Status Message
$response->header; // Response Headers
$response->body;   // Response Body
```
## Curl Info
```php
$response->errorCode; // CURL Error Code
$response->error;     // CURL Error Message
$response->curlInfos; // All CURL Information
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
