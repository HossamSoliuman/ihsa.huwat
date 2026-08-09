<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<EmployeeDocument> */
class EmployeeDocumentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'document_type' => 'contract',
            'document_number' => fake()->bothify('DOC-####'),
            'issue_date' => today()->subYear(),
            'expiry_date' => today()->addYear(),
            'original_name' => 'document.pdf',
            'stored_path' => 'employment/documents/document.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 1024,
            'uploaded_by' => User::factory(),
        ];
    }
}
