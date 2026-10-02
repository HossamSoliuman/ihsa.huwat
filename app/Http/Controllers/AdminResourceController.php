<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Support\AdminRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AdminResourceController extends Controller
{
    public function __construct(private readonly AdminRegistry $registry)
    {
    }

    public function store(Request $request, string $tab, string $resource): RedirectResponse
    {
        $this->guard($tab, $resource);

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
        $this->guard($tab, $resource);

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
        $this->guard($tab, $resource);

        $this->registry->model($resource)->newQuery()->findOrFail($id)->delete();

        $this->log('حذف', $resource, $id);

        return $this->back($tab, $resource, 'تم حذف السجل.');
    }

    private function guard(string $tab, string $resource): void
    {
        // المورد يُكتب من تبويبه وحده: مورد تبويبٍ أُسقط (كالرخص) لا يُفتح من تبويب آخر.
        if (! array_key_exists($resource, $this->registry->resourcesForTab($tab))) {
            throw new NotFoundHttpException;
        }

        if (! empty($this->registry->resource($resource)['readonly'])) {
            throw new AccessDeniedHttpException('هذا السجل للعرض فقط.');
        }
    }

    private function log(string $action, string $resource, int|string|null $id): void
    {
        AuditLog::create([
            'user_email' => request()->user()?->email ?? 'system',
            'role' => request()->user()?->role ?? 'admin',
            'action' => $action,
            'entity' => class_basename($this->registry->resource($resource)['model']),
            'record_label' => (string) $id,
            'details' => "{$action} سجل في {$this->registry->resource($resource)['title']}",
            'ip' => request()->ip(),
        ]);
    }

    private function back(string $tab, string $resource, string $message): RedirectResponse
    {
        return redirect()
            ->route('admin.tab', ['tab' => $tab, 'resource' => $resource])
            ->with('status', $message);
    }
}