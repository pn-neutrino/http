<?php

namespace Neutrino\Psr7;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

/**
 * Class Request
 *
 * @package     Neutrino\Psr7
 */
class Request implements RequestInterface
{
    /** @var string */
    protected $version;

    /** @var  Headers */
    protected $headers;

    /** @var  StreamInterface */
    protected $body;

    /** @var string */
    protected $target;

    /** @var string */
    protected $method;

    /** @var UriInterface */
    protected $uri;

    public function __construct()
    {
        $this->headers = new Headers();
    }

    /**
     * @inheritdoc
     */
    public function getProtocolVersion()
    {
        return $this->version;
    }

    /**
     * @inheritdoc
     */
    public function withProtocolVersion($version)
    {
        $this->version = $version;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getHeaders()
    {
        return $this->headers->all();
    }

    /**
     * @inheritdoc
     */
    public function hasHeader($name)
    {
        return $this->headers->has($name);
    }

    /**
     * @inheritdoc
     */
    public function getHeader($name, $default = null)
    {
        return $this->headers->get($name, $default);
    }

    /**
     * @inheritdoc
     */
    public function getHeaderLine($name, $default = null)
    {
        return $this->headers->line($name, $default);
    }

    /**
     * @inheritdoc
     */
    public function withHeader($name, $value)
    {
        $this->headers->set($name, is_array($value) ? $value : [$value]);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function withAddedHeader($name, $value)
    {
        if (is_array($value)) {
            foreach ($value as $item) {
                $this->headers->add($name, $item);
            }
        } else {
            $this->headers->add($name, $value);
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function withoutHeader($name)
    {
        $this->headers->remove($name);

        return $this;
    }

    /**
     * Gets the body of the message.
     *
     * @return StreamInterface Returns the body as a stream.
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Return an instance with the specified message body.
     *
     * The body MUST be a StreamInterface object.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return a new instance that has the
     * new body stream.
     *
     * @param StreamInterface $body Body.
     *
     * @return static
     * @throws \InvalidArgumentException When the body is not valid.
     */
    public function withBody(StreamInterface $body)
    {
        // TODO throws \InvalidArgumentException When the body is not valid.
        $this->body = $body;

        return $this;
    }

    /**
     * Retrieves the message's request target.
     *
     * Retrieves the message's request-target either as it will appear (for
     * clients), as it appeared at request (for servers), or as it was
     * specified for the instance (see withRequestTarget()).
     *
     * In most cases, this will be the origin-form of the composed URI,
     * unless a value was provided to the concrete implementation (see
     * withRequestTarget() below).
     *
     * If no URI is available, and no request-target has been specifically
     * provided, this method MUST return the string "/".
     *
     * @return string
     */
    public function getRequestTarget()
    {
        if (isset($this->target)) {
            return $this->target;
        }

        // TODO Uri Check

        return "/";
    }

    /**
     * Return an instance with the specific request-target.
     *
     * If the request needs a non-origin-form request-target — e.g., for
     * specifying an absolute-form, authority-form, or asterisk-form —
     * this method may be used to create an instance with the specified
     * request-target, verbatim.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * changed request target.
     *
     * @link http://tools.ietf.org/html/rfc7230#section-5.3 (for the various
     *     request-target forms allowed in request messages)
     *
     * @param mixed $requestTarget
     *
     * @return static
     */
    public function withRequestTarget($requestTarget)
    {
        $this->target = $requestTarget;

        return $this;
    }

    /**
     * Retrieves the HTTP method of the request.
     *
     * @return string Returns the request method.
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * Return an instance with the provided HTTP method.
     *
     * While HTTP method names are typically all uppercase characters, HTTP
     * method names are case-sensitive and thus implementations SHOULD NOT
     * modify the given string.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * changed request method.
     *
     * @param string $method Case-sensitive method.
     *
     * @return static
     * @throws \InvalidArgumentException for invalid HTTP methods.
     */
    public function withMethod($method)
    {
        // TODO Check Valid HTTP Method

        $this->method = $method;

        return $this;
    }

    /**
     * Retrieves the URI instance.
     *
     * This method MUST return a UriInterface instance.
     *
     * @link http://tools.ietf.org/html/rfc3986#section-4.3
     * @return UriInterface Returns a UriInterface instance
     *     representing the URI of the request.
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * Returns an instance with the provided URI.
     *
     * This method MUST update the Host header of the returned request by
     * default if the URI contains a host component. If the URI does not
     * contain a host component, any pre-existing Host header MUST be carried
     * over to the returned request.
     *
     * You can opt-in to preserving the original state of the Host header by
     * setting `$preserveHost` to `true`. When `$preserveHost` is set to
     * `true`, this method interacts with the Host header in the following ways:
     *
     * - If the Host header is missing or empty, and the new URI contains
     *   a host component, this method MUST update the Host header in the returned
     *   request.
     * - If the Host header is missing or empty, and the new URI does not contain a
     *   host component, this method MUST NOT update the Host header in the returned
     *   request.
     * - If a Host header is present and non-empty, this method MUST NOT update
     *   the Host header in the returned request.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * new UriInterface instance.
     *
     * @link http://tools.ietf.org/html/rfc3986#section-4.3
     *
     * @param UriInterface $uri          New request URI to use.
     * @param bool         $preserveHost Preserve the original state of the Host header.
     *
     * @return static
     */
    public function withUri(UriInterface $uri, $preserveHost = false)
    {
        if (!$preserveHost && !empty(($host = $uri->getHost()))) {
            $this->headers->set('Host', [$host]);
        }

        return $this;
    }
}
