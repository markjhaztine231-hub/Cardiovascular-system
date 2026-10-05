<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    /** Show the current user's profile page. */
    public function show()
    {
        return inertia('Profile', [
            'user' => Auth::user(),
        ]);
    }

    /** Update name, age, sex, contact, email. */
    public function update(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,'.$user->id,
            'age' => 'nullable|integer|min:1|max:130',
            'sex' => 'nullable|string|in:Male,Female',
            'contact' => 'nullable|string|max:30',
            'specialty' => 'nullable|string|max:255',
        ]);

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        // Demographics moved off users onto the profile records.
        if ($user->patient) {
            $patient = $user->patient;
            $patient->sex = $validated['sex'] ?? $patient->sex;
            $patient->contact_phone = $validated['contact'] ?? $patient->contact_phone;
            if ($validated['age'] !== null) {
                $patient->date_of_birth = now()->subYears((int) $validated['age']);
            }
            $patient->save();
        }

        if ($user->provider && ! empty($validated['specialty'])) {
            $user->provider->update(['specialty' => $validated['specialty']]);
        }

        return back()->with('success', 'Profile updated successfully.');
    }

    /** Change the current user's password. */
    public function updatePassword(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
            'new_password_confirmation' => 'required',
        ]);

        if (! Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => 'The current password is incorrect.',
            ]);
        }

        $user->update(['password' => Hash::make($request->new_password)]);

        return back()->with('success', 'Password changed successfully.');
    }
}
