<?php

namespace Neutrino\Http\Provider;

use Neutrino\Http\Exception as HttpException;
use Neutrino\Http\Provider\Exception as ProviderException;
use Neutrino\Http\Header;
use Neutrino\Http\Standard\Method;
use Neutrino\Http\Request;
use Neutrino\Http\Response;

class Curl extends Request
{
    private static $isAvailable;

    public static function checkAvailability()
    {
        if (!isset(self::$isAvailable)) {
            self::$isAvailable = extension_loaded('curl');
        }

        if (!self::$isAvailable) {
            throw new ProviderException(self::class . ' require curl extension');
        }
    }

    /**
     * Curl constructor.
     *
     * @param \Neutrino\Http\Response|null $response
     * @param \Neutrino\Http\Header|null $header
     */
    public function __construct(Response $response = null, Header $header = null)
    {
        self::checkAvailability();

        parent::__construct($response, $header);
    }

    /**
     * Definie le timeout de la requete
     * Applique l'option CURL 'CURLOPT_TIMEOUT'
     *
     * @param int $timeout
     *
     * @return $this
     */
    public function setTimeout($timeout)
    {
        return $this->setOption(CURLOPT_TIMEOUT, $timeout);
    }

    /**
     * Definie le timeout de connexion de la requete
     * Applique l'option CURL 'CURLOPT_CONNECTTIMEOUT'
     *
     * @param int $timeout
     *
     * @return $this
     */
    public function setConnectTimeout($timeout)
    {
        return $this->setOption(CURLOPT_CONNECTTIMEOUT, $timeout);
    }

    /**
     * @return \Neutrino\Http\Response
     * @throws \Exception
     */
    protected function makeCall()
    {
        try {
            $ch = curl_init();

            $this->curlOptions($ch);

            $this->curlExec($ch);

            $this->curlInfos($ch);

            if ($this->response->errorCode) {
                throw new HttpException($this->response->error, $this->response->errorCode);
            }

            return $this->response;
        } finally {
            if (isset($ch) && is_resource($ch)) {
                curl_close($ch);
            }
        }
    }

    protected function curlOptions($ch)
    {
        $method = $this->method;

        if ($method === Method::HEAD) {
            curl_setopt($ch, CURLOPT_NOBODY, true);
        }

        // Default Options
        curl_setopt_array($ch,
            [
                CURLOPT_URL => $this->uri->build(),
                CURLOPT_CUSTOMREQUEST => $method,
                CURLOPT_AUTOREFERER => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 20,
                CURLOPT_HEADER => false,
                CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
                CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
                CURLOPT_CONNECTTIMEOUT => 30,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HEADERFUNCTION => [$this, 'curlHeaderFunction'],
            ]);

        curl_setopt_array($ch, $this->options);
    }

    /**
     * @param resource $ch
     *
     * @return void
     */
    protected function curlExec($ch)
    {
        $result = curl_exec($ch);

        $this->response->body = $result;
    }

    protected function curlHeaderFunction($ch, $raw)
    {
        if ($this->response->code === null) {
            $this->response->code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        }

        $this->response->header->parse($raw);

        return strlen($raw);
    }

    public function curlInfos($ch)
    {
        if ($this->response->code === null) {
            $this->response->code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        }

        if (($errno = curl_errno($ch)) !== 0) {
            $this->response->errorCode = curl_errno($ch);
            $this->response->error = curl_error($ch);
        }

        $this->response->curlInfos = curl_getinfo($ch);
    }

    /**
     * Construit les parametres de la requete.
     * HTTP [POST, PUT, PATCH] : Applique l'option CURL "CURLOPT_POSTFIELDS"
     * HTTP [...] : Contruit l'url de la requete
     *
     * @return $this
     */
    protected function buildParams()
    {
        if ($this->isPostMethod()) {
            if ($this->isJsonRequest()) {
                return $this
                    ->setOption(CURLOPT_POSTFIELDS, json_encode($this->params))
                    ->addHeader('Content-Type', 'application/json');
            } else {
                return $this
                    ->setOption(CURLOPT_POSTFIELDS, http_build_query($this->params))
                    ->addHeader('Content-Type', 'application/x-www-form-urlencoded');
            }
        } else {
            return $this->buildUrl();
        }
    }

    /**
     * Construit les cookies de la requete
     * Applique l'option CURL 'CURLOPT_COOKIE'
     *
     * @return $this
     */
    protected function buildCookies()
    {
        if (!empty($this->cookies)) {
            return $this->setOption(CURLOPT_COOKIE, $this->getCookies(true));
        }

        return $this;
    }

    /**
     * Construit les headers de la requete.
     * Applique l'option CURL "CURLOPT_HTTPHEADER"
     *
     * @return $this
     */
    protected function buildHeaders()
    {
        if (!empty($this->header->getHeaders())) {
            return $this->setOption(CURLOPT_HTTPHEADER, $this->header->build());
        }

        return $this;
    }

    /**
     * Definie le proxy de la requete
     * Applique les options CURL : CURLOPT_PROXY, CURLOPT_PROXYPORT, CURLOPT_PROXYUSERPWD
     *
     * @return $this
     */
    protected function buildProxy()
    {
        if (isset($this->proxy['host'])) {
            $this
                ->setOption(CURLOPT_PROXY, $this->proxy['host'])
                ->setOption(CURLOPT_PROXYPORT, $this->proxy['port']);

            if (isset($this->proxy['access'])) {
                $this->setOption(CURLOPT_PROXYUSERPWD, $this->proxy['access']);
            }
        }

        return $this;
    }
}