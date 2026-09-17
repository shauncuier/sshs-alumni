<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * Laravel 11 removed AuthorizesRequests from the framework base controller, so
 * it is added here.
 *
 * Without it `$this->authorize()` is simply an undefined method — which fails
 * loudly, but only on the exact request that needed the check. Policies are
 * this application's main authorization mechanism, so every controller gets it.
 *
 * @see docs/04-roles-permissions.md section 6
 */
abstract class Controller
{
    use AuthorizesRequests;
}
