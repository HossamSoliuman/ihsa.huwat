<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * القوائم المرجعية لبوابات التطبيق — كل قائمة بذّارها المستقل، وهذا يجمعها
 * بترتيب واحد حتى يُستدعى في الإنتاج بأمر واحد: db:seed --class=LookupSeeder.
 */
class LookupSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            BoatCategorySeeder::class,
            BoatTypeSeeder::class,
            MaintenanceTypeSeeder::class,
            TripTypeSeeder::class,
            FisherRoleSeeder::class,
            IdTypeSeeder::class,
            JobTitleSeeder::class,
            PaymentMethodSeeder::class,
            PaymentStatusSeeder::class,
            CustomerTypeSeeder::class,
            StockMovementTypeSeeder::class,
            NotificationTypeSeeder::class,
        ]);
    }
}
