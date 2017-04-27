<?php

namespace Neutrino\Http\Client\Provider;

use Neutrino\Http\Header;
use Neutrino\Http\Exception as HttpException;
use Neutrino\Http\Provider\Exception as ProviderException;
use Neutrino\Http\Request;
use Neutrino\Http\Response;

use Neutrino\Http\Uri;

class StreamContext extends Request
{
    private static $isAvailable;

    public static function checkAvailability()
    {
        if (!isset(self::$isAvailable)) {
            $wrappers = stream_get_wrappers();

            self::$isAvailable = in_array('http', $wrappers) && in_array('https', $wrappers);
        }

        if (!self::$isAvailable) {
            throw new ProviderException(self::class . ' HTTP or HTTPS stream wrappers not registered.');
        }
    }

    public function __construct(Response $response = null, Header $header = null)
    {
        self::checkAvailability();

        parent::__construct($response, $header);
    }

    /**
     * @param int $timeout
     *
     * @return $this
     */
    public function setTimeout($timeout)
    {
        return $this->setOption('timeout', $timeout);
    }

    /**
     * @param $errno
     * @param $errstr
     * @throws HttpException
     */
    private function errorHandler($errno, $errstr)
    {
        throw new HttpException($errstr, $errno);
    }

    protected function makeCall()
    {
        try {
            $context = stream_context_create();

            $this->streamContextOptions($context);

            set_error_handler([$this, 'errorHandler']);

            $content = $this->streamContextExec($context);

            restore_error_handler();

            $this->streamContextParseHeader();

            $this->response->body = $content;

            return $this->response;
        } catch (HttpException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new HttpException(null, 0, $e);
        } finally {
            $context = null;
        }
    }

    protected function streamContextOptions($context)
    {
        stream_context_set_option($context, ['http' => array_merge([
            'follow_location' => 1,
            'max_redirects' => 20,
            'timeout' => 30
        ], $this->options)]);
    }

    protected function streamContextExec($context)
    {
        return file_get_contents($this->uri->build(), false, $context);
    }

    protected function streamContextParseHeader()
    {
        $this->response->header->parse($http_response_header);

        $this->response->code = $this->response->header->code;
    }

    /**
     * Construit les parametres de la requete.
     *
     * @return $this
     */
    protected function buildParams()
    {
        if ($this->isPostMethod()) {
            if ($this->isJsonRequest()) {
                $this->header->set('Content-Type', 'application/json');
                $this->setOption('content', json_encode($this->params));
            } else {
                $this->header->set('Content-Type', 'application/x-www-form-urlencoded');
                $this->setOption('content', http_build_query($this->params));
            }

            return $this;
        } else {
            return $this->buildUrl();
        }
    }

    /**
     * Construit les headers de la requete.
     *
     * @return $this
     */
    protected function buildHeaders()
    {
        $headers = $this->header->build();

        return $this->setOption('header', implode(PHP_EOL, $headers));
    }

    /**
     * Construit le proxy de la requete
     *
     * @return $this
     */
    protected function buildProxy()
    {
        if (isset($this->proxy['host'])) {
            $uri = new Uri([
                'scheme' => 'tcp',
                'host' => $this->proxy['host'],
                'port' => isset($this->proxy['port']) ? $this->proxy['port'] : 80
            ]);

            if (isset($this->proxy['access'])) {
                $uri->user = $this->proxy['access'];
            }

            $this->setOption('proxy', $uri->build());
        }

        return $this;
    }

    /**
     * Construit les cookies de la requete
     *
     * @return $this
     */
    protected function buildCookies()
    {
        if (!empty($this->cookies)) {
            return $this->addHeader('Cookie', $this->getCookies(true));
        }

        return $this;
    }
}
