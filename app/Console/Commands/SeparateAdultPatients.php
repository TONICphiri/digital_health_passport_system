<?php

namespace App\Console\Commands;

use App\Services\ChildSeparationService;
use Illuminate\Console\Command;

class SeparateAdultPatients extends Command
{
    protected $signature = 'patients:separate-adults';

    protected $description = 'Separate child records from the mother\'s profile when the child reaches adulthood';

    public function handle(ChildSeparationService $service): int
    {
        $count = $service->separateAdults();
        $this->info("{$count} records separated.");

        return self::SUCCESS;
    }
}
