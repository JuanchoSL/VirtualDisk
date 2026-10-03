<?php declare(strict_types=1);

namespace JuanchoSL\VirtualDisk\Repositories\Common;

use JuanchoSL\HttpData\Factories\RequestFactory;
use JuanchoSL\HttpData\Factories\StreamFactory;
use JuanchoSL\HttpData\Factories\UriFactory;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;

trait FactoryTrait
{

    protected RequestFactoryInterface $request_factory;
    protected UriFactoryInterface $uri_factory;
    protected StreamFactoryInterface $stream_factory;


    public function getStreamFactory(): StreamFactoryInterface
    {
        return $this->stream_factory ?? new StreamFactory();
    }

    public function getUriFactory(): UriFactoryInterface
    {
        return $this->uri_factory ?? new UriFactory();
    }

    public function getRequestFactory(): RequestFactoryInterface
    {
        return $this->request_factory ?? new RequestFactory();
    }

    public function setStreamFactory(StreamFactoryInterface $stream_factory): static
    {
        $this->stream_factory = $stream_factory;
        return $this;
    }

    public function setUriFactory(UriFactoryInterface $uri_factory): static
    {
        $this->uri_factory = $uri_factory;
        return $this;
    }

    public function setRequestFactory(RequestFactoryInterface $request_factory): static
    {
        $this->request_factory = $request_factory;
        return $this;
    }

}