<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\SaveEmployeeDocumentAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEmployeeDocumentRequest;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployeeDocumentController extends Controller
{
    public function store(StoreEmployeeDocumentRequest $request, Employee $employee, SaveEmployeeDocumentAction $action): RedirectResponse
    {
        $action->execute(
            $employee,
            Arr::except($request->validated(), 'document'),
            $request->file('document'),
            $request->user(),
            $request->ip(),
        );

        return back()->with('status', 'تم رفع المستند إلى الملف الخاص بالموظف.');
    }

    public function download(Request $request, Employee $employee, EmployeeDocument $employeeDocument): StreamedResponse
    {
        $this->authorize('view', $employee);
        abort_unless($employeeDocument->employee_id === $employee->id, 404);
        abort_unless(Storage::disk('local')->exists($employeeDocument->stored_path), 404);

        return Storage::disk('local')->download($employeeDocument->stored_path, $employeeDocument->original_name, [
            'Cache-Control' => 'private, no-store, max-age=0',
            'Content-Security-Policy' => 'sandbox',
            'X-Content-Type-Options' => 'nosniff',
            'X-Download-Options' => 'noopen',
        ]);
    }
}
