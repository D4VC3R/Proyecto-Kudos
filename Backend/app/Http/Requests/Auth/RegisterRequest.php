<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * Gestiona y valida las peticiones de registro de la API.
 */
class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Password::defaults()],
            // RGPD (F3-07): casilla sin marcar de aceptación de la política de privacidad y declaración de edad
            // (14 años: edad de consentimiento digital en España). "accepted" admite true, 1, "1", "yes", "on".
            'accept_privacy' => ['required', 'accepted'],
            'age_confirmation' => ['required', 'accepted'],
        ];
    }

    /**
     * Define los mensajes de error personalizados para este request.
     */
    public function messages(): array
    {
        return [
            'email.unique' => 'El correo electrónico introducido ya está en uso. Por favor, utiliza otro o inicia sesión.',
            'name.required' => 'El nombre es obligatorio.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Debes proporcionar una dirección de correo válida.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'accept_privacy.required' => 'Debes aceptar la política de privacidad.',
            'accept_privacy.accepted' => 'Debes aceptar la política de privacidad.',
            'age_confirmation.required' => 'Debes confirmar que tienes 14 años o más.',
            'age_confirmation.accepted' => 'Debes confirmar que tienes 14 años o más.',
        ];
    }
}
