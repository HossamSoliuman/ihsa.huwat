<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Support\AdminRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class AdminResourceController extends Controller
{
    public function __construct(private readonly AdminRegistry $registry)
    {
    }

    public function store(Request $request, string $tab, string $resource): RedirectResponse
    {
        $this->guard($resource);

        $data = $this->registry->normalize(
            $resource,
            $request->validate($this->registry->validationRules($resource))
        );

        $record = $this->registry->model($resource)->newQuery()->create($data);

        $this->log('إنشاء', $resource, $record->getKey());

        return $this->back($tab, $resource, 'تم إضافة السجل بنجاح.');
    }

    public function update(Request $request, string $tab, string $resource, int $id): RedirectResponse
    {
        $this->guard($resource);

        $data = $this->registry->normalize(
            $resource,
            $request->validate($this->registry->validationRules($resource))
        );

        $record = $this->registry->model($resource)->newQuery()->findOrFail($id);
        $record->update($data);

        $this->log('تعديل', $resource, $id);

        return $this->back($tab, $resource, 'تم تحديث السجل بنجاح.');
    }

    public function destroy(string $tab, string $resource, int $id): RedirectResponse
    {
        $this->guard($resource);

        $this->registry->model($resource)->newQuery()->findOrFail($id)->delete();

        $this->log('حذف', $resource, $id);

        return $this->back($tab, $resource, 'تم حذف السجل.');
    }

    private function guard(string $resource): void
    {
        if (! empty($this->registry->resource($resource)['readonly'])) {
            throw new AccessDeniedHttpException('هذا السجل للعرض فقط.');
        }
    }

    private function log(string $action, string $resource, int|string|null $id): void
    {
        AuditLog::create([
            'action' => $action,
            'entity' => class_basename($this->registry->resource($resource)['model']),
            'entity_id' => (string) $id,
            'user' => request()->user()?->email ?? 'system',
            'user_role' => request()->user()?->role ?? 'admin',
            'details' => "{$action} سجل في {$this->registry->resource($resource)['title']}",
            'timestamp' => now(),
        ]);
    }

    private function back(string $tab, string $resource, string $message): RedirectResponse
    {
        return redirect()
            ->route('admin.tab', ['tab' => $tab, 'resource' => $resource])
            ->with('status', $message);
    }
}