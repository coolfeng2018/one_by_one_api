<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array
     */
    protected $except = [
        //
        '/order/create',
        '/order/notify',
        '/order/callback',

        '/orderPay/create',
        '/orderPay/notify',
        '/orderPay/callback',
    ];
}
