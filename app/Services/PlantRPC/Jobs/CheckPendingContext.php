<?php declare(strict_types=1);

namespace App\Services\PlantRPC\Jobs;

use App\Jobs\Traits\Measurable;
use App\Services\PlantRPC\PlantRPC;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class CheckPendingContext implements ShouldQueue
{
    use Queueable, Measurable;

    public function __construct(
        readonly public string $id,
    ) {
    }

    public function handle(PlantRPC $plantRPC): void
    {
        $plantRPC->checkPendingContext($this->id);
    }
}
