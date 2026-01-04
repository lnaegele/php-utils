<?php
declare(strict_types=1);
namespace Jolutions\PhpUtils\Cors\Application;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class CorsMiddleware
{
    public function __construct(
        private string $url,
    ) {}

    /**
     * Cors midleware
     *
     * @param ServerRequestInterface $request PSR-7 request
     * @param RequestHandlerInterface $handler PSR-15 request handler
     *
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        foreach (CorsMiddleware::getCorsHeadersFor($this->url) as $key => $value)
        {
            $response = $response->withHeader($key, $value);
        }

        return $response;
    }

    public static function getCorsHeadersFor($url): array
    {
        return [
            'Access-Control-Allow-Origin' => $url,
            'Access-Control-Allow-Headers' => '*', //X-Requested-With, Content-Type, Accept, Origin, Authorization',
            'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, PATCH, OPTIONS'
        ];
    }
}