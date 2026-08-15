<?php

namespace App\Http\Controllers;

use App\Models\IntegrationSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class IntegrationSettingController extends Controller
{
    public function update(Request $request, string $tab, string $provider): RedirectResponse
    {
        $definition = config("info.integrations.{$provider}");

        abort_if(! $definition, 404);

        $validated = $request->validate([
            'enabled' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'settings' => ['nullable', 'array'],
        ]);

        $settings = collect($definition['fields'])
            ->mapWithKeys(function (array $field) use ($validated) {
                $value = data_get($validated, "settings.{$field['key']}");

                return [$field['key'] => match ($field['type'] ?? 'text') {
                    'boolean' => (bool) $value,
                    'number' => $value === null || $value === '' ? null : (float) $value,
                    default => $value === '' ? null : $value,
                }];
            })
            ->all();

        IntegrationSetting::updateOrCreate(
            ['provider' => $provider],
            [
                'enabled' => (bool) ($validated['enabled'] ?? false),
                'notes' => $validated['notes'] ?? null,
                'settings' => $settings,
            ]
        );

        return redirect()
            ->route('admin.tab', ['tab' => $tab])
            ->with('status', 'تم حفظ إعدادات التكامل.');
    }
}