<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureInformationIdentity
{
    /**
     * Session key holding the national id and phone the visitor confirmed on the
     * portal landing page. The portal is public, so this identity is the only
     * thing scoping what a visitor may submit or read back.
     */
    public const SESSION_KEY = 'information_identity';

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (self::verified($request) === null) {
            return redirect()->route('information.identity.create');
        }

        return $next($request);
    }

    /**
     * The identity confirmed for the current session, if any.
     *
     * @return array{national_id: string, phone: string}|null
     */
    public static function verified(Request $request): ?array
    {
        $identity = $request->session()->get(self::SESSION_KEY);

        if (! is_array($identity) || ! isset($identity['national_id'], $identity['phone'])) {
            return null;
        }

        return [
            'national_id' => (string) $identity['national_id'],
            'phone' => (string) $identity['phone'],
        ];
    }
}
