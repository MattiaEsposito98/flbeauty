<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class PasswordResetController extends Controller
{
    /**
     * Invia l'email con il link per reimpostare la password. Risposta
     * generica in ogni caso per non rivelare se un indirizzo è registrato.
     */
    public function sendResetLink(Request $request)
    {
        $request->validate(['email' => ['required', 'email']]);

        Password::sendResetLink($request->only('email'));

        return response()->json([
            'message' => 'Se l\'indirizzo risulta registrato, riceverai una email con le istruzioni per reimpostare la password.',
        ]);
    }

    public function reset(Request $request)
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $status = Password::reset($data, function ($user, $password) {
            $user->forceFill(['password' => Hash::make($password)])->save();

            // Chiude tutti gli accessi aperti: se qualcuno era entrato con la
            // vecchia password, viene buttato fuori.
            $user->tokens()->delete();
        });

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => ['Il link non è valido o è scaduto. Richiedine uno nuovo.'],
            ]);
        }

        return response()->json([
            'message' => 'Password reimpostata con successo. Ora puoi accedere.',
        ]);
    }
}
