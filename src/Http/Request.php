<?php

namespace Neutrino\Http;

abstract class Request
{
    /*
     | Request Parameters
     */
    /**
     * Method HTTP
     *
     * @var string
     */
    protected $method;

    /**
     * Url de la requete
     *
     * @var Uri
     */
    protected $uri;

    /**
     * Parametres de la requete
     *
     * @var array
     */
    protected $params = [];

    /**
     * Header de la requete
     *
     * @var \Neutrino\Http\Header
     */
    protected $header;

    /**
     * Proxy de la requete
     *
     * @var array
     */
    protected $proxy = [];

    /**
     * Authentification (basic)
     *
     * @var array
     */
    protected $auth = [];

    /**
     * Cookies de la requete
     *
     * @var array
     */
    protected $cookies = [];

    /**
     * Options
     *
     * @var array
     */
    protected $options = [];

    /**
     * Flag spécifiant si l'on doit faire une requete JSON. Uniquement pour les methods HTTP POST, PUT, PATCH
     *
     * @var bool
     */
    protected $jsonRequest = false;

    /*
     | Response
     */
    /** @var \Neutrino\Http\Response */
    public $response;

    /**
     * Request constructor.
     *
     * @param \Neutrino\Http\Response|null $response
     * @param \Neutrino\Http\Header|null $header
     */
    public function __construct(Response $response = null, Header $header = null)
    {
        $this->header = $header === null ? new Header() : $header;
        $this->response = $response === null ? new Response() : $response;
    }

    /**
     * Retour l'url de la requete, construite avec les parametres si la method HTTP n'est pas POST, PUT, PATCH
     *
     * @return Uri
     */
    public function getUri()
    {
        if ($this->isPostMethod()) {
            return $this->uri;
        }

        return $this->buildUrl()->uri;
    }

    /**
     * Definie l'url de la requete
     *
     * @param string|Uri $uri
     *
     * @return $this
     */
    public function setUri($uri)
    {
        $this->uri = new Uri($uri);

        return $this;
    }

    /**
     * Retourne la method HTTP de la requete
     *
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * Definie la method HTTP de la requete
     *
     * @param string $method
     *
     * @return $this
     */
    public function setMethod($method)
    {
        $this->method = $method;

        return $this;
    }

    /**
     * Renvoie si l'on fais un appel HTTP basée sur le POST
     *
     * @return bool
     */
    protected function isPostMethod()
    {
        $method = $this->getMethod();

        return $method == 'POST' || $method == 'PUT' || $method == 'PATCH';
    }

    /**
     * Est-ce que l'on doit envoyer un body "json" contenant les parametres de la requete
     *
     * @return bool
     */
    public function isJsonRequest()
    {
        return $this->jsonRequest;
    }

    /**
     * Definie si l'on doit envoyer un body "json" contenant les parametres de la requete
     *
     * @param bool $jsonRequest
     *
     * @return $this
     */
    public function setJsonRequest($jsonRequest)
    {
        $this->jsonRequest = $jsonRequest;

        return $this;
    }

    /**
     * Retourne les parametres de la requete
     *
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Definie, ou ajoute, des parametres de la requete
     *
     * @param array $parameters
     * @param bool $merge Est-ce que l'on ajout les parametres aux parametres existant, ou les ecrases
     *
     * @return $this
     */
    public function setParams($parameters, $merge = false)
    {
        if ($merge) {
            $this->params = array_merge($this->params, $parameters);
        } else {
            $this->params = $parameters;
        }

        return $this;
    }

    /**
     * Ajout un parametre à la requete
     *
     * @param string $name
     * @param string|array $value
     *
     * @return $this
     */
    public function addParam($name, $value)
    {
        $this->params[$name] = $value;

        return $this;
    }

    /**
     * Construit l'url de la requete, Si la method HTTP n'est pas [POST, PUT, PATCH]
     *
     * @return $this
     */
    protected function buildUrl()
    {
        if ($this->isPostMethod()) {
            return $this;
        }

        return $this->extendUrl($this->params);
    }

    /**
     * Ajout des parametres en GET à l'url
     *
     * @param array $parameters
     *
     * @return $this
     */
    public function extendUrl(array $parameters = [])
    {
        $this->uri->extendQuery($parameters);

        return $this;
    }

