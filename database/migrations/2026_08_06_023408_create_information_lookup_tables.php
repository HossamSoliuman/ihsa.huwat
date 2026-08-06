<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Option lists the portal form renders as dropdowns. They shipped hard-coded in
     * `config/information.php`; the table below takes over so the information centre
     * can edit them, and the config keeps serving as the seed and the fallback.
     *
     * @var list<string>
     */
    private const OPTION_GROUPS = [
        'nationalities',
        'boat_types',
        'boat_classifications',
        'hull_materials',
        'crew_roles',
        'fishing_methods',
        'fishing_tool_types',
        'fishing_tool_materials',
        'fishing_tool_conditions',
    ];

    public function up(): void
    {
        Schema::create('cities', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('governorate_id');
            $table->string('name', 150);
            $table->timestamp('created_at')->useCurrent();
            $table->foreign('governorate_id')->references('id')->on('governorates')->cascadeOnDelete();
            $table->unique(['governorate_id', 'name']);
        });

        Schema::create('lookup_options', function (Blueprint $table) {
            $table->increments('id');
            $table->string('group', 60);
            $table->string('value', 60);
            $table->string('label', 150);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['group', 'value']);
        });

        $this->seedOptions();
        $this->seedCities();
    }

    public function down(): void
    {
        Schema::dropIfExists('lookup_options');
        Schema::dropIfExists('cities');
    }

    /** Copies the shipped defaults across, keeping their stored keys and their order. */
    private function seedOptions(): void
    {
        $rows = [];

        foreach (self::OPTION_GROUPS as $group) {
            $sortOrder = 0;

            foreach ((array) config('information.'.$group, []) as $value => $label) {
                $rows[] = [
                    'group' => $group,
                    'value' => (string) $value,
                    'label' => (string) $label,
                    'sort_order' => $sortOrder += 10,
                    'is_active' => true,
                ];
            }
        }

        if ($rows !== []) {
            DB::table('lookup_options')->insert($rows);
        }
    }

    /**
     * The city field used to be free text, so the cities already typed by applicants
     * become the starting list — nobody has to rebuild it before the portal reopens.
     */
    private function seedCities(): void
    {
        if (! Schema::hasTable('information_submissions')) {
            return;
        }

        $governorateIds = DB::table('governorates')->pluck('id', 'name');

        $rows = DB::table('information_submissions')
            ->select('owner_city', 'owner_governorate')
            ->whereNotNull('owner_city')
            ->where('owner_city', '<>', '')
            ->distinct()
            ->get()
            ->map(fn (object $submission): ?array => $governorateIds->has($submission->owner_governorate)
                ? [
                    'governorate_id' => $governorateIds->get($submission->owner_governorate),
                    'name' => trim($submission->owner_city),
                ]
                : null)
            ->filter()
            ->unique(fn (array $city): string => $city['governorate_id'].'|'.$city['name'])
            ->values()
            ->all();

        if ($rows !== []) {
            DB::table('cities')->insert($rows);
        }
    }
};
