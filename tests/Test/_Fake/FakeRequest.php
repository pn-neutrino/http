<?php

namespace Test\_Fake;

use Neutrino\Http\Request;

class FakeRequest extends Request
{
    public $called = [];

    /**
     * Definie le timeout de la requete
     *
     * @param int $timeout
     *
     * @return $this
     */
    public function setTimeout($timeout)
    {
        return $this->setOption('timeout', $timeout);
    }

    /**
     * @return \Neutrino\Http\Response
     */
    protected function makeCall()
    {
        $this->called[] = __FUNCTION__;

        $this->response->body = json_encode($this->options);

        return $this->response;
    }

    /**
     * Construit les parametres de la requete.
     *
     * @return $this
     */
    protected function buildParams()
    {
        $this->called[] = __FUNCTION__;

        if (!empty($this->params)) {

            if ($this->isPostMethod()) {
                if ($this->isJsonRequest()) {
                    return $this->setOption('params', json_encode($this->params));
                }

                return $this->setOption('params', $this->params);
            }

            $this->uri->extendQuery($this->params);
        }

        return $this;
    }

    /**
     * Construit les headers de la requete.
     *
     * @return $this
     */
    protected function buildHeaders()
    {
        $this->called[] = __FUNCTION__;

        return $this->setOption('headers', $this->header->build());
    }

    /**
     * Construit le proxy de la requete
     *
     * @return $this
     */
    protected function buildProxy()
    {
        $this->called[] = __FUNCTION__;

        return $this->setOption('proxy', $this->proxy);
    }

    /**
     * Construit les cookies de la requete
     *
     * @return $this
     */
    protected function buildCookies()
    {
        $this->called[] = __FUNCTION__;

        return $this->setOption('cookies', $this->getCookies(true));
    }

    protected function buildAuth()
    {
        $this->called[] = __FUNCTION__;

        return parent::buildAuth();
    }

    public function extendUrl(array $parameters = [])
    {
        $this->called[] = __FUNCTION__;

        return parent::extendUrl($parameters);
    }

}