<?php

namespace App\Http\Controllers;

use App\Models\NotificationSetting;
use App\Support\SystemSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * الإعدادات — ما يُحرَّر هنا هو قنوات الإشعارات وحدها؛ بقية اللوحات توصيف
 * لسلوك النظام يُقرأ من App\Support\SystemSettings.
 */
class SettingsController extends Controller
{
    public function index(): View
    {
        return view('settings.index', [
            'channels' => NotificationSetting::orderBy('display_order')->orderBy('id')->get(),
            'panels' => SystemSettings::panels(),
            'privacyNote' => SystemSettings::privacyNote(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $enabled = $request->collect('channels')->keys()->all();

        // كل قناة تُكتب بحالتها: خانة غير مؤشّرة لا تُرسل أصلًا، فالغائب مُعطّل.
        foreach (NotificationSetting::all() as $channel) {
            $channel->update(['enabled' => in_array($channel->channel, $enabled, true)]);
        }

        return redirect()->route('subadmin.settings')->with('status', 'تم حفظ تفضيلات الإشعارات');
    }
}
