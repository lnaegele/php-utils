<?php
declare(strict_types=1);
namespace Jolutions\PhpUtils\BackgroundJob;

interface BackgroundJobInterface
{
    public function execute(): void;
}