<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Barter;
use Carbon\Carbon;

class CleanupRejectedBarters extends Command
{
    protected $signature = 'barters:cleanup';
    protected $description = 'Clean up rejected barters older than 30 days';

    public function handle()
    {
        $count = Barter::where('status', 'rejected')
            ->where('updated_at', '<', Carbon::now()->subDays(30))
            ->delete();

        $this->info("Cleaned up {$count} rejected barters");
        return 0;
    }
}