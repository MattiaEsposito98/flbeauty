<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AccountController extends Controller
{
    /**
     * Cambio password dal profilo. Nome utente ed email non sono modificabili
     * dal sito (decisione presa con l'utente).
     */
    public function updatePassword(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'string', 'current_password:sanctum'],
            'password' => ['required', 'string', 'min:8', 'confirmed', 'different:current_password'],
        ], [
            'current_password.current_password' => 'La password attuale non è corretta.',
            'password.different' => 'La nuova password deve essere diversa da quella attuale.',
        ]);

        $user = $request->user();
        $user->forceFill(['password' => Hash::make($data['password'])])->save();

        // Chiude gli accessi aperti sugli altri dispositivi, non quello in uso.
        $user->tokens()->where('id', '!=', $user->currentAccessToken()->id)->delete();

        return response()->json([
            'message' => 'Password aggiornata. Gli accessi aperti su altri dispositivi sono stati chiusi.',
        ]);
    }

    /**
     * Eliminazione dell'account (diritto alla cancellazione, art. 17 GDPR).
     * Vengono cancellati account, indirizzi, carrello, preferiti e storico
     * accessi (cancellazione a cascata nel database). Gli ordini restano per
     * gli obblighi fiscali (10 anni) ma non sono più collegati a un account.
     */
    public function destroy(Request $request)
    {
        $request->validate([
            'password' => ['required', 'string', 'current_password:sanctum'],
        ], [
            'password.current_password' => 'La password non è corretta.',
        ]);

        $user = $request->user();

        if ($user->is_admin) {
            throw ValidationException::withMessages([
                'password' => ['Gli account dello staff non si eliminano dal sito.'],
            ]);
        }

        DB::transaction(function () use ($user) {
            $user->tokens()->delete();
            DB::table('password_reset_tokens')->where('email', $user->email)->delete();
            $user->delete();
        });

        return response()->json([
            'message' => 'Il tuo account è stato eliminato. Ci dispiace vederti andare via!',
        ]);
    }
}
