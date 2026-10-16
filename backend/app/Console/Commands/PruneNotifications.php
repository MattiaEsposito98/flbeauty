<?php

namespace App\Console\Commands;

use App\Support\SiteNotifier;
use Illuminate\Console\Command;

class PruneNotifications extends Command
{
    protected $signature = 'notifications:prune {--days= : Giorni da tenere (default: shop.notification_days)}';

    protected $description = 'Cancella le notifiche del sito (campanella) più vecchie del limite';

    public function handle(): int
    {
        $days = $this->option('days') !== null ? (int) $this->option('days') : null;

        $this->info(SiteNotifier::prune($days).' notifiche cancellate.');

        return self::SUCCESS;
    }
}
