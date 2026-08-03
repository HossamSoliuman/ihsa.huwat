<?php

namespace Tests\Feature;

use App\Models\Boat;
use App\Models\Captain;
use App\Models\HarborLicense;
use App\Models\InformationDraft;
use App\Models\InformationSubmission;
use App\Models\Port;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InformationPortalTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('information.create'))->assertRedirect(route('login'));
    }

    public function test_login_returns_the_user_to_the_information_portal(): void
    {
        $user = $this->dataEntryUser();

        $this->get(route('information.create'))->assertRedirect(route('login'));

        $this->post(route('login.store'), [
            'username' => $user->username,
            'password' => 'password',
        ])->assertRedirect(route('information.create'));
    }

    public function test_only_data_entry_roles_can_open_the_portal(): void
    {
        $unauthorizedRole = Role::query()->where('code', 'hr_manager')->firstOrFail();
        $unauthorizedUser = User::factory()->create(['role_id' => $unauthorizedRole->id]);

        $this->actingAs($unauthorizedUser)
            ->get(route('information.create'))
            ->assertForbidden();
    }

    public function test_statistical_employee_can_open_the_complete_reference_workflow(): void
    {
        $user = $this->dataEntryUser();

        $this->actingAs($user)
            ->get(route('information.create'))
            ->assertOk()
            ->assertSee('بيانات المالك')
            ->assertSee('معلومات الرخصة')
            ->assertSee('معلومات الهيكل والمحرك')
            ->assertSee('قائمة البحارة')
            ->assertSee('أدوات الصيد')
            ->assertSee('المستندات والمرفقات');
    }

    public function test_user_can_save_restore_and_discard_a_partial_draft(): void
    {
        $user = $this->dataEntryUser();
        $draftPayload = [
            'fields' => ['owner_full_name' => 'عبدالله أحمد'],
            'crew_members' => [['full_name' => 'سالم علي']],
            'fishing_tools' => [['type' => 'trawl_net']],
        ];

        $this->actingAs($user)
            ->postJson(route('information.draft.store'), [
                'current_step' => 3,
                'payload' => $draftPayload,
            ])
            ->assertOk()
            ->assertJsonPath('message', 'تم حفظ المسودة.');

        $draft = InformationDraft::query()->whereBelongsTo($user)->sole();
        $this->assertSame(3, $draft->current_step);
        $this->assertSame('عبدالله أحمد', $draft->payload['fields']['owner_full_name']);

        $this->actingAs($user)
            ->get(route('information.create'))
            ->assertOk()
            ->assertSee('توجد مسودة محفوظة');

        $this->actingAs($user)
            ->deleteJson(route('information.draft.discard'))
            ->assertNoContent();

        $this->assertModelMissing($draft);
    }

    public function test_valid_submission_persists_every_reference_section_and_private_file(): void
    {
        Storage::fake('local');
        $user = $this->dataEntryUser();
        $port = Port::factory()->create();
        InformationDraft::query()->create([
            'user_id' => $user->id,
            'payload' => ['fields' => [], 'crew_members' => [], 'fishing_tools' => []],
            'current_step' => 2,
        ]);

        $response = $this->actingAs($user)->post(route('information.store'), $this->validSubmissionData($port));

        $submission = InformationSubmission::query()->with(['boat', 'captain', 'port'])->sole();
        $this->assertModelExists($submission);
        $this->assertSame('B-2048', $submission->boat->registration_no);
        $this->assertSame('محمد سالم البحري', $submission->captain->full_name);
        $this->assertSame($port->getKey(), $submission->port->getKey());
        $this->assertSame('saudi', $submission->owner_nationality);
        $this->assertSame('owner@example.test', $submission->owner_email);
        $this->assertSame(today()->subMonth()->format('Y-m-d'), $submission->license_issue_date?->format('Y-m-d'));
        $this->assertSame('ENG-2048', $submission->boat_data['engine_number']);
        $this->assertSame('A12345678', $submission->captain_data['captain_passport_number']);
        $this->assertCount(2, $submission->crew_members);
        $this->assertCount(3, $submission->fishing_tools);
        $this->assertCount(8, $submission->document_paths);
        $this->assertNotNull($submission->captain_photo_path);
        $this->assertNotNull($submission->consented_at);
        $this->assertSame(0, InformationDraft::query()->whereBelongsTo($user)->count());

        $license = HarborLicense::query()->where('license_number', 'LIC-2026-77')->sole();
        $this->assertSame($submission->document_paths['fishing_license'], $license->attachment_path);
        $this->assertSame('B-2048', $license->boat_number);
        $this->assertSame(today()->subMonth()->format('Y-m-d'), $license->issue_date?->format('Y-m-d'));

        foreach ($submission->document_paths as $documentPath) {
            Storage::disk('local')->assertExists($documentPath);
        }

        Storage::disk('local')->assertExists($submission->captain_photo_path);
        $response->assertRedirect(route('information.submitted', $submission->reference_no));

        $this->actingAs($user)
            ->get(route('information.submitted', $submission->reference_no))
            ->assertOk()
            ->assertSee($submission->reference_no)
            ->assertSee('تم استلام البيانات بنجاح');
    }

    public function test_existing_boat_and_captain_are_updated_without_duplicates(): void
    {
        Storage::fake('local');
        $user = $this->dataEntryUser();
        $port = Port::factory()->create();

        $this->actingAs($user)->post(route('information.store'), $this->validSubmissionData($port));
        $this->actingAs($user)->post(route('information.store'), [
            ...$this->validSubmissionData($port),
            'boat_name' => 'النورس الجديد',
            'captain_phone' => '0555555555',
        ]);

        $this->assertSame(1, Boat::query()->where('registration_no', 'B-2048')->count());
        $this->assertSame(1, Captain::query()->where('national_id', '1123456789')->count());
        $this->assertSame(2, InformationSubmission::query()->count());
        $this->assertSame('النورس الجديد', Boat::query()->where('registration_no', 'B-2048')->value('name'));
        $this->assertSame('0555555555', Captain::query()->where('national_id', '1123456789')->value('phone'));
    }

    public function test_submission_rejects_inactive_ports_and_invalid_identity_numbers(): void
    {
        Storage::fake('local');
        $user = $this->dataEntryUser();
        $inactivePort = Port::factory()->create(['is_active' => false]);

        $this->actingAs($user)->post(route('information.store'), [
            ...$this->validSubmissionData($inactivePort),
            'owner_national_id' => '123',
        ])->assertInvalid(['port_id', 'owner_national_id']);

        $this->assertSame(0, InformationSubmission::query()->count());
    }

    public function test_submission_rejects_missing_reference_fields_invalid_tools_and_unsafe_files(): void
    {
        Storage::fake('local');
        $user = $this->dataEntryUser();
        $port = Port::factory()->create();
        $submissionData = $this->validSubmissionData($port);

        unset($submissionData['engine_number']);
        $submissionData['fishing_tools'][0]['is_primary'] = false;
        $submissionData['documents']['engine_photo'] = UploadedFile::fake()->create('engine.pdf', 5, 'application/pdf');
        $submissionData['documents']['additional'] = UploadedFile::fake()->create('payload.exe', 5, 'application/octet-stream');

        $this->actingAs($user)
            ->post(route('information.store'), $submissionData)
            ->assertInvalid([
                'engine_number',
                'documents.engine_photo',
                'fishing_tools',
                'documents.additional',
            ]);

        $this->assertSame(0, InformationSubmission::query()->count());
    }

    private function dataEntryUser(): User
    {
        $role = Role::query()->where('code', 'stat_employee')->firstOrFail();

        return User::factory()->create(['role_id' => $role->id]);
    }

    /** @return array<string, mixed> */
    private function validSubmissionData(Port $port): array
    {
        return [
            'owner_full_name' => 'عبدالله أحمد البحري',
            'owner_national_id' => '1023456789',
            'owner_nationality' => 'saudi',
            'owner_birth_date' => today()->subYears(42)->format('Y-m-d'),
            'owner_email' => 'owner@example.test',
            'owner_phone' => '0500000000',
            'owner_region' => $port->governorate->region->name,
            'owner_governorate' => $port->governorate->name,
            'owner_city' => 'القنفذة',
            'owner_address' => 'حي الشاطئ، شارع الميناء',
            'license_number' => 'LIC-2026-77',
            'license_issue_date' => today()->subMonth()->format('Y-m-d'),
            'license_expiry_date' => today()->addYear()->format('Y-m-d'),
            'port_id' => $port->id,
            'boat_name' => 'النورس',
            'boat_name_en' => 'Al-Nawras',
            'registration_no' => 'B-2048',
            'boat_type' => 'small',
            'boat_classification' => 'traditional_craft',
            'hull_material' => 'wood_fiberglass',
            'boat_build_date' => today()->subYears(5)->format('Y-m-d'),
            'boat_license_expiry_date' => today()->addYear()->format('Y-m-d'),
            'hull_number' => 'HULL-2048',
            'engine_number' => 'ENG-2048',
            'engine_serial_number' => 'SER-2048',
            'call_sign' => 'HZ-2048',
            'captain_full_name' => 'محمد سالم البحري',
            'captain_national_id' => '1123456789',
            'captain_phone' => '0511111111',
            'captain_passport_number' => 'A12345678',
            'captain_birth_date' => today()->subYears(39)->format('Y-m-d'),
            'captain_license_number' => 'MAR-7788',
            'captain_license_expiry_date' => today()->addYears(2)->format('Y-m-d'),
            'captain_nationality' => 'saudi',
            'captain_qualification' => 'master_fisher',
            'captain_experience_years' => 18,
            'captain_photo' => UploadedFile::fake()->image('captain.jpg'),
            'crew_count' => 2,
            'crew_members' => [
                [
                    'full_name' => 'سالم علي الصياد',
                    'identity_number' => '2123456789',
                    'passport_number' => 'P12345678',
                    'phone' => '0522222222',
                    'birth_date' => today()->subYears(31)->format('Y-m-d'),
                    'nationality' => 'saudi',
                    'role' => 'fisher',
                    'experience_years' => 9,
                ],
                [
                    'full_name' => 'ناصر حسن البحار',
                    'identity_number' => '2234567890',
                    'passport_number' => 'P87654321',
                    'phone' => '0533333333',
                    'birth_date' => today()->subYears(29)->format('Y-m-d'),
                    'nationality' => 'saudi',
                    'role' => 'deckhand',
                    'experience_years' => 6,
                ],
            ],
            'fishing_method' => 'nets',
            'fishing_tools' => [
                ['type' => 'trawl_net', 'quantity' => 12, 'size' => '3 إنش', 'material' => 'nylon', 'condition' => 'serviceable', 'is_primary' => true],
                ['type' => 'traps', 'quantity' => 45, 'size' => null, 'material' => 'steel', 'condition' => 'serviceable', 'is_primary' => false],
                ['type' => 'line', 'quantity' => 18, 'size' => null, 'material' => 'thread', 'condition' => 'maintenance_required', 'is_primary' => false],
            ],
            'documents' => [
                'engine_photo' => UploadedFile::fake()->image('engine.jpg'),
                'boat_photo' => UploadedFile::fake()->image('boat.jpg'),
                'boat_registration' => UploadedFile::fake()->create('boat-registration.pdf', 110, 'application/pdf'),
                'boat_license' => UploadedFile::fake()->create('boat-license.pdf', 100, 'application/pdf'),
                'fishing_license' => UploadedFile::fake()->create('fishing-license.pdf', 100, 'application/pdf'),
                'insurance' => UploadedFile::fake()->create('insurance.pdf', 70, 'application/pdf'),
                'safety_certificate' => UploadedFile::fake()->create('safety.pdf', 70, 'application/pdf'),
                'additional' => UploadedFile::fake()->create('supporting.pdf', 50, 'application/pdf'),
            ],
            'consent' => true,
        ];
    }
}
