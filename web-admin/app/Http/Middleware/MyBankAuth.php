<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class MyBankAuth
{
    public function handle(Request $request, Closure $next)
    {
        if (!session('mybank_access_token') || !session('mybank_user')) {
            return redirect()->route('login');
        }

        return $next($request);
    }
}


