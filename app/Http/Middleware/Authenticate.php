<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        return $request->expectsJson() ? null : route('login');
    }
    protected function authenticate($request, array $guards)
    {
        echo "lam";
        if (empty($guards)) {
            $guards = [null];
        }

        foreach ($guards as $guard) {
            if ($this->auth->guard($guard)->check()) {
                //Check logic
                // Lấy session id hiện tại
                //So sánh với session id trong bảng user --> Nếu khác nhau , sẽ xử lý logout(kèm theo message) nếu giống nhau thì bỏ qua
                $checkDevice = $this->checkDevice($request);
                if ($checkDevice) {
                    $this->unauthenticated($request, $guards);
                }
                return $this->auth->shouldUse($guard);
            }
        }

        $this->unauthenticated($request, $guards);
    }
    public function checkDevice(Request $request)
    {
        $sessionId = $request->session()->getId();
        $user = $request->user();
        $lastSessionId = $user->last_session;
        if ($lastSessionId !== $sessionId) {
            Auth::logout();
            return false;
        }
        return true;
        // dd($sessionId);
    }
}