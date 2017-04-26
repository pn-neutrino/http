<?php

namespace Neutrino\Http;

use Neutrino\Http\Parser\Parserize;

/**
 * Class Response
 */
class Response
{
    /** @var int|null */
    public $code;

    /** @var string */
    public $status;

    /** @var Header */
    public $header;

    /** @var string */
    public $body = '';

    /** @var mixed */
    public $data;

    /** @var int|null */
    public $errorCode;

    /** @var string|null */
    public $error;

    /** @var mixed */
    public $curlInfos;

    /**
     * Response constructor.
     *
     * @param \Neutrino\Http\Header $header
     */
    public function __construct(Header $header = null)
    {
        $this->header = $header === null ? new Header() : $header;
    }

    /**
     * Renvoie si la reponse est valide
     *
     * @return bool
     */
    public function isOk()
    {
        return $this->code >= 200 && $this->code < 300;
    }

    /**
     * Renvoie si la reponse est en erreur
     *
     * @return bool
     */
    public function isFail()
    {
        return $this->code < 200 && $this->code >= 300;
    }

    /**
     * Renvoie une erreur CURL est survenue
     *
     * @return bool
     */
    public function isError()
    {
        return $this->errorCode !== null || $this->errorCode !== 0;
    }

    /**
     * @param Parserize|string $parser
     *
     * @return mixed
     * @throws \RuntimeException
     */
    public function parse($parser)
    {
        if (is_string($parser)) {
            $parser = new $parser;
        }

        if ($parser instanceof Parserize) {
            return $this->data = $parser->parse($this->body);
        }

        throw new \RuntimeException(__METHOD__ . ' $parserize must implement ' . Parserize::class);
    }
}
