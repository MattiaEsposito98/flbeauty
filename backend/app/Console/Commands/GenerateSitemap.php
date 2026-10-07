<?php

namespace App\Console\Commands;

use App\Support\Sitemap;
use Illuminate\Console\Command;

class GenerateSitemap extends Command
{
    protected $signature = 'sitemap:generate';

    protected $description = 'Genera sitemap.xml (home, categorie, prodotti attivi) per Google';

    public function handle(): int
    {
        $path = config('app.sitemap_path') ?: public_path('sitemap.xml');

        if (! is_dir(dirname($path)) || ! is_writable(dirname($path))) {
            $this->error('Cartella non scrivibile: '.dirname($path).' (controlla SITEMAP_PATH nel .env)');

            return self::FAILURE;
        }

        file_put_contents($path, Sitemap::xml());

        $this->info('Sitemap scritta in '.$path);

        return self::SUCCESS;
    }
}
