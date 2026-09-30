<?php

namespace App\Support;

/**
 * المبلغ كتابةً بالعربية — `amount_to_words` في hispa:
 * "ثلاثة عشر ألف وسبعمائة وثلاثون ريال سعودي فقط لا غير"، والهللات تُذكر إن
 * لم تكن صفرًا. المبلغ السالب يُسبق بـ"سالب".
 */
class AmountInWords
{
    private const ONES = ['', 'واحد', 'اثنان', 'ثلاثة', 'أربعة', 'خمسة', 'ستة', 'سبعة', 'ثمانية', 'تسعة', 'عشرة',
        'أحد عشر', 'اثنا عشر', 'ثلاثة عشر', 'أربعة عشر', 'خمسة عشر', 'ستة عشر', 'سبعة عشر', 'ثمانية عشر', 'تسعة عشر'];

    private const TENS = ['', '', 'عشرون', 'ثلاثون', 'أربعون', 'خمسون', 'ستون', 'سبعون', 'ثمانون', 'تسعون'];

    private const HUNDREDS = ['', 'مائة', 'مائتان', 'ثلاثمائة', 'أربعمائة', 'خمسمائة', 'ستمائة', 'سبعمائة', 'ثمانمائة', 'تسعمائة'];

    private const SCALES = [
        ['', '', ''],
        ['ألف', 'ألفان', 'آلاف'],
        ['مليون', 'مليونان', 'ملايين'],
        ['مليار', 'ملياران', 'مليارات'],
    ];

    public static function riyals(float $amount): string
    {
        $amount = round($amount, 2);
        $negative = $amount < 0;
        $amount = abs($amount);
        $riyals = (int) floor($amount);
        $halalas = (int) round(($amount - $riyals) * 100);

        $words = self::integer($riyals).' ريال سعودي';
        if ($halalas > 0) {
            $words .= ' و'.self::integer($halalas).' هللة';
        }

        return ($negative ? 'سالب ' : '').$words.' فقط لا غير';
    }

    public static function integer(int $number): string
    {
        if ($number === 0) {
            return 'صفر';
        }

        $groups = [];
        while ($number > 0) {
            $groups[] = $number % 1000;
            $number = intdiv($number, 1000);
        }

        $parts = [];
        for ($i = count($groups) - 1; $i >= 0; $i--) {
            $g = $groups[$i];

            if ($g === 0) {
                continue;
            }

            if ($i === 0) {
                $parts[] = self::threeDigits($g);

                continue;
            }

            [$singular, $dual, $plural] = self::SCALES[$i];
            $parts[] = match (true) {
                $g === 1 => $singular,
                $g === 2 => $dual,
                $g <= 10 => self::threeDigits($g).' '.$plural,
                default => self::threeDigits($g).' '.$singular,
            };
        }

        return implode(' و', $parts);
    }

    private static function threeDigits(int $n): string
    {
        $parts = [];
        $h = intdiv($n, 100);
        $rest = $n % 100;

        if ($h > 0) {
            $parts[] = self::HUNDREDS[$h];
        }

        if ($rest > 0) {
            $u = $rest % 10;
            $t = intdiv($rest, 10);
            $parts[] = match (true) {
                $rest < 20 => self::ONES[$rest],
                $u > 0 => self::ONES[$u].' و'.self::TENS[$t],
                default => self::TENS[$t],
            };
        }

        return implode(' و', $parts);
    }
}