    /**
     * Definie, ou ajoute, des headers à la requete
     *
     * @param array $headers
     * @param bool $merge Est-ce que l'on ajout les parametres aux parametres existant, ou les ecrases
     *
     * @return $this
     */
    public function setHeaders($headers, $merge = false)
    {
        $this->header->setHeaders($headers, $merge);

        return $this;
    }

    /**
     * Ajout un header à la requete
     *
     * @param string $name
     * @param string $value
     *
     * @return $this
     */
    public function addHeader($name, $value)
    {
        $this->header->set($name, $value);

        return $this;
    }

    /**
     * Retourne les informations de proxy
     *
     * @return array
     */
    public function getProxy()
    {
        return $this->proxy;
    }

    /**
     * Definie les informations de proxy
     *
     * @param string $host
     * @param string $port
     * @param string $access
     *
     * @return $this
     */
    public function setProxy($host, $port, $access)
    {
        $this->proxy = [
            'host' => $host,
            'port' => $port,
            'access' => $access,
        ];

        return $this;
    }

    /**
     * Retourne les informations d'authentification
     *
     * @return array
     */
    public function getAuth()
    {
        return $this->auth;
    }

    /**
     * Definie les informations d'authentification
     *
     * @param string $type
     * @param string $user
     * @param string $pass
     *
     * @return $this
     */
    public function setAuth($type, $user, $pass)
    {
        $this->auth = [
            'type' => $type,
            'user' => $user,
            'pass' => $pass,
        ];

        return $this;
    }

    /**
     * Construit les informations d'authentification de la requete
     *
     * Auth type:
     * - BASIC : Ajout le header 'Authorization'
     * - secretkeyv2 : Ajout le cookie contenant le hmac d'authentification
     *
     * @return $this
     */
    protected function buildAuth()
    {
        $auth = $this->auth;

        if (isset($auth['type'])) {
            switch ($auth['type']) {
                case 'basic':
                    $this->addHeader('Authorization', 'Basic ' . base64_encode($auth['user'] . ':' . $auth['pass']));
                    break;
                default:
                    // TODO
            }
        }

        return $this;
    }

    /**
     * Ajoute un cookie a la requete
     *
     * @param null|string $key
     * @param string $value
     *
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function addCookie($key, $value)
    {
        if (is_null($key)) {
            $this->cookies[] = $value;
        } else {
            $this->cookies[$key] = $value;
        }

        return $this;
    }

    /**
     * Retourne les cookies
     *
     * @param bool $format Retourne les cookies formatés
     *
     * @return array|string
     */
    public function getCookies($format = false)
    {
        if ($format) {
            return implode(';', $this->cookies);
        }

        return $this->cookies;
    }

    /**
     * Retourne les options
     *
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Definie, ou ajoute, des options
     *
     * @param array $options
     * @param bool $merge Est-ce que l'on ajoute les options aux options existantes, ou les ecrases
     *
     * @return $this
     */
    public function setOptions($options, $merge = false)
    {
        if ($merge) {
            $this->options = array_merge($this->options, $options);
        } else {
            $this->options = $options;
        }

        return $this;
    }

    /**
     * Ajout une option CURL
     *
     * @param string $name
     * @param string $value
     *
     * @return $this
     */
    public function setOption($name, $value)
    {
        $this->options[$name] = $value;

        return $this;
    }

    /**
     * @return \Neutrino\Http\Response
     */
    public function call()
    {
        $this
            ->buildParams()
            ->buildAuth()
            ->buildProxy()
            ->buildCookies()
            ->buildHeaders();

        return $this->makeCall();
    }

    /**
     * Definie le timeout de la requete
     *
     * @param int $timeout
     *
     * @return $this
     */
    abstract public function setTimeout($timeout);

    /**
     * @return \Neutrino\Http\Response
     */
    abstract protected function makeCall();

    /**
     * Construit les parametres de la requete.
     *
     * @return $this
     */
    abstract protected function buildParams();

    /**
     * Construit les headers de la requete.
     *
     * @return $this
     */
    abstract protected function buildHeaders();

    /**
     * Construit le proxy de la requete
     *
     * @return $this
     */
    abstract protected function buildProxy();

    /**
     * Construit les cookies de la requete
     *
     * @return $this
     */
    abstract protected function buildCookies();
}