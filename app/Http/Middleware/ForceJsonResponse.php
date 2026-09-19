<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * واجهة التطبيق تردّ JSON دائمًا: أخطاء التحقق والاستيثاق و404 كلها بصيغة
 * واحدة، حتى لو نسي العميل ترويسة Accept. والعربية تخرج حروفًا لا رموز
 * هروب، حتى تُقرأ في السجلات وأدوات الاختبار.
 */
class ForceJsonResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->headers->set('Accept', 'application/json');

        $response = $next($request);

        if ($response instanceof JsonResponse) {
            $response->setEncodingOptions($response->getEncodingOptions() | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return $response;
    }
}
