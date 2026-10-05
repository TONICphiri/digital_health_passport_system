<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class Controller
{
    use AuthorizesRequests;

    protected function perPage(): int
    {
        return (int) config('health_passport.per_page', 15);
    }
}
