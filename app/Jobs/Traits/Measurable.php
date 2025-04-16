<?php declare(strict_types = 1);

namespace App\Jobs\Traits;

use App\Jobs\Middleware\Measure;

/**
 * Трейт для задач (Job), время выполнения которых мы хотим видеть на графике
 */
trait Measurable
{
    /**
     * @return class-string[]
     */
    public function middleware(): array
    {
        return [
            Measure::class,
        ];
    }
}
