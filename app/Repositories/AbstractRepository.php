<?php

declare(strict_types=1);

namespace App\Repositories;

abstract class AbstractRepository
{
    abstract public function beginTransaction(): void;
    abstract public function commitTransaction(): void;
    abstract public function rollbackTransaction(): void;
}
