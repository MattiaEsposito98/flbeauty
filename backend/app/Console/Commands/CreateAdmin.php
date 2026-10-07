<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

/**
 * Crea (o promuove) un account staff per il pannello /admin. La password si digita
 * in modo nascosto: non va mai scritta nei comandi né condivisa.
 */
class CreateAdmin extends Command
{
    protected $signature = 'admin:create {--name=} {--username=} {--email=}';

    protected $description = 'Crea un utente amministratore per il pannello /admin (password chiesta in modo nascosto)';

    public function handle(): int
    {
        $name = $this->option('name') ?: text('Nome e cognome', required: true);
        $username = $this->option('username') ?: text('Username (lettere, numeri, . _ -)', required: true);
        $email = $this->option('email') ?: text('Email', required: true);

        $validator = Validator::make(compact('name', 'username', 'email'), [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z0-9._-]+$/', 'unique:users,username'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $plain = password('Password (almeno 12 caratteri, non si vede mentre scrivi)', required: true);
        $confirm = password('Ripeti la password', required: true);

        if ($plain !== $confirm) {
            $this->error('Le due password non coincidono.');

            return self::FAILURE;
        }

        if (mb_strlen($plain) < 12) {
            $this->error('La password deve avere almeno 12 caratteri.');

            return self::FAILURE;
        }

        // `is_admin` non è assegnabile in massa di proposito: solo da qui.
        $user = new User(['name' => $name, 'username' => $username, 'email' => $email, 'password' => $plain]);
        $user->forceFill(['is_admin' => true, 'email_verified_at' => now()])->save();

        $this->info("Amministratore creato: {$email}");

        return self::SUCCESS;
    }
}
