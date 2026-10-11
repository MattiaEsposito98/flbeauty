<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use Faker\Factory as FakerFactory;
use Faker\Generator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Catalogo di prova IN ITALIANO per il database LOCALE: categorie, marche, prodotti con varianti e
 * immagini segnaposto (disegnate con GD, senza bisogno di internet). Faker (it_IT) genera prezzi,
 * scorte e quali varianti avere; nomi e descrizioni sono scritti a mano perché Faker in italiano
 * produce testo latino.
 *
 * ATTENZIONE: prima elimina i vecchi prodotti di prova (nomi latini/inglesi) e gli ordini di
 * prova che li contengono. Non gira mai in produzione. Uso: php artisan db:seed --class=ItalianCatalogSeeder
 */
class ItalianCatalogSeeder extends Seeder
{
    /** Prodotti da NON toccare (nomi italiani con foto vere). */
    private const KEEP = ['Lenzuolo bianco'];

    private Generator $faker;

    public function run(): void
    {
        abort_if(app()->isProduction(), 1, 'Questo seeder è solo per il database locale.');

        $this->faker = FakerFactory::create('it_IT');

        $this->removeOldDemoData();

        $categories = collect(['Trucco viso', 'Trucco occhi', 'Trucco labbra', 'Skincare', 'Profumeria', 'Capelli', 'Corpo e bagno', 'Unghie', 'Accessori beauty'])
            ->mapWithKeys(fn (string $name) => [$name => Category::firstOrCreate(['name' => $name], ['description' => null, 'is_active' => true])]);

        $brands = collect(['Yara', 'Bella Rosa', 'Aurora', 'Dolce Vita', 'Lumina', 'Mediterraneo'])
            ->mapWithKeys(fn (string $name) => [$name => Brand::firstOrCreate(['name' => $name])]);

        foreach ($this->catalog() as [$category, $brand, $name, $description, $min, $max, $variantLabel, $variants]) {
            $this->createProduct($categories[$category], $brand ? $brands[$brand] : null, $name, $description, $min, $max, $variantLabel, $variants);
        }

        $this->command?->info('Catalogo italiano creato: '.Product::count().' prodotti, '.Category::count().' categorie, '.Brand::count().' marche.');
    }

    private function removeOldDemoData(): void
    {
        DB::transaction(function () {
            $old = Product::whereNotIn('name', self::KEEP)->get();

            // Le righe d'ordine bloccano la cancellazione dei prodotti: via gli ordini di prova che li contengono.
            Order::whereHas('items', fn ($q) => $q->whereIn('product_id', $old->pluck('id')))->get()->each->delete();

            foreach ($old as $product) {
                foreach ($product->images ?? [] as $path) {
                    Storage::disk('public')->delete($path);
                }
                $product->delete();
            }

            Brand::whereIn('name', ['Glam Test'])->delete();
        });
    }

    private function createProduct(Category $category, ?Brand $brand, string $name, string $description, float $min, float $max, ?string $variantLabel, array $variants): void
    {
        $price = (float) $this->faker->numberBetween((int) $min, (int) $max) + $this->faker->randomElement([0.50, 0.90, 0.90, 0.00]);

        $product = Product::create([
            'category_id' => $category->id,
            'brand_id' => $brand?->id,
            'name' => $name,
            'description' => $description,
            'variant_label' => $variants ? $variantLabel : null,
            'images' => [
                $this->makeImage($name, $brand?->name, 0),
                $this->makeImage($name, $brand?->name, 1),
            ],
            'price' => $price,
            'stock' => $variants ? 0 : $this->faker->numberBetween(0, 40),
            'is_active' => $this->faker->boolean(92),
        ]);

        foreach ($this->faker->randomElements($variants, min(count($variants), $this->faker->numberBetween(2, max(2, count($variants))))) as $i => $variant) {
            $product->variants()->create([
                'name' => $variant,
                'price' => $this->faker->boolean(15) ? $price + 1 : null,
                'stock' => $this->faker->numberBetween(0, 15),
                'is_active' => true,
                'sort_order' => $i + 1,
            ]);
        }
    }

