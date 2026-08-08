<?php

namespace App\Actions;

use App\Models\Boat;
use App\Models\Captain;
use App\Models\HarborLicense;
use App\Models\InformationSubmission;
use App\Models\User;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class StoreInformationSubmissionAction
{
    public function __construct(private FilesystemManager $filesystem) {}

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<string, UploadedFile>  $documents
     * @param  array<int, UploadedFile>  $crewPhotos  Keyed by the crew member index they belong to.
     */
    public function handle(
        ?User $submitter,
        array $attributes,
        array $documents,
        ?UploadedFile $captainPhoto,
        array $crewPhotos = [],
    ): InformationSubmission {
        $storedPaths = [];

        try {
            return DB::transaction(function () use ($submitter, $attributes, $documents, $captainPhoto, $crewPhotos, &$storedPaths): InformationSubmission {
                $boat = Boat::query()->updateOrCreate(
                    ['registration_no' => $attributes['registration_no']],
                    [
                        'name' => $attributes['boat_name'],
                        'boat_type' => $attributes['boat_type'],
                        'home_port_id' => $attributes['port_id'],
                    ],
                );

                $captain = Captain::query()->updateOrCreate(
                    ['national_id' => $attributes['captain_national_id']],
                    [
                        'full_name' => $attributes['captain_full_name'],
                        'phone' => $attributes['captain_phone'],
                    ],
                );

                $submission = InformationSubmission::query()->create([
                    ...Arr::only($attributes, [
                        'port_id',
                        'owner_full_name',
                        'owner_national_id',
                        'owner_nationality',
                        'owner_birth_date',
                        'owner_email',
                        'owner_phone',
                        'owner_region',
                        'owner_governorate',
                        'owner_address',
                        'crew_count',
                        'fishing_method',
                        'license_number',
                        'license_issue_date',
                        'license_expiry_date',
                    ]),
                    'reference_no' => $this->newReference(),
                    'submitted_by' => $submitter?->getKey(),
                    'boat_id' => $boat->getKey(),
                    'captain_id' => $captain->getKey(),
                    'boat_data' => Arr::only($attributes, [
                        'boat_name',
                        'boat_name_en',
                        'registration_no',
                        'boat_type',
                        'boat_classification',
                        'hull_material',
                        'boat_build_date',
                        'boat_license_expiry_date',
                        'hull_number',
                        'engine_number',
                        'engine_serial_number',
                        'call_sign',
                        'berth_number',
                        'mooring_number',
                    ]),
                    'captain_data' => Arr::only($attributes, [
                        'captain_full_name',
                        'captain_national_id',
                        'captain_phone',
                        'captain_license_number',
                        'captain_license_expiry_date',
                        'captain_fishing_license_number',
                        'captain_fishing_license_issue_date',
                        'captain_fishing_license_expiry_date',
                        'captain_nationality',
                    ]),
                    'crew_members' => $attributes['crew_members'],
                    'fishing_tools' => $attributes['fishing_tools'],
                    'status' => InformationSubmission::STATUS_SUBMITTED,
                    'consented_at' => now(),
                    'submitted_at' => now(),
                ]);

                $submission->events()->create([
                    'event_type' => 'submitted',
                    'to_status' => InformationSubmission::STATUS_SUBMITTED,
                    'actor_user_id' => $submitter?->getKey(),
                ]);

                $documentPaths = [];

                foreach ($documents as $category => $document) {
                    if (! $document instanceof UploadedFile) {
                        continue;
                    }

                    $path = $this->storeFile($submission, $category, $document);
                    $storedPaths[] = $path;
                    $documentPaths[$category] = $path;
                }

                $captainPhotoPath = null;

                if ($captainPhoto) {
                    $captainPhotoPath = $this->storeFile($submission, 'captain-photo', $captainPhoto);
                    $storedPaths[] = $captainPhotoPath;
                }

                /**
                 * Crew photos arrive keyed by the row index they were filled in on, so the
                 * stored path is folded back into that member's record.
                 */
                $crewMembers = $attributes['crew_members'];

                foreach ($crewPhotos as $index => $crewPhoto) {
                    if (! $crewPhoto instanceof UploadedFile || ! isset($crewMembers[$index])) {
                        continue;
                    }

                    $crewPhotoPath = $this->storeFile($submission, 'crew-photos', $crewPhoto);
                    $storedPaths[] = $crewPhotoPath;
                    $crewMembers[$index]['photo_path'] = $crewPhotoPath;
                }

                $primaryDocumentPath = Arr::get($documentPaths, 'fishing_license')
                    ?? Arr::get($documentPaths, 'boat_license');

                $submission->update([
                    'document_path' => $primaryDocumentPath,
                    'document_paths' => $documentPaths,
                    'captain_photo_path' => $captainPhotoPath,
                    'crew_members' => $crewMembers,
                ]);

                $licenseAttributes = [
                    'port_id' => $attributes['port_id'],
                    'license_type' => 'operational',
                    'license_holder_name' => $attributes['owner_full_name'],
                    'boat_number' => $attributes['registration_no'],
                    'issue_date' => $attributes['license_issue_date'],
                    'expiry_date' => $attributes['license_expiry_date'],
                    'license_status' => $this->licenseStatus($attributes['license_expiry_date']),
                ];

                if ($primaryDocumentPath) {
                    $licenseAttributes['attachment_path'] = $primaryDocumentPath;
                }

                HarborLicense::query()->updateOrCreate(
                    ['license_number' => $attributes['license_number']],
                    $licenseAttributes,
                );

                return $submission->load(['port', 'boat', 'captain']);
            });
        } catch (Throwable $exception) {
            if ($storedPaths !== []) {
                $this->filesystem->disk('local')->delete($storedPaths);
            }

            throw $exception;
        }
    }

    private function storeFile(
        InformationSubmission $submission,
        string $category,
        UploadedFile $file,
    ): string {
        $path = $this->filesystem->disk('local')->putFileAs(
            'information/'.$submission->reference_no.'/'.Str::slug($category),
            $file,
            Str::uuid().'.'.$file->extension(),
        );

        if (! is_string($path)) {
            throw new RuntimeException('Unable to store an information submission file.');
        }

        return $path;
    }

    private function newReference(): string
    {
        do {
            $reference = 'INFO-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
        } while (InformationSubmission::query()->where('reference_no', $reference)->exists());

        return $reference;
    }

    private function licenseStatus(string $expiryDate): string
    {
        return $expiryDate < today()->format('Y-m-d') ? 'expired' : 'valid';
    }
}
