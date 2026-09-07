<?php

namespace App\Http\Controllers\Auth;

use App\Enum\SecurityEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\UpdatePasswordRequest;
use App\Support\Security\SecurityAuditor;
use Illuminate\Http\RedirectResponse;

class PasswordController extends Controller
{
    public function update(UpdatePasswordRequest $request): RedirectResponse
    {
        $user = $request->user();
        $user->senha = $request->validated('password');
        $user->save();

        SecurityAuditor::log(SecurityEvent::PasswordChanged, [
            'id_usuario' => $user->getAuthIdentifier(),
            'via' => 'profile',
        ]);

        return back()->with('status', 'password-updated');
    }
}
