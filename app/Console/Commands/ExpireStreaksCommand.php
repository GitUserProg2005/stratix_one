<?php

namespace App\Console\Commands;

use App\Models\ChatStreak;
use Illuminate\Console\Command;

class ExpireStreaksCommand extends Command
{
    protected $signature = 'streak:expire';

    protected $description = 'Деактивирует стрики (огоньки), по которым не было активности 24+ часов';

    public function handle(): int
    {
        $expired = ChatStreak::where('last_activity_at', '<', now()->subHours(24))
            ->where('active', true)
            ->update(['active' => false]);

        $this->info("Expired {$expired} streaks.");

        return self::SUCCESS;
    }
}
