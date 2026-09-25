<?php

namespace App\Services\Owner;

use App\Models\Asset;
use App\Models\Boat;
use App\Models\BoatInspection;
use App\Models\FishingEquipment;
use App\Models\FleetDocument;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * أصول الأسطول (O2): المعدات ومواسمها وترحيل شرائها، الأصول وحالة التخلّص،
 * الفحوصات وموعد القارب القادم، والوثائق ومرفقاتها. الويب يستدعي هذه وحدها.
 */
class FleetService
{
    public function __construct(private readonly ExpenseService $expenses) {}

    // ── المعدات ─────────────────────────────────────────────

    public function saveEquipment(User $owner, array $data, ?FishingEquipment $equipment = null): FishingEquipment
    {
        return DB::transaction(function () use ($owner, $data, $equipment) {
            $equipment ??= new FishingEquipment(['owner_id' => $owner->id]);
            $equipment->fill([
                'name' => $data['name'],
                'boat_id' => $data['boat_id'] ?? null,
                'gear_type_id' => $data['gear_type_id'] ?? null,
                'vendor_id' => $data['vendor_id'] ?? null,
                'quantity' => (int) $data['quantity'],
                'unit_cost' => (float) ($data['unit_cost'] ?? 0),
                'purchase_date' => $data['purchase_date'] ?? null,
                'condition' => $data['condition'] ?? FishingEquipment::CONDITIONS[0],
                'notes' => $data['notes'] ?? null,
            ])->save();

            $equipment->seasons()->sync($data['season_ids'] ?? []);
            $this->expenses->syncSource($equipment, $owner);

            return $equipment;
        });
    }

    public function deleteEquipment(User $owner, FishingEquipment $equipment): void
    {
        DB::transaction(function () use ($owner, $equipment) {
            $this->expenses->releaseSource($equipment, $owner);
            $equipment->delete();
        });
    }

    // ── الأصول ──────────────────────────────────────────────

    /**
     * الأصل النشط لا تاريخ تخلّص له؛ المباع أو التالف يحمل تاريخه (وقيمة البيع إن وُجدت).
     */
    public function saveAsset(User $owner, array $data, ?Asset $asset = null): Asset
    {
        $active = ($data['status'] ?? Asset::ACTIVE) === Asset::ACTIVE;

        $asset ??= new Asset(['owner_id' => $owner->id]);
        $asset->fill([
            'asset_type_id' => $data['asset_type_id'],
            'boat_id' => $data['boat_id'] ?? null,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'purchase_date' => $data['purchase_date'],
            'purchase_cost' => (float) $data['purchase_cost'],
            'salvage_value' => (float) ($data['salvage_value'] ?? 0),
            'useful_life_years' => (int) $data['useful_life_years'],
            'status' => $data['status'] ?? Asset::ACTIVE,
            'disposed_at' => $active ? null : $data['disposed_at'],
            'disposal_value' => $active ? null : ($data['disposal_value'] ?? null),
            'notes' => $data['notes'] ?? null,
        ])->save();

        return $asset;
    }

    // ── الفحوصات ────────────────────────────────────────────

    /**
     * موعد الفحص القادم افتراضيًا بعد سنة إلا عشرة أيام (قاعدة hispa)، وآخر
     * فحص للقارب يحدّث `boats.next_inspection_date`.
     */
    public function saveInspection(array $data, ?UploadedFile $attachment = null, bool $removeAttachment = false, ?BoatInspection $inspection = null): BoatInspection
    {
        return DB::transaction(function () use ($data, $attachment, $removeAttachment, $inspection) {
            $previousBoat = $inspection?->boat_id;
            $inspection ??= new BoatInspection;

            $inspection->fill([
                'boat_id' => $data['boat_id'],
                'inspection_date' => $data['inspection_date'],
                'next_due_date' => $data['next_due_date'] ?? Carbon::parse($data['inspection_date'])->addYear()->subDays(10)->toDateString(),
                'inspector' => $data['inspector'] ?? null,
                'result' => $data['result'] ?? BoatInspection::RESULTS[0],
                'notes' => $data['notes'] ?? null,
            ]);

            $this->replaceAttachment($inspection, $attachment, $removeAttachment, "inspections/{$data['boat_id']}");
            $inspection->save();

            $this->syncNextInspection($inspection->boat_id);

            if ($previousBoat && $previousBoat !== $inspection->boat_id) {
                $this->syncNextInspection($previousBoat);
            }

            return $inspection;
        });
    }

    public function deleteInspection(BoatInspection $inspection): void
    {
        $this->deleteAttachment($inspection);
        $inspection->delete();
        $this->syncNextInspection($inspection->boat_id);
    }

    public function syncNextInspection(int $boatId): void
    {
        $latest = BoatInspection::where('boat_id', $boatId)->orderByDesc('inspection_date')->orderByDesc('id')->first();

        Boat::whereKey($boatId)->update(['next_inspection_date' => $latest?->next_due_date]);
    }

    // ── الوثائق ─────────────────────────────────────────────

    public function saveDocument(User $owner, array $data, ?UploadedFile $attachment = null, bool $removeAttachment = false, ?FleetDocument $document = null): FleetDocument
    {
        $document ??= new FleetDocument(['owner_id' => $owner->id]);
        $document->fill([
            'documentable_type' => FleetDocument::HOLDERS[$data['holder_type']],
            'documentable_id' => $data['holder_id'],
            'document_type_id' => $data['document_type_id'],
            'number' => $data['number'] ?? null,
            'issue_date' => $data['issue_date'] ?? null,
            'expiry_date' => $data['expiry_date'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        $this->replaceAttachment($document, $attachment, $removeAttachment, "documents/{$owner->id}");
        $document->save();

        return $document;
    }

    public function deleteDocument(FleetDocument $document): void
    {
        $this->deleteAttachment($document);
        $document->delete();
    }

    private function replaceAttachment(BoatInspection|FleetDocument $record, ?UploadedFile $attachment, bool $remove, string $folder): void
    {
        if ($remove || $attachment) {
            $this->deleteAttachment($record);
        }

        if ($attachment) {
            $record->attachment_path = $attachment->store($folder, 'public');
        }
    }

    private function deleteAttachment(BoatInspection|FleetDocument $record): void
    {
        if ($record->attachment_path) {
            Storage::disk('public')->delete($record->attachment_path);
            $record->attachment_path = null;
        }
    }
}
