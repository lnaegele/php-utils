<?php
declare(strict_types=1);
namespace Jolutions\PhpUtils\UserFriendlyError\Application;

use Exception;

class UserFriendlyException extends Exception {
    private int $statusCode;

    function __construct(string $message, int $statusCode = 500) {
        parent::__construct($message);
        $this->statusCode = $statusCode;
    }

    public function getStatusCode() : int {
        return $this->statusCode;
    }
}