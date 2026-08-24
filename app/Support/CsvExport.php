<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * تنزيل مجموعة صفوف كملف CSV.
 *
 * يُكتب الملف ببادئة BOM لأن Excel على ويندوز يقرأ CSV بترميز النظام ما لم
 * يجدها، فتظهر العناوين العربية مشوّهة بدونها.
 */
class CsvExport
{
    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     */
    public static function download(Collection $rows, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");

            if ($rows->isNotEmpty()) {
                fputcsv($handle, array_keys($rows->first()));

                foreach ($rows as $row) {
                    fputcsv($handle, array_values($row));
                }
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
