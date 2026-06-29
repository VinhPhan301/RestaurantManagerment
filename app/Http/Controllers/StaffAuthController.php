<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class StaffAuthController extends Controller
{
    /**
     * Display the staff login view.
     */
    public function create(): Response
    {
        return Inertia::render('Staff/Login');
    }

    /**
     * Handle an incoming staff authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        // Check if user has manager or staff role
        if (!in_array(Auth::user()->role, ['manager', 'staff'])) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        return redirect()->route('staff.dashboard');
    }

    /**
     * Destroy an authenticated staff session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect()->route('staff.login');
    }
}
