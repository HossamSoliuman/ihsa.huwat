<?php

namespace Tests\Feature;

use App\Models\Bank;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EmployeeDocumentTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_hr_uploads_and_downloads_a_private_employee_document(): void
    {
        Storage::fake('local');
        $employee = Employee::factory()->create();
        $hrManager = $this->userWithRole('hr_manager');

        $this->actingAs($hrManager)->post(route('dashboard.hr.employees.documents.store', $employee), [
            'document_type' => 'contract',
            'document_number' => 'C-100',
            'issue_date' => '2026-01-01',
            'expiry_date' => '2026-12-31',
            'document' => UploadedFile::fake()->create('contract.pdf', 128, 'application/pdf'),
        ])->assertSessionHasNoErrors();

        $document = EmployeeDocument::query()->where('employee_id', $employee->id)->firstOrFail();
        Storage::disk('local')->assertExists($document->stored_path);
        $this->actingAs($hrManager)->get(route('dashboard.hr.employees.documents.download', [$employee, $document]))
            ->assertOk()->assertDownload('contract.pdf');
        $this->assertDatabaseHas('audit_logs', ['action' => 'employee_document_uploaded', 'model_id' => $document->id]);
    }

    public function test_bank_details_are_normalized_and_audited(): void
    {
        $employee = Employee::factory()->create();
        $bank = Bank::factory()->create();

        $this->actingAs($this->userWithRole('hr_manager'))->patch(route('dashboard.hr.employees.bank-details.update', $employee), [
            'bank_id' => $bank->id,
            'iban' => 'sa03 8000 0000 6080 1016 7519',
            'account_holder_name' => 'موظف الاختبار',
        ])->assertSessionHasNoErrors();

        $this->assertSame('SA0380000000608010167519', $employee->fresh()->iban);
        $this->assertDatabaseHas('audit_logs', ['action' => 'employee_bank_details_updated', 'model_id' => $employee->id]);
    }

    private function userWithRole(string $role): User
    {
        return User::factory()->create(['role_id' => Role::query()->where('code', $role)->valueOrFail('id')]);
    }
}
