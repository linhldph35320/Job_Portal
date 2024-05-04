<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        // khi mà chưa đăng nhập mà vẫn muốn vào trang profile thì sẽ bị đẩy lại trang đăng nhập
        return $request->expectsJson() ? null : route('account.login');
    }
}
