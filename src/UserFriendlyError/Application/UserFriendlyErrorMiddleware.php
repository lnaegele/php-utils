<?php
declare(strict_types=1);
namespace Jolutions\PhpUtils\UserFriendlyError\Application;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class UserFriendlyErrorMiddleware
{
    public function __construct(
        private ResponseFactoryInterface $responseFactory) {}

    /**
     * UserFriendly error midleware
     *
     * @param ServerRequestInterface $request PSR-7 request
     * @param RequestHandlerInterface $handler PSR-15 request handler
     *
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (UserFriendlyException $e) {
             $response = $this->responseFactory->createResponse($e->getStatusCode())->withHeader('Content-type', 'application/json');
             $payload = ['error' => $e->getMessage()];
             $response->getBody()->write(json_encode($payload, JSON_UNESCAPED_UNICODE));
             return $response;
        }
    }
}