<?php

namespace Database\Factories;

use App\Models\Boat;
use App\Models\DocumentType;
use App\Models\FleetDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FleetDocument>
 */
class FleetDocumentFactory extends Factory
{
    protected $model = FleetDocument::class;

    public function definition(): array
    {
        return [
            'documentable_type' => Boat::class,
            'documentable_id' => Boat::factory(),
            'owner_id' => fn (array $attributes) => Boat::find($attributes['documentable_id'])?->owner_id,
            'document_type_id' => DocumentType::factory(),
            'number' => fake()->numerify('DOC-#####'),
            'issue_date' => now()->subYear()->toDateString(),
            'expiry_date' => now()->addMonths(6)->toDateString(),
        ];
    }
}
