<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('doctor.{id}', function (User $user, int $id) {
    return (int) $user->id === $id && $user->role === 'doctor';
}, ['guards' => ['web']]);

Broadcast::channel('receptionist', function (User $user) {
    return $user->role === 'receptionist';
}, ['guards' => ['web']]);

Broadcast::channel('patient.{id}', function (User $user, int $id) {
    return (int) $user->id === $id && $user->role === 'patient';
}, ['guards' => ['web']]);
