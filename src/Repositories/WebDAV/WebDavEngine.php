<?php declare(strict_types=1);

namespace JuanchoSL\VirtualDisk\Repositories\WebDAV;

use DOMDocument;
use JuanchoSL\Validators\Types\Entities\EntityValidations;
use JuanchoSL\Validators\Types\Strings\StringValidations;
use JuanchoSL\VirtualDisk\Adapters\ExtendedManipulator;
use JuanchoSL\VirtualDisk\Repositories\Common\CacheStatTrait;
use Psr\Http\Message\UriInterface;
use Fig\Http\Message\StatusCodeInterface;
use Fig\Http\Message\RequestMethodInterface;
use JuanchoSL\VirtualDisk\Contracts\RemoteHttpOrigin;
use JuanchoSL\VirtualDisk\Repositories\Common\FactoryTrait;
use JuanchoSL\VirtualDisk\Repositories\Common\ClientTrait;
use JuanchoSL\VirtualDisk\Contracts\DiskInterface;
use JuanchoSL\VirtualDisk\Contracts\EntityInfoInterface;
use JuanchoSL\VirtualDisk\Contracts\FileInterface;
use JuanchoSL\VirtualDisk\Repositories\AbstractRepository;

class WebDavEngine extends AbstractRepository implements DiskInterface, FileInterface, EntityInfoInterface, RemoteHttpOrigin
{

    use FactoryTrait, ClientTrait, CacheStatTrait;

    protected $token = '';

    protected string $status = '';

    public function __construct(UriInterface $uri)
    {
        $userinfo = $uri->getUserInfo();
        if (mb_strpos($userinfo, ":") !== false) {
            list($username, $password) = explode(":", $userinfo, 2);
            $this->token = "Basic " . base64_encode(urldecode($username) . ":" . urldecode($password));
        }
        $this->path = (string) $uri->withUserInfo('');
    }

    public function exists(string $path): bool
    {
        return $this->stat($path) !== false;
    }

    public function isDir(string $path): bool
    {
        $stat = $this->stat($path);
        $val = (new EntityValidations())->isKeyContaining('type')->isValueAttributeValidating('type', (new StringValidations())->isValueEquals('dir'));
        return $val($stat);
    }

    public function isFile(string $path): bool
    {
        $stat = $this->stat($path);
        $val = (new EntityValidations())->isKeyContaining('type')->isValueAttributeValidating('type', (new StringValidations())->isValueEquals('file'));
        return $val($stat);
    }

    public function rename(string $path, string $new): bool
    {
        $virtual_path = $this->virtualPath($path);
        if ($this->isDir($path)) {
            if (substr($path, -1) !== static::SEPARATOR) {
                $path .= static::SEPARATOR;
            }
            if (substr($virtual_path, -1) !== static::SEPARATOR) {
                $virtual_path .= static::SEPARATOR;
            }
            if (substr($new, -1) !== static::SEPARATOR) {
                $new .= static::SEPARATOR;
            }
            $new = rtrim($this->virtualPath(str_replace($path, $new, $path)), static::SEPARATOR) . static::SEPARATOR;
        } else {
            $env = clone $this;
            $new = (string) (new ExtendedManipulator($path))->replace($path, $new)->apply(function ($var) use ($env) {
                return $env->virtualPath($var);
            })->rtrim(static::SEPARATOR);
        }
        $req = $this->getRequestFactory()
            ->createRequest('MOVE', $this->getUriFactory()->createUri($virtual_path))
            ->withHeader("Authorization", $this->token)
            ->withHeader("Overwrite", 'T')
            ->withHeader("Destination", $this->getUriFactory()->createUri($new)->withUserInfo(''))
        ;
        $response = $this->getHttpClient()->sendRequest($req);
        $res = $this->setStatus($response, StatusCodeInterface::STATUS_NOT_FOUND * -1);
        if ($res) {
            $this->getCacheRepository()->delete(md5((string) $virtual_path));
        }
        return $res;
    }

    public function ls(string $path = '.'): iterable
    {
        if (substr($path, -1) !== static::SEPARATOR) {
            $path .= static::SEPARATOR;
        }
        $req = $this->getRequestFactory()
            ->createRequest(RequestMethodInterface::METHOD_GET, $this->getUriFactory()->createUri($this->virtualPath($path)))
            ->withHeader("Authorization", $this->token)
        ;
        $response = $this->getHttpClient()->sendRequest($req);
        $results = [];
        $dom = new DOMDocument();
        $dom->loadHTML((string) $response->getBody(), LIBXML_NOWARNING | LIBXML_NOERROR);
        $xpath = new \DOMXPath($dom);
        $data = $xpath->query("//a");
        foreach ($data as $a) {
            if (!in_array(trim($a->nodeValue, static::SEPARATOR), ['.', '..'])) {
                $results[] = $a->nodeValue;
            }
        }
        return $results;
    }

