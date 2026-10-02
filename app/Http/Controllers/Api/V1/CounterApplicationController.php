<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Counters\CounterApplicationRequest;
use App\Http\Resources\Api\CounterApplicationResource;
use App\Http\Resources\Api\HiringRoundResource;
use App\Models\CounterApplication;
use App\Models\HiringRound;
use App\Models\OtpCode;
use App\Services\Counters\CounterApplicationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * التقديم لوظيفة عدّاد من التطبيق — بلا حساب، كصفحة /counter/apply:
 * الجولات المفتوحة، ثم التقديم (يرسل رمزًا)، ثم التوثيق بالرمز، والمتابعة
 * والسحب بمفتاح الطلب. الخدمة نفسها التي تستدعيها صفحة الويب.
 */
class CounterApplicationController extends Controller
{
    public function rounds(): AnonymousResourceCollection
    {
        return HiringRoundResource::collection(
            HiringRound::accepting()->with(['port.governorate.region', 'company'])->orderBy('closes_at')->get()
        );
    }

    public function store(CounterApplicationRequest $request, CounterApplicationService $service): JsonResponse
    {
        $application = $service->submit($request->validated(), $request->ip());

        return (new CounterApplicationResource($this->load($application)))
            ->additional(['message' => 'استلمنا طلبك — أرسلنا رمز تحقق إلى جوالك.', 'meta' => ['expires_in' => OtpCode::TTL_MINUTES * 60]])
            ->response()
            ->setStatusCode(201);
    }

    public function show(string $token): CounterApplicationResource
    {
        return new CounterApplicationResource($this->application($token));
    }

    public function verify(Request $request, string $token, CounterApplicationService $service): CounterApplicationResource
    {
        $data = $request->validate(['code' => ['required', 'digits:6']]);

        $application = $service->verify($this->application($token), $data['code']);

        return (new CounterApplicationResource($application))
            ->additional(['message' => 'وُثّق جوالك ووصل طلبك إلى الشركة.']);
    }

    public function resend(string $token, CounterApplicationService $service): JsonResponse
    {
        $service->sendCode($this->application($token));

        return response()->json([
            'message' => 'أرسلنا رمزًا جديدًا إلى جوالك.',
            'data' => ['expires_in' => OtpCode::TTL_MINUTES * 60],
        ]);
    }

    public function withdraw(string $token, CounterApplicationService $service): CounterApplicationResource
    {
        $application = $service->withdraw($this->application($token));

        return (new CounterApplicationResource($application))->additional(['message' => 'سُحب طلبك.']);
    }

    private function application(string $token): CounterApplication
    {
        return $this->load(CounterApplication::where('token', $token)->firstOrFail());
    }

    private function load(CounterApplication $application): CounterApplication
    {
        $application->load(['round', 'company', 'port']);
        // مورد يلفّ نموذجًا أُنشئ للتوّ يردّ 201 من تلقاء نفسه؛ الحالة هنا تُحدَّد صراحةً.
        $application->wasRecentlyCreated = false;

        return $application;
    }
}
