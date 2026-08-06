<?php

namespace Database\Seeders;

use App\Models\FishFamily;
use Illuminate\Database\Seeder;

class FishFamilySeeder extends Seeder
{
    /**
     * The families of the national catch coding sheet, as
     * [code, scientific name, English name, Gulf local name, Red Sea local name].
     * Every code lands on a hundred; the species filed under it share its first two digits.
     *
     * @var list<array{0: int, 1: string, 2: ?string, 3: ?string, 4: ?string}>
     */
    private const FAMILIES = [
        [1000, 'chirocentridae', 'Wolf-herring', 'أسماك الحف', 'اسماك الدرب'],
        [1100, 'Chanidae', 'Milkfish', 'الأسماك اللبنية', 'الأسماك اللبنية'],
        [1200, 'Bothiidae', 'Lefteye flounder', 'الأسماك المفلطحة', 'الأسماك المفلطحة'],
        [1300, 'Ariidae', 'Seacatfishes', 'أسماك القطوة', 'أسماك أبو شنب من عائلة الكمل'],
        [1400, 'Synodontidae', 'Lizardfishes', 'أسماك الحاسوم', 'أسماك المكرونة'],
        [1500, 'Serranidae', 'Groupers', 'اسماك الهامور', 'أسماك الكشر'],
        [1600, 'Lutjanidae', 'Snappers / Jobfishes', 'أسماك الحمرة', 'أسماك النهاشات'],
        [1700, 'Nemipteridae', 'Threadfin breams', 'الشعوم خيطية الزعانف', 'مجموعة المرجان'],
        [1800, 'Haemulidae', 'Grunts, sweetlips', 'أسماك الشخل والمطوع', 'أسماك القطرين / الناقم'],
        [1900, 'Lethrinidae', 'Emperors', 'اسماك الشعري', 'أسماك الشعور'],
        [2000, 'Sparidae', 'Seabreams', 'أسماك الشعوم', 'أسماك الشعوم البحرية'],
        [2100, 'Mullidae', 'Goatfishes', 'أسماك الحامر', 'أسماك أبودقن / عنبر'],
        [2200, 'Acanthuridae', 'Surgionfish, Unicornfish', null, 'الأسماك الجراحة ووحيدة القرن'],
        [2300, 'Holocentridae', 'Squirrelfishes / Soliderfishes', null, 'الأسماك السنجابية'],
        [2400, 'Labridae', 'Wrasses', null, 'أسماك الترباني'],
        [2500, 'Gerreidae', 'Mojarras', 'أسماك البدح', 'أسماك القاص'],
        [2600, 'Scaridae', 'Parrotfishes', 'الأسماك الببغائية', 'أسماك الحريد'],
        [2700, 'Siganidae', 'Rabbitfish', 'الصافي', 'اسماك السيجان'],
        [2800, 'Balistidae', 'Triggerfishes', 'الأسماك الخنزيرية', 'حجوم'],
        [2900, 'Ephippidae', 'Spadefishes, batfishes &scats', null, 'أسماك الوطواط'],
        [3000, 'Priacanthidae', 'Bigeye or catalufas', 'عائلة الحمرور', 'عائلة أبو شرار'],
        [3100, 'Pomacanthidae', 'Angelfishes', 'الأسماك الملائكية', 'الأسماك الملائكية'],
        [3200, 'Teraponidae', 'Terapoonfishes', 'أسماك الزمرور', 'أسماك الجربوع'],
        [3300, 'Belonidae', 'Needlefishes', 'أسماك الحاقول', 'أسماك الخرم'],
        [3400, 'Sphyraenidae', 'Barracudas', 'أسماك الجد والادويلمي', 'أسماك البركودا'],
        [3500, 'Mugilidae', 'Mullets', 'أسماك البياح', 'أسماك العربي'],
        [3600, 'Rachycentridae', 'Cobias', 'أسماك الكوبيا', 'أسماك السخلة / الخوت'],
        [3700, 'Caesionidae', 'Fusiliers', 'أسماك الديايوة والخطاف', 'أسماك عيده'],
        [3800, 'Tetraodontidae', 'Puffers', 'أسماك الفوجل ( الفقل )', 'أسماك الدرمه'],
        [3900, 'Carangidae', 'Jacks, Trevallies, & Scads', 'أسماك الحمام والخضرة', 'أسماك البياض'],
        [4000, 'Stromatidae', 'Pomfrets', 'أسماك الزبيدي', 'أسماك الزبيدي'],
        [5000, 'Clupeidae', 'Sardin, Herring', 'أسماك الجواف', 'أسماك السردين'],
        [5100, 'Scombridae', 'Mackerels and tunas', 'أسماك الكنعد والتبان', 'أسماك الدراك والتونة'],
        [5200, 'Istiophoridae', 'Sailfish, Marlin', null, 'أسماك فرس البحر وأبوشراع'],
        [5300, 'Kyphosidae', 'Sea chubs', null, 'أسماك التهمل'],
        [5400, 'Platycephalidae', 'Flatheads', 'أسماك الوحره', 'أسماك الرقاد'],
        [5500, 'Carcharhinidae', 'Requiem sharks', 'أسماك الجرجور', 'أسماك القرش'],
        [5600, 'Dasyatidae', 'Stingrays', 'أسماك اللخم', 'أسماك الرقيطات'],
        [5700, 'Penaeidae', 'Prawns and Shrimps', 'الربيان', 'الجمبري'],
        [5800, 'Portunidae', 'Crabs', 'القبقب', 'السرطان / الكبوريا'],
        [5900, 'Loliginidae & Sepiidae', 'Squids and cuttlefishes', 'الخثاق والحبار', 'الحبار'],
        [6000, 'Octopodidae', 'Octopuses', null, 'أخطبوط'],
        [6100, 'Leiognathidae', 'Ponyfishes', 'أسماك تراجي', 'أسماك أبو قرص'],
        [6200, 'Psettodidae', 'Spiny turbots', null, null],
        [6300, 'Sillaginidae', 'Sillagos', 'أسماك الحاسوم', 'أسماك الشحاميه'],
        [6400, 'Albulidae', 'Bonefish', 'بون', 'أسماك البنك'],
        [6500, 'Miscellaneous', 'Mixed and other fishes', 'مخلط', 'مخلط'],
    ];

    public function run(): void
    {
        $rows = array_map(fn (array $family): array => [
            'code' => $family[0],
            'scientific_name' => $family[1],
            'english_name' => $family[2],
            'local_name_gulf' => $family[3],
            'local_name_red_sea' => $family[4],
        ], self::FAMILIES);

        FishFamily::query()->upsert($rows, ['code'], [
            'scientific_name',
            'english_name',
            'local_name_gulf',
            'local_name_red_sea',
        ]);
    }
}
