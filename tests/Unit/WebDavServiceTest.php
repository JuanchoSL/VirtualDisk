<?php

namespace JuanchoSL\VirtualDisk\Tests\Unit;

use JuanchoSL\CurlClient\Wrappers\PsrCurlClient;
use JuanchoSL\HttpData\Factories\RequestFactory;
use JuanchoSL\HttpData\Factories\StreamFactory;
use JuanchoSL\HttpData\Factories\UriFactory;
use JuanchoSL\VirtualDisk\Repositories\WebDAV\WebDavEngine;


class WebDavServiceTest extends AbstractT
{

    public static function providerData(): array
    {
        return [
            "DAV" => [
                (new WebDavEngine((new UriFactory())->createUri(getenv("PATH_WEBDAV"))))
                    ->setRequestFactory(new RequestFactory())
                    ->setUriFactory(new UriFactory())
                    ->setStreamFactory(new StreamFactory())
                    ->setHttpClient(new PsrCurlClient())
            ]
        ];
    }
}