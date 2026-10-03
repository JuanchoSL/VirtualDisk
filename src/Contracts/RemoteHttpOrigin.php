<?php declare(strict_types=1);

namespace JuanchoSL\VirtualDisk\Contracts;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;


interface RemoteHttpOrigin
{

    public function setUriFactory(UriFactoryInterface $uri_factory): static;
    public function setStreamFactory(StreamFactoryInterface $stream_factory): static;
    public function setRequestFactory(RequestFactoryInterface $request_factory): static;
    public function setHttpClient(ClientInterface $client): static;
}