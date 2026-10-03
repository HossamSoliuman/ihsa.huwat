<?php

namespace App\Http\Controllers;

use App\Models\IntegrationSetting;
use App\Support\AdminRegistry;
use App\Support\Nav;
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

    /**
     * رئيسة إدارة النظام: أقسام القائمة الجانبية نفسها بطاقاتٍ، على كل تبويب
     * من تبويبات البوابة ما يلخّص حاله — عدد سجلاته، أو حال تكامله.
     */
    public function index(): View
    {
        $tabs = collect($this->registry->tabs());
        $integrations = IntegrationSetting::pluck('enabled', 'provider');
        $counts = $tabs->where('type', 'resource')
            ->map(fn (array $tab) => collect($tab['resources'])->sum(fn (string $resource) => $this->registry->model($resource)->newQuery()->count()));

        $sections = array_map(fn (array $section) => ['items' => array_map(function (array $item) use ($tabs, $counts, $integrations) {
            $tab = $tabs->get($item['params']['tab'] ?? '');

            $summary = match ($tab['type'] ?? null) {
                null => null,
                'resource' => ['text' => number_format($counts[$item['params']['tab']]).' سجل', 'tone' => 'muted'],
                'integration' => $integrations->get($tab['provider'])
                    ? ['text' => 'مفعّل', 'tone' => 'ok']
                    : ['text' => 'معطّل', 'tone' => 'muted'],
                default => ['text' => 'أداة', 'tone' => 'info'],
            };

            return $item + ['summary' => $summary, 'label_en' => $tab['label_en'] ?? null];
        }, $section['items'])] + $section, Nav::sections(Nav::SUBADMIN));

        return view('admin.overview', [
            'sections' => $sections,
            'totals' => [
                'records' => $counts->sum(),
                'tables' => $tabs->where('type', 'resource')->sum(fn (array $tab) => count($tab['resources'])),
                'integrations' => $integrations->filter()->count(),
                'integrations_total' => $tabs->where('type', 'integration')->count(),
            ],
        ]);
    }

    public function show(string $tab, Request $request): View
    {
        $definition = $this->registry->tab($tab);

        return view('admin.index', [
            'activeTab' => $tab,
            'definition' => $definition,
            'section' => $this->sectionOf($tab),
            'panel' => $this->panel($definition, $request),
        ]);
    }

    /**
     * عنوان قسم القائمة الذي يقع فيه التبويب — يُعرض فوق عنوان الصفحة.
     */
    private function sectionOf(string $tab): ?string
    {
        foreach (Nav::sections(Nav::SUBADMIN) as $section) {
            foreach ($section['items'] as $item) {
                if (($item['params']['tab'] ?? null) === $tab) {
                    return $section['title'];
                }
            }
        }

        return null;
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

        $search = trim((string) $request->query('q', ''));

        return [
            'view' => 'admin.panels.resource',
            'resources' => $resources,
            'counts' => array_map(fn (array $resource) => $this->registry->model($resource['key'])->newQuery()->count(), $resources),
            'activeResource' => $active,
            'resource' => $this->registry->resource($active),
            'records' => $this->registry->records($active, $search),
            'fields' => $this->registry->fields($active),
            'search' => $search,
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
