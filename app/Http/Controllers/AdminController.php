<?php

namespace App\Http\Controllers;

use App\Models\IntegrationSetting;
use App\Support\AdminRegistry;
use App\Support\ToolData;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function __construct(
        private readonly AdminRegistry $registry,
        private readonly ToolData $tools,
    ) {
    }

    public function index(): View
    {
        return $this->show($this->registry->defaultTab(), request());
    }

    public function show(string $tab, Request $request): View
    {
        $definition = $this->registry->tab($tab);

        return view('admin.index', [
            'tabs' => $this->registry->tabs(),
            'activeTab' => $tab,
            'definition' => $definition,
            'panel' => $this->panel($definition, $request),
        ]);
    }

    private function panel(array $definition, Request $request): array
    {
        return match ($definition['type']) {
            'resource' => $this->resourcePanel($definition, $request),
            'integration' => $this->integrationPanel($definition),
            default => ['view' => $definition['view'], 'data' => $this->tools->for($definition['key'])],
        };
    }

    private function resourcePanel(array $definition, Request $request): array
    {
        $resources = $this->registry->resourcesForTab($definition['key']);
        $active = $request->query('resource');

        if (! $active || ! array_key_exists($active, $resources)) {
            $active = array_key_first($resources);
        }

        return [
            'view' => 'admin.panels.resource',
            'resources' => $resources,
            'activeResource' => $active,
            'resource' => $this->registry->resource($active),
            'records' => $this->registry->records($active),
            'fields' => $this->registry->fields($active),
        ];
    }

    private function integrationPanel(array $definition): array
    {
        $provider = $definition['provider'];

        return [
            'view' => 'admin.panels.integration',
            'provider' => $provider,
            'config' => config("info.integrations.{$provider}"),
            'setting' => IntegrationSetting::firstOrNew(['provider' => $provider]),
        ];
    }
}