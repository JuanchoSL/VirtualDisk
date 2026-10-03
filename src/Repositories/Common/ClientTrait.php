<?php declare(strict_types=1);

namespace JuanchoSL\VirtualDisk\Repositories\Common;

use JuanchoSL\CurlClient\Wrappers\PsrCurlClient;
use JuanchoSL\Logger\Composers\TextComposer;
use JuanchoSL\Logger\Logger;
use JuanchoSL\Logger\Repositories\FileRepository;
use Psr\Http\Client\ClientInterface;

trait ClientTrait
{

    protected ClientInterface $http_client;

    public function getHttpClient(): ClientInterface
    {
        return $this->http_client ?? new PsrCurlClient();
    }

    public function setHttpClient(ClientInterface $http_client): static
    {
        $this->http_client = $http_client;
        return $this;
    }

}