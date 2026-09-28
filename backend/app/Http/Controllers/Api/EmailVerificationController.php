<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    /**
     * Rotta firmata aperta dal link nell'email di verifica. Non richiede
     * autenticazione: l'identità è garantita dalla firma dell'URL.
     */
    public function verify(Request $request, int $id, string $hash)
    {
        $user = User::findOrFail($id);

        abort_unless(hash_equals($hash, sha1($user->getEmailForVerification())), 403);

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        return redirect(config('app.frontend_url').'/login?verified=1');
    }

    /**
     * Reinvia l'email di verifica. Risposta generica in ogni caso per non
     * rivelare se un indirizzo email è registrato o meno. Accetta email o
     * username (`login`), come il form di accesso da cui viene richiamato.
     */
    public function resend(Request $request)
    {
        $request->validate(['login' => ['required', 'string', 'max:255']]);

        $user = User::findByLogin($request->login);

        if ($user && ! $user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();
        }

        return response()->json([
            'message' => 'Se l\'indirizzo risulta registrato e non ancora verificato, abbiamo inviato una nuova email di verifica.',
        ]);
    }
}
