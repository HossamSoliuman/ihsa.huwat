<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The nine option lists used to share one `lookup_options` table keyed by a group
     * column. Each list now owns a table, so the rows the information centre has already
     * edited are carried across before the shared table goes.
     *
     * The group name and the table name match one for one.
     *
     * @var list<string>
     */
    private const LISTS = [
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
        if (! Schema::hasTable('lookup_options')) {
            return;
        }

        foreach (self::LISTS as $list) {
            $rows = DB::table('lookup_options')
                ->where('group', $list)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
                ->map(fn (object $option): array => [
                    'code' => $option->value,
                    'name' => $option->label,
                    'sort_order' => $option->sort_order,
                    'is_active' => $option->is_active,
                    'created_at' => $option->created_at,
                    'updated_at' => $option->created_at,
                ])
                ->all();

            if ($rows !== []) {
                DB::table($list)->insert($rows);
            }
        }

        Schema::drop('lookup_options');
    }

    public function down(): void
    {
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

        foreach (self::LISTS as $list) {
            $rows = DB::table($list)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
                ->map(fn (object $option): array => [
                    'group' => $list,
                    'value' => $option->code,
                    'label' => $option->name,
                    'sort_order' => $option->sort_order,
                    'is_active' => $option->is_active,
                    'created_at' => $option->created_at,
                ])
                ->all();

            if ($rows !== []) {
                DB::table('lookup_options')->insert($rows);
            }
        }
    }
};
