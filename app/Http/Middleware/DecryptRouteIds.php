<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Symfony\Component\HttpFoundation\Response;

class DecryptRouteIds
{
    /**
     * Handle an incoming request and decrypt route parameters.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $route = $request->route();
        
        if ($route) {
            $parameters = $route->parameters();
            
            foreach ($parameters as $key => $value) {
                // Try to decrypt the parameter if it's a string and looks encrypted
                if (is_string($value) && !is_numeric($value)) {
                    try {
                        $decrypted = Crypt::decryptString($value);
                        
                        // If decryption successful and result is numeric, update the route parameter
                        if (is_numeric($decrypted)) {
                            $route->setParameter($key, $decrypted);
                        }
                    } catch (\Exception $e) {
                        // If decryption fails, leave parameter as is
                        continue;
                    }
                }
            }
        }
        
        return $next($request);
    }
}