    /** Immagine segnaposto 900×1125: sfumatura nei toni rosa/pesca con il nome del prodotto. */
    private function makeImage(string $name, ?string $brand, int $style): string
    {
        $w = 900;
        $h = 1125;
        $img = imagecreatetruecolor($w, $h);

        $hue = $this->faker->numberBetween(335, 385) % 360;   // dal rosa al pesca
        [$r1, $g1, $b1] = $this->hsl($hue, 0.55, $style === 0 ? 0.90 : 0.82);
        [$r2, $g2, $b2] = $this->hsl($hue + 12, 0.50, $style === 0 ? 0.76 : 0.66);

        for ($y = 0; $y < $h; $y++) {
            $t = $y / $h;
            imagefilledrectangle($img, 0, $y, $w, $y, imagecolorallocate(
                $img,
                (int) ($r1 + ($r2 - $r1) * $t),
                (int) ($g1 + ($g2 - $g1) * $t),
                (int) ($b1 + ($b2 - $b1) * $t)
            ));
        }

        $ink = imagecolorallocate($img, 110, 56, 70);
        $white = imagecolorallocatealpha($img, 255, 255, 255, 40);
        imagefilledellipse($img, $w / 2, 470, 360, 360, $white);

        $font = 'C:/Windows/Fonts/arial.ttf';
        $lines = explode("\n", wordwrap($name, 18, "\n", true));
        $y = 800;
        foreach ($lines as $line) {
            $box = imagettfbbox(44, 0, $font, $line);
            imagettftext($img, 44, 0, (int) (($w - ($box[2] - $box[0])) / 2), $y, $ink, $font, $line);
            $y += 64;
        }
        if ($brand) {
            $box = imagettfbbox(28, 0, $font, mb_strtoupper($brand));
            imagettftext($img, 28, 0, (int) (($w - ($box[2] - $box[0])) / 2), $y + 30, $ink, $font, mb_strtoupper($brand));
        }

        $path = 'products/images/'.Str::ulid().'.jpg';
        Storage::disk('public')->makeDirectory('products/images');
        imagejpeg($img, Storage::disk('public')->path($path), 82);
        imagedestroy($img);

        return $path;
    }

    /** @return array{0:int,1:int,2:int} */
    private function hsl(float $h, float $s, float $l): array
    {
        $h = fmod($h, 360) / 360;
        $q = $l < 0.5 ? $l * (1 + $s) : $l + $s - $l * $s;
        $p = 2 * $l - $q;
        $f = function ($t) use ($p, $q) {
            $t = fmod($t + 1, 1.0);

            return match (true) {
                $t < 1 / 6 => $p + ($q - $p) * 6 * $t,
                $t < 1 / 2 => $q,
                $t < 2 / 3 => $p + ($q - $p) * (2 / 3 - $t) * 6,
                default => $p,
            };
        };

        return [(int) round($f($h + 1 / 3) * 255), (int) round($f($h) * 255), (int) round($f($h - 1 / 3) * 255)];
    }

