<?php

/** @noinspection PhpUnusedParameterInspection */

declare(strict_types=1);

namespace Pixelgroup\HttpClient\Tests;

use Pixelgroup\HttpClient\ResponseHandlerInterface;
use Pixelgroup\HttpClient\Utils\HashUtil;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * @internal
 *
 * @small
 */
final class HashUtilTest extends TestCase
{
    /**
     * @throws \ReflectionException
     */
    public function testHashCallable(): void
    {
        $callable = static function (RequestInterface $request, ResponseInterface $response) {
            return $response->getBody()->getContents();
        };

        $callableStatic = static function (RequestInterface $request, ResponseInterface $response) {
            return $response->getBody()->getContents();
        };

        $responseHandler = new class() implements ResponseHandlerInterface {
            /**
             * @param RequestInterface  $request
             * @param ResponseInterface $response
             *
             * @return mixed
             */
            public function __invoke(RequestInterface $request, ResponseInterface $response)
            {
                return $response->getBody()->getContents();
            }
        };

        $hashes = [
            'callable' => $this->calcHash($callable),
            'callableStatic' => $this->calcHash($callableStatic),
            '[$this, callableMethod]' => $this->calcHash([$this, 'callableMethod']),
            '[__CLASS__, callableStaticMethod]' => $this->calcHash([__CLASS__, 'callableStaticMethod']),
            '__CLASS__::callableStaticMethod' => $this->calcHash(__CLASS__ . '::callableStaticMethod'),
            'trim' => $this->calcHash('trim'),
            '__NAMESPACE__.\callableFunction' => $this->calcHash(__NAMESPACE__ . '\callableFunction'),
            'new Handler()' => $this->calcHash(new Handler()),
            'anonymous class implements ResponseHandlerInterface{}' => $this->calcHash($responseHandler),
        ];

        foreach ($hashes as $hash) {
            self::assertNotEmpty($hash);

            if (method_exists($this, 'assertMatchesRegularExpression')) {
                /**
                 * @noinspection PhpUndefinedMethodInspection
                 * @noinspection RedundantSuppression
                 */
                self::assertMatchesRegularExpression('/^[\da-f]{8}$/', $hash);
            } else {
                /** @noinspection PhpDeprecationInspection */
                self::assertRegExp('/^[\da-f]{8}$/', $hash);
            }
        }

        self::assertSame($hashes['[__CLASS__, callableStaticMethod]'], $hashes['__CLASS__::callableStaticMethod']);
    }

    /**
     * @param callable $func
     *
     * @throws \ReflectionException
     *
     * @return string
     */
    private function calcHash(callable $func): string
    {
        $hash = HashUtil::hashCallable($func);
        $hash2 = HashUtil::hashCallable($func);

        self::assertSame($hash, $hash2);

        return $hash;
    }

    /**
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     *
     * @return string
     */
    public function callableMethod(RequestInterface $request, ResponseInterface $response): string
    {
        return $response->getBody()->getContents();
    }

    /**
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     *
     * @return string
     */
    public static function callableStaticMethod(RequestInterface $request, ResponseInterface $response): string
    {
        return $response->getBody()->getContents();
    }
}

/**
 * @param RequestInterface  $request
 * @param ResponseInterface $response
 *
 * @return string
 */
function callableFunction(RequestInterface $request, ResponseInterface $response): string
{
    return $response->getBody()->getContents();
}

/**
 * Class Handler.
 */
class Handler implements ResponseHandlerInterface
{
    /**
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     *
     * @return mixed
     */
    public function __invoke(RequestInterface $request, ResponseInterface $response)
    {
        return $response->getBody()->getContents();
    }
}
