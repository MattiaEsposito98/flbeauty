<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ComuniSeeder extends Seeder
{
    /**
     * Importa l'elenco dei comuni italiani da database/data/comuni.tsv
     * (nome, coordinate, provincia, regione, CAP). Le coordinate vengono dal DB
     * "midalot"; provincia/regione/CAP da una fonte open-source (comuni-json di
     * matteocontrini, incrociata per nome). Tutto versionato nel repo così non
     * dipende da risorse esterne presenti solo sulla macchina di sviluppo.
     */
    public function run(): void
    {
        $path = database_path('data/comuni.tsv');

        if (! file_exists($path)) {
            $this->command->error("File non trovato: {$path}");

            return;
        }

        DB::table('comuni')->delete();

        $handle = fopen($path, 'r');
        $now = now();
        $batch = [];

        while (($line = fgets($handle)) !== false) {
            [$name, $latitude, $longitude, $province, $region, $postalCodes] = explode("\t", rtrim($line, "\n"));

            $batch[] = [
                'name' => $name,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'province' => $province ?: null,
                'region' => $region ?: null,
                'postal_codes' => $postalCodes ? json_encode(explode(',', $postalCodes)) : null,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (count($batch) === 500) {
                DB::table('comuni')->insert($batch);
                $batch = [];
            }
        }

        if ($batch !== []) {
            DB::table('comuni')->insert($batch);
        }

        fclose($handle);

        $this->command->info('Comuni importati: '.DB::table('comuni')->count());
    }
}