    /** [categoria, marca, nome, descrizione, prezzo min, prezzo max, etichetta varianti, varianti possibili] */
    private function catalog(): array
    {
        $nudeShades = ['Nude rosato', 'Beige chiaro', 'Beige dorato', 'Sabbia', 'Caramello', 'Ambra'];
        $lipShades = ['Rosa antico', 'Corallo', 'Rosso ciliegia', 'Malva', 'Pesca', 'Bordeaux', 'Nude caldo'];
        $eyeShades = ['Champagne', 'Bronzo', 'Rosa perlato', 'Grigio fumo', 'Verde salvia', 'Nero intenso'];
        $nailShades = ['Rosa cipria', 'Rosso rubino', 'Lilla', 'Bianco latte', 'Pesca', 'Nude'];
        $scents = ['Vaniglia', 'Fiori di pesco', 'Agrumi di Sicilia', 'Rosa e muschio', 'Lavanda', 'Cocco'];

        return [
            ['Trucco viso', 'Bella Rosa', 'Fondotinta effetto seta', 'Fondotinta fluido a coprenza modulabile, dona un incarnato uniforme e luminoso. Texture leggera che non appesantisce, tenuta fino a 12 ore.', 9, 18, 'Tonalità', $nudeShades],
            ['Trucco viso', 'Aurora', 'Cipria compatta traslucida', 'Cipria setosa che opacizza senza seccare e sfuma le piccole imperfezioni. Pratica da portare in borsa, con specchio e piumino.', 6, 12, null, []],
            ['Trucco viso', 'Dolce Vita', 'Blush in crema pesca', 'Blush cremoso dal finish naturale: si sfuma con le dita e regala un colorito sano, anche sulle pelli più mature.', 7, 14, 'Colore', ['Pesca', 'Rosa petalo', 'Corallo']],
            ['Trucco viso', 'Lumina', 'Illuminante liquido perla', 'Poche gocce da mescolare al fondotinta o da applicare sugli zigomi per un effetto «pelle baciata dal sole».', 8, 15, null, []],
            ['Trucco occhi', 'Yara', 'Mascara volume intenso', 'Scovolino a clessidra che separa e allunga le ciglia senza grumi. Formula nera intensa, resistente alle lacrime.', 6, 13, null, []],
            ['Trucco occhi', 'Bella Rosa', 'Palette ombretti Tramonto', 'Dodici ombretti opachi e satinati dalle tonalità calde, per look dal giorno alla sera. Ottima pigmentazione e facili da sfumare.', 12, 24, null, []],
            ['Trucco occhi', 'Aurora', 'Matita occhi waterproof', 'Matita a lunga tenuta con mina morbida, perfetta per la rima interna. Resiste ad acqua e sudore.', 3, 8, 'Colore', ['Nero', 'Marrone', 'Blu notte', 'Verde bosco']],
            ['Trucco occhi', 'Lumina', 'Ombretto liquido glitter', 'Ombretto liquido con glitter fini, si applica con il pennellino e asciuga in pochi secondi.', 5, 11, 'Colore', $eyeShades],
            ['Trucco labbra', 'Yara', 'Lip gloss brillante', 'Gloss non appiccicoso dall\'effetto volume, con un delicato profumo di vaniglia. Labbra morbide e luminose tutto il giorno.', 2, 6, 'Colore', $lipShades],
            ['Trucco labbra', 'Dolce Vita', 'Rossetto cremoso a lunga durata', 'Colore pieno e confortevole in un solo passaggio, arricchito con burro di karité per non seccare le labbra.', 6, 14, 'Colore', $lipShades],
            ['Trucco labbra', 'Bella Rosa', 'Matita labbra definizione', 'Matita dalla punta precisa per contornare e riempire, evita che il rossetto sbavi.', 3, 7, 'Colore', ['Nude', 'Rosa', 'Rosso', 'Prugna']],
            ['Skincare', 'Mediterraneo', 'Crema viso idratante all\'olio d\'oliva', 'Crema giorno ricca di antiossidanti, idrata a fondo e lascia la pelle morbida e luminosa. Adatta a tutti i tipi di pelle.', 11, 24, null, []],
            ['Skincare', 'Aurora', 'Siero viso all\'acido ialuronico', 'Siero leggero ad assorbimento rapido che riempie le linee sottili e dona freschezza. Da usare mattina e sera prima della crema.', 14, 29, null, []],
            ['Skincare', 'Lumina', 'Maschera viso purificante all\'argilla', 'Maschera all\'argilla bianca che deterge in profondità e riduce la lucidità. Basta una posa di 10 minuti.', 5, 12, null, []],
            ['Skincare', 'Mediterraneo', 'Acqua micellare struccante', 'Rimuove trucco e impurità in un solo gesto, senza bisogno di risciacquo. Non unge e rispetta la pelle sensibile.', 4, 10, null, []],
            ['Profumeria', 'Dolce Vita', 'Eau de parfum Fiori d\'arancio', 'Fragranza fresca e solare con note di fiori d\'arancio, bergamotto e muschio bianco. Flacone da 50 ml.', 18, 39, null, []],
            ['Profumeria', 'Bella Rosa', 'Profumo Rosa e vaniglia', 'Un profumo avvolgente e femminile che unisce la delicatezza della rosa alla dolcezza della vaniglia. Flacone da 30 ml.', 14, 32, null, []],
            ['Capelli', 'Mediterraneo', 'Shampoo nutriente all\'argan', 'Shampoo delicato all\'olio di argan che districa e rende i capelli lucenti e più facili da pettinare.', 5, 12, null, []],
            ['Capelli', 'Aurora', 'Maschera capelli ristrutturante', 'Trattamento intensivo per capelli secchi o colorati: nutre, ripara le doppie punte e dona morbidezza.', 7, 16, null, []],
            ['Capelli', 'Lumina', 'Olio capelli lucidante', 'Poche gocce sulle lunghezze per controllare il crespo e dare un effetto setoso e brillante senza appesantire.', 6, 14, null, []],
            ['Corpo e bagno', 'Dolce Vita', 'Crema corpo profumata', 'Crema corpo dalla texture burrosa che idrata per 24 ore e lascia sulla pelle un profumo delicato.', 6, 15, 'Profumo', $scents],
            ['Corpo e bagno', 'Mediterraneo', 'Bagnoschiuma agli agrumi', 'Bagnoschiuma fresco ed energizzante a base di estratti di limone e arancia, per una doccia che risveglia.', 3, 8, 'Profumo', ['Limone', 'Arancia', 'Bergamotto']],
            ['Corpo e bagno', 'Bella Rosa', 'Scrub corpo allo zucchero', 'Scrub delicato allo zucchero di canna e olio di mandorle dolci, per una pelle liscia e vellutata.', 7, 15, 'Profumo', $scents],
            ['Unghie', 'Aurora', 'Smalto gel effetto lucido', 'Smalto a lunga tenuta con finish gel, si applica in due passate e asciuga rapidamente. Senza lampada.', 3, 8, 'Colore', $nailShades],
            ['Unghie', 'Yara', 'Kit manicure completo', 'Set da viaggio con lima, tronchesino, spingi-cuticole e forbicine in acciaio, in pratica custodia.', 8, 16, null, []],
            ['Accessori beauty', 'Lumina', 'Set pennelli make-up', 'Set di 8 pennelli con setole morbide per viso e occhi, comodi da usare e facili da lavare. Con pochette inclusa.', 10, 22, null, []],
            ['Accessori beauty', 'Bella Rosa', 'Spugnetta per fondotinta', 'Spugnetta a goccia in lattice per sfumare fondotinta e correttore con un risultato uniforme. Si usa asciutta o umida.', 2, 6, null, []],
            ['Accessori beauty', null, 'Specchio da borsa con luce', 'Specchio tondo richiudibile con luce LED, ideale per ritocchi veloci ovunque tu sia.', 6, 13, null, []],
        ];
    }
}
