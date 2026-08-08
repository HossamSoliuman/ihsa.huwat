<?php

namespace Tests\Feature;

use App\Models\Boat;
use App\Models\Captain;
use App\Models\HarborLicense;
use App\Models\InformationSubmission;
use App\Models\Port;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InformationPortalTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_the_form_is_closed_until_an_identity_is_confirmed(): void
    {
        $this->get(route('information.create'))->assertRedirect(route('information.identity.create'));
        $this->post(route('information.store'), [])->assertRedirect(route('information.identity.create'));

        $this->assertSame(0, InformationSubmission::query()->count());
    }

    public function test_the_form_opens_for_a_confirmed_visitor_without_logging_in(): void
    {
        $this->confirmIdentity();

        $this->get(route('information.create'))
            ->assertOk()
            ->assertSee('1023456789')
            ->assertSee('0500000000');

        $this->assertGuest();
    }

    public function test_a_confirmed_visitor_can_open_the_complete_reference_workflow(): void
    {
        $this->confirmIdentity();

        $this->get(route('information.create'))
            ->assertOk()
            ->assertSee('بيانات المالك')
            ->assertSee('بيانات القارب')
            ->assertSee('القبطان والبحارة')
            ->assertSee('قائمة البحارة')
            ->assertSee('رقم الرصيف')
            ->assertSee('رقم الموقف')
            ->assertSee('رقم رخصة الصيد')
            ->assertSee('صورة البحار')
            ->assertSee('أدوات الصيد')
            ->assertSee('سجل الأدوات')
            ->assertSee('المستندات والمرفقات');
    }

    public function test_valid_submission_persists_every_reference_section_and_private_file(): void
    {
        Storage::fake('local');
        $this->confirmIdentity();
        $port = Port::factory()->create();

        $response = $this->post(route('information.store'), $this->validSubmissionData($port));

        $submission = InformationSubmission::query()->with(['boat', 'captain', 'port'])->sole();
        $this->assertModelExists($submission);
        $this->assertNull($submission->submitted_by);
        $this->assertSame('B-2048', $submission->boat->registration_no);
        $this->assertSame('محمد سالم البحري', $submission->captain->full_name);
        $this->assertSame($port->getKey(), $submission->port->getKey());
        $this->assertSame('saudi', $submission->owner_nationality);
        $this->assertSame('owner@example.test', $submission->owner_email);
        $this->assertSame(today()->subMonth()->format('Y-m-d'), $submission->license_issue_date?->format('Y-m-d'));
        $this->assertSame('ENG-2048', $submission->boat_data['engine_number']);
        $this->assertSame('12', $submission->boat_data['berth_number']);
        $this->assertSame('B-07', $submission->boat_data['mooring_number']);
        $this->assertSame('FSH-4455', $submission->captain_data['captain_fishing_license_number']);
        $this->assertSame(today()->subMonths(6)->format('Y-m-d'), $submission->captain_data['captain_fishing_license_issue_date']);
        $this->assertSame(today()->addYears(2)->format('Y-m-d'), $submission->captain_data['captain_fishing_license_expiry_date']);
        $this->assertSame('FSH-1122', $submission->crew_members[0]['fishing_license_number']);
        $this->assertNull($submission->crew_members[1]['fishing_license_number']);
        $this->assertArrayNotHasKey('photo_path', $submission->crew_members[1]);
        Storage::disk('local')->assertExists($submission->crew_members[0]['photo_path']);
        $this->assertCount(2, $submission->crew_members);
        $this->assertCount(3, $submission->fishing_tools);
        $this->assertCount(8, $submission->document_paths);
        $this->assertNotNull($submission->captain_photo_path);
        $this->assertNotNull($submission->consented_at);

        $license = HarborLicense::query()->where('license_number', 'LIC-2026-77')->sole();
        $this->assertSame($submission->document_paths['fishing_license'], $license->attachment_path);
        $this->assertSame('B-2048', $license->boat_number);
        $this->assertSame(today()->subMonth()->format('Y-m-d'), $license->issue_date?->format('Y-m-d'));

        foreach ($submission->document_paths as $documentPath) {
            Storage::disk('local')->assertExists($documentPath);
        }

        Storage::disk('local')->assertExists($submission->captain_photo_path);
        $response->assertRedirect(route('information.submitted', $submission->reference_no));

        $this->get(route('information.submitted', $submission->reference_no))
            ->assertOk()
            ->assertSee($submission->reference_no)
            ->assertSee('تم استلام البيانات بنجاح');
    }

    public function test_the_owner_identity_is_pinned_to_the_confirmed_session(): void
    {
        Storage::fake('local');
        $this->confirmIdentity();
        $port = Port::factory()->create();

        $this->post(route('information.store'), [
            ...$this->validSubmissionData($port),
            'owner_national_id' => '1099999999',
            'owner_phone' => '0599999999',
        ]);

        $submission = InformationSubmission::query()->sole();
        $this->assertSame('1023456789', $submission->owner_national_id);
        $this->assertSame('0500000000', $submission->owner_phone);
    }

    public function test_existing_boat_and_captain_are_updated_without_duplicates(): void
    {
        Storage::fake('local');
        $this->confirmIdentity();
        $port = Port::factory()->create();

        $this->post(route('information.store'), $this->validSubmissionData($port));
        $this->post(route('information.store'), [
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
        $this->confirmIdentity();
        $inactivePort = Port::factory()->create(['is_active' => false]);

        $this->post(route('information.store'), [
            ...$this->validSubmissionData($inactivePort),
            'captain_national_id' => '123',
        ])->assertInvalid(['port_id', 'captain_national_id']);

        $this->assertSame(0, InformationSubmission::query()->count());
    }

    public function test_submission_rejects_missing_reference_fields_invalid_tools_and_unsafe_files(): void
    {
        Storage::fake('local');
        $this->confirmIdentity();
        $port = Port::factory()->create();
        $submissionData = $this->validSubmissionData($port);

        unset($submissionData['engine_number']);
        $submissionData['fishing_tools'][0]['is_primary'] = false;
        $submissionData['documents']['engine_photo'] = UploadedFile::fake()->create('engine.pdf', 5, 'application/pdf');
        $submissionData['documents']['additional'] = UploadedFile::fake()->create('payload.exe', 5, 'application/octet-stream');

        $this->post(route('information.store'), $submissionData)
            ->assertInvalid([
                'engine_number',
                'documents.engine_photo',
                'fishing_tools',
                'documents.additional',
            ]);

        $this->assertSame(0, InformationSubmission::query()->count());
    }

    /**
     * Walk the public gate so the rest of the portal is reachable.
     */
    private function confirmIdentity(string $nationalId = '1023456789', string $phone = '0500000000'): void
    {
        $this->post(route('information.identity.store'), [
            'national_id' => $nationalId,
            'phone' => $phone,
        ])->assertRedirect(route('information.create'));
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
            'berth_number' => '12',
            'mooring_number' => 'B-07',
            'captain_full_name' => 'محمد سالم البحري',
            'captain_national_id' => '1123456789',
            'captain_phone' => '0511111111',
            'captain_license_number' => 'MAR-7788',
            'captain_license_expiry_date' => today()->addYears(2)->format('Y-m-d'),
            'captain_fishing_license_number' => 'FSH-4455',
            'captain_fishing_license_issue_date' => today()->subMonths(6)->format('Y-m-d'),
            'captain_fishing_license_expiry_date' => today()->addYears(2)->format('Y-m-d'),
            'captain_nationality' => 'saudi',
            'captain_photo' => UploadedFile::fake()->image('captain.jpg'),
            'crew_count' => 2,
            'crew_members' => [
                [
                    'full_name' => 'سالم علي الصياد',
                    'identity_number' => '2123456789',
                    'phone' => '0522222222',
                    'nationality' => 'saudi',
                    'role' => 'fisher',
                    'fishing_license_number' => 'FSH-1122',
                    'fishing_license_issue_date' => today()->subYear()->format('Y-m-d'),
                    'fishing_license_expiry_date' => today()->addYear()->format('Y-m-d'),
                ],
                [
                    'full_name' => 'ناصر حسن البحار',
                    'identity_number' => '2234567890',
                    'phone' => '0533333333',
                    'nationality' => 'saudi',
                    'role' => 'deckhand',
                    'fishing_license_number' => null,
                    'fishing_license_issue_date' => null,
                    'fishing_license_expiry_date' => null,
                ],
            ],
            'crew_photos' => [
                0 => UploadedFile::fake()->image('crew-one.jpg'),
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
