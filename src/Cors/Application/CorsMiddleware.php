<?php
declare(strict_types=1);
namespace Jolutions\PhpUtils\Cors\Application;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

class CorsMiddleware
{
    public function __construct(
        private string $allowOrigin,
        private string $allowHeaders,
        private bool $allowCredentials = false,
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
        // Answer CORS preflight requests directly, as there are no OPTIONS routes
        // (routing them would raise a HttpMethodNotAllowedException that is logged as error for every preflight)
        $isPreflight = $request->getMethod()=='OPTIONS' && $request->hasHeader('Access-Control-Request-Method');
        $response = $isPreflight
            ? (new Response(204))->withHeader('Access-Control-Max-Age', '600') // let browsers cache the preflight result for 10 minutes
            : $handler->handle($request);

        foreach ($this->getCorsHeaders() as $key => $value)
        {
            $response = $response->withHeader($key, $value);
        }

        return $response;
    }

    public function getCorsHeaders(): array
    {
        return [
            'Access-Control-Allow-Origin' => $this->allowOrigin,
            'Access-Control-Allow-Headers' => $this->allowHeaders, // wildcard does not work in combination with allow-credentials true
            'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, PATCH, OPTIONS',
            'Access-Control-Allow-Credentials' => $this->allowCredentials ? 'true' : 'false',
        ];
    }
}