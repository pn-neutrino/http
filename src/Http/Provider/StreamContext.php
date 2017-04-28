<?php

namespace Neutrino\Http\Provider;

use Neutrino\Http\Exception as HttpException;
use Neutrino\Http\Header;
use Neutrino\Http\Provider\Exception as ProviderException;
use Neutrino\Http\Request;
use Neutrino\Http\Response;
use Neutrino\Http\Standard\Method;
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

    public function __construct()
    {
        self::checkAvailability();

        parent::__construct();
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
     *
     * @throws HttpException
     */
    protected function errorHandler($errno, $errstr)
    {
        $this->response->error     = $errstr;
        $this->response->errorCode = $errno;

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

            $this->response->body = $content;

            return $this->response;
        } finally {
            $context = null;
        }
    }

    protected function streamContextOptions($context)
    {
        stream_context_set_option($context, ['http' => array_merge([
            'follow_location' => 1,
            'max_redirects'   => 20,
            'timeout'         => 30,
            'ignore_errors'   => true,
        ], $this->options, ['method' => $this->method])]);
    }

    protected function streamContextExec($context)
    {
        if ($this->method !== Method::HEAD) {
            $content = file_get_contents($this->uri->build(), false, $context);

            $this->streamContextParseHeader($http_response_header);

            return $content;
        }

        try {
            $handler = fopen($this->uri->build(), 'r', null, $context);

            $this->streamContextParseHeader($http_response_header);

            return '';
        } finally {
            if (isset($handler) && is_resource($handler)) {
                fclose($handler);
            }
        }
    }

    protected function streamContextParseHeader($headers)
    {
        $this->response->header->parse($headers);

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
                return $this
                    ->setHeader('Content-Type', 'application/json')
                    ->setOption('content', json_encode($this->params));
            }

            return $this
                ->setHeader('Content-Type', 'application/x-www-form-urlencoded')
                ->setOption('content', http_build_query($this->params));
        }

        return $this->buildUrl();
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
                'host'   => $this->proxy['host'],
                'port'   => isset($this->proxy['port']) ? $this->proxy['port'] : 80
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
            return $this->setHeader('Cookie', $this->getCookies(true));
        }

        return $this;
    }
}