    public function write(string $contents, string $path): bool
    {
        $req = $this->getRequestFactory()
            ->createRequest(RequestMethodInterface::METHOD_PUT, $this->getUriFactory()->createUri($this->virtualPath($path)))
            ->withHeader("Overwrite", 'T')
            ->withHeader("Authorization", $this->token)
            ->withBody($this->stream_factory->createStream($contents))
        ;
        $response = $this->getHttpClient()->sendRequest($req);
        return $this->setStatus($response, StatusCodeInterface::STATUS_CREATED);
    }

    public function read(string $path): string|false
    {
        $req = $this->getRequestFactory()
            ->createRequest(RequestMethodInterface::METHOD_GET, $this->getUriFactory()->createUri($this->virtualPath($path)))
            ->withHeader("Authorization", $this->token)
        ;
        $response = $this->getHttpClient()->sendRequest($req)->getBody();
        return (string) $response;
    }

    public function mkdir(string $path): bool
    {
        if (substr($path, -1) !== static::SEPARATOR) {
            $path .= static::SEPARATOR;
        }

        $req = $this->getRequestFactory()
            ->createRequest('MKCOL', $this->getUriFactory()->createUri($this->virtualPath($path) . static::SEPARATOR))
            ->withHeader("Authorization", $this->token)
        ;

        $response = $this->getHttpClient()->sendRequest($req);
        return $this->setStatus($response, StatusCodeInterface::STATUS_CREATED);
    }

    public function rmdir(string $path): bool
    {
        if (substr($path, -1) !== static::SEPARATOR) {
            $path .= static::SEPARATOR;
        }
        $virtual_path = $this->virtualPath($path);
        $req = $this->getRequestFactory()
            ->createRequest(RequestMethodInterface::METHOD_DELETE, $this->getUriFactory()->createUri($virtual_path . static::SEPARATOR))
            ->withHeader("Authorization", $this->token)
        ;
        $response = $this->getHttpClient()->sendRequest($req);
        $res = $this->setStatus($response, StatusCodeInterface::STATUS_NO_CONTENT);
        if ($res) {
            $this->getCacheRepository()->delete(md5((string) $virtual_path));
        }
        return $res;
    }

    public function remove(string $path): bool
    {
        $virtual_path = $this->virtualPath($path);
        $req = $this->getRequestFactory()
            ->createRequest(RequestMethodInterface::METHOD_DELETE, $this->getUriFactory()->createUri($virtual_path))
            ->withHeader("Authorization", $this->token)
        ;
        $response = $this->getHttpClient()->sendRequest($req);
        $res = $this->setStatus($response, StatusCodeInterface::STATUS_NO_CONTENT);
        if ($res) {
            $this->getCacheRepository()->delete(md5((string) $virtual_path));
        }
        return $res;
    }

    public function stat(string $path): iterable|false
    {
        $virtual_path = $this->virtualPath($path);
        $req = $this->getRequestFactory()
            ->createRequest('PROPFIND', $this->getUriFactory()->createUri($virtual_path))
            ->withHeader("Authorization", $this->token)
        ;
        $cache = $this->getCacheRepository();
        $id = md5($virtual_path);
        $response = $cache->get($id, function () use ($req, $cache, $id) {
            $response = $this->getHttpClient()->sendRequest($req);
            $cache->set($id, $response, 300);
            return $response;
        });

        if (!$this->setStatus($response, StatusCodeInterface::STATUS_NOT_FOUND * -1)) {
            return false;
        }
        $dom = new DOMDocument();
        $dom->loadXML((string) $response->getBody());
        $length = $dom->getElementsByTagName('getcontentlength')->item(0);
        $name = $dom->getElementsByTagName('displayname')->item(0)->nodeValue;
        if (is_null($length)) {
            $name .= static::SEPARATOR;
        }
        return [
            'name' => $name,
            'modify' => strtotime($dom->getElementsByTagName('getlastmodified')->item(0)->nodeValue),
            'size' => $dom->getElementsByTagName('getcontentlength')->item(0)?->nodeValue,
            'type' => is_null($length) ? 'dir' : 'file',
        ];
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    protected function setStatus($response, int $required_code)
    {
        if (($required_code > 0 AND $response->getStatusCode() != $required_code) OR ($required_code < 0 AND $response->getStatusCode() == abs($required_code))) {
            $error = (string) $response->getBody();
            $this->status = $error;
            return false;
        }
        $this->status = '';
        return true;
    }
}