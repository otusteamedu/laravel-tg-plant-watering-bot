<?php declare(strict_types = 1);

namespace App\Jobs\Traits;

use App\Jobs\Middleware\Measure;

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
