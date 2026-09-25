<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Asset;
use App\Models\Boat;
use App\Models\BoatInspection;
use App\Models\BoatMaintenance;
use App\Models\Consignment;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Fisher;
use App\Models\FishingEquipment;
use App\Models\FleetDocument;
use App\Models\OwnerEmployee;
use App\Models\Role;
use App\Models\Sale;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vendor;

/**
 * سجلات المالك تُقرأ دائمًا مقيّدةً به: معرّف يخصّ مالكًا آخر = 404، لا
 * تسريب بين الملاك في الويب ولا في الـAPI.
 */
trait ResolvesOwnerRecords
{
    protected function ownedBoat(User $owner, int|string $id): Boat
    {
        return Boat::forOwner($owner)->findOrFail($id);
    }

    protected function ownedMaintenance(User $owner, int|string $id): BoatMaintenance
    {
        return BoatMaintenance::whereHas('boat', fn ($q) => $q->where('owner_id', $owner->id))->findOrFail($id);
    }

    protected function ownedCaptain(User $owner, int|string $id): User
    {
        return $owner->staff()->whereHas('appRole', fn ($q) => $q->where('key', Role::CAPTAIN))->findOrFail($id);
    }

    protected function ownedCrew(User $owner, int|string $id): Fisher
    {
        return Fisher::forOwner($owner)->crew()->findOrFail($id);
    }

    protected function ownedEmployee(User $owner, int|string $id): OwnerEmployee
    {
        return OwnerEmployee::forOwner($owner)->findOrFail($id);
    }

    protected function ownedCustomer(User $owner, int|string $id): Customer
    {
        return Customer::forAccount($owner)->findOrFail($id);
    }

    protected function ownedVendor(User $owner, int|string $id): Vendor
    {
        return Vendor::forOwner($owner)->findOrFail($id);
    }

    protected function ownedTrip(User $owner, int|string $id): Trip
    {
        return Trip::forOwner($owner)->findOrFail($id);
    }

    protected function ownedSale(User $owner, int|string $id): Sale
    {
        return Sale::forSeller($owner)->findOrFail($id);
    }

    protected function ownedConsignment(User $owner, int|string $id): Consignment
    {
        return Consignment::forOwner($owner)->findOrFail($id);
    }

    protected function ownedExpense(User $owner, int|string $id): Expense
    {
        return Expense::forOwner($owner)->findOrFail($id);
    }

    protected function ownedEquipment(User $owner, int|string $id): FishingEquipment
    {
        return FishingEquipment::forOwner($owner)->findOrFail($id);
    }

    protected function ownedAsset(User $owner, int|string $id): Asset
    {
        return Asset::forOwner($owner)->findOrFail($id);
    }

    protected function ownedInspection(User $owner, int|string $id): BoatInspection
    {
        return BoatInspection::forOwner($owner)->findOrFail($id);
    }

    protected function ownedDocument(User $owner, int|string $id): FleetDocument
    {
        return FleetDocument::forOwner($owner)->findOrFail($id);
    }
}
