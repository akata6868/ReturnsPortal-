<?php

namespace ReturnsPortal\Middleware;

use Plenty\Plugin\Http\Request;
use Plenty\Plugin\Http\Response;

class AdminAuthMiddleware
{
    public function handle(Request $request, \Closure $next)
    {
        // TODO: Add real admin auth check. For now, pass-through.
        return $next($request);
    }
}


