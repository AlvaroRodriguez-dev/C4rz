<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }

    //asistencia app
    // ── Asignar license a usuario ────────────────────────────────────
    public function asignarLicense(Request $request, User $user)
    {
        $request->validate([
            'license' => 'nullable|string|max:50',
        ]);

        $schema   = env('PG_RRHH_SCHEMA', '2026');

        // Verificar que el LICENSE existe en PostgreSQL
        if ($request->license) {
            $existe = DB::connection('pgsql_rrhh')
                ->table(DB::raw('"' . $schema . '"."rrhh_personal"'))
                ->where('LICENSE', $request->license)
                ->whereNull('DELETED_AT')
                ->exists();

            if (!$existe) {
                return back()->withErrors([
                    'license' => 'El número de empleado no existe en el sistema de RRHH.'
                ]);
            }
        }

        $user->update(['license' => $request->license]);

        return back()->with('success', 'Número de empleado asignado correctamente.');
    }
}
