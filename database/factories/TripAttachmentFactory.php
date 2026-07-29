<?php

namespace Database\Factories;

use App\Models\Trip;
use App\Models\TripAttachment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TripAttachment>
 */
class TripAttachmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'trip_id' => Trip::factory(),
            'type' => 'scale_photo',
            'file_path' => 'trips/testing/attachment.pdf',
            'uploaded_at' => now(),
        ];
    }
}
