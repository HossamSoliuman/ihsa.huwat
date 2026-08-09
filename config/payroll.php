<?php

return [
    'monthly_hours' => 240,
    'monthly_days' => 30,
    'overtime_multiplier' => 1.5,
    'loan_number_prefix' => 'LN',
    'run_number_prefix' => 'PR',

    'run_statuses' => [
        'draft' => 'مسودة',
        'calculated' => 'محسوب',
        'approved' => 'معتمد',
        'paid' => 'مصروف',
        'closed' => 'مغلق',
    ],

    'loan_statuses' => [
        'requested' => 'قيد المراجعة',
        'approved' => 'معتمدة',
        'active' => 'قيد السداد',
        'completed' => 'مكتملة',
        'cancelled' => 'ملغاة',
    ],

    'adjustment_statuses' => [
        'draft' => 'مسودة',
        'approved' => 'معتمد',
        'consumed' => 'مرحّل للمسير',
    ],

    'months' => [
        1 => 'يناير', 2 => 'فبراير', 3 => 'مارس', 4 => 'أبريل',
        5 => 'مايو', 6 => 'يونيو', 7 => 'يوليو', 8 => 'أغسطس',
        9 => 'سبتمبر', 10 => 'أكتوبر', 11 => 'نوفمبر', 12 => 'ديسمبر',
    ],
];
