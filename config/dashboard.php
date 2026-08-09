<?php

return [
    'navigation' => [
        ['route' => 'dashboard.admin', 'active' => 'dashboard.admin', 'label' => 'الرئيسية', 'icon' => 'home', 'group' => 'نظرة عامة', 'roles' => ['super_admin']],
        ['route' => 'dashboard.harbors.index', 'active' => 'dashboard.harbors.*', 'label' => 'المرافئ', 'icon' => 'anchor', 'group' => 'نظرة عامة', 'roles' => ['super_admin', 'region_manager', 'gov_supervisor', 'port_supervisor']],
        ['route' => 'dashboard.profile.show', 'active' => 'dashboard.profile.*', 'label' => 'ملفي', 'icon' => 'id-card', 'group' => 'حسابي', 'roles' => ['employee_portal']],
        ['route' => 'dashboard.employee-operations.index', 'active' => 'dashboard.employee-operations.*', 'label' => 'الإحصاء', 'icon' => 'clipboard', 'group' => 'العمل اليومي', 'roles' => ['stat_employee']],
        ['route' => 'dashboard.trips.index', 'active' => 'dashboard.trips.*', 'label' => 'الرحلات', 'icon' => 'ship', 'group' => 'العمل اليومي', 'roles' => ['super_admin', 'gov_supervisor', 'port_supervisor', 'stat_employee']],
        ['route' => 'dashboard.reports.index', 'active' => 'dashboard.reports.*', 'label' => 'التقارير', 'icon' => 'file-text', 'group' => 'المتابعة', 'roles' => ['super_admin', 'region_manager', 'gov_supervisor']],
        ['route' => 'dashboard.employee-performance.index', 'active' => 'dashboard.employee-performance.*', 'label' => 'أداء الموظفين', 'icon' => 'bar-chart', 'group' => 'المتابعة', 'roles' => ['super_admin', 'hr_manager', 'gov_supervisor']],
        ['route' => 'dashboard.coverage.index', 'active' => 'dashboard.coverage.*', 'label' => 'التغطية', 'icon' => 'map', 'group' => 'المتابعة', 'roles' => ['super_admin', 'region_manager']],
        ['route' => 'dashboard.alerts.index', 'active' => 'dashboard.alerts.*', 'label' => 'التنبيهات', 'icon' => 'bell', 'group' => 'المتابعة', 'roles' => ['super_admin', 'gov_supervisor', 'port_supervisor']],
        ['route' => 'dashboard.discrepancies.index', 'active' => 'dashboard.discrepancies.*', 'label' => 'جودة البيانات', 'icon' => 'alert-triangle', 'group' => 'المتابعة', 'roles' => ['super_admin', 'port_supervisor', 'quality_supervisor']],
        ['route' => 'dashboard.hr.index', 'active' => 'dashboard.hr.index', 'label' => 'نظرة عامة', 'icon' => 'users', 'group' => 'الموارد البشرية', 'roles' => ['super_admin', 'hr_manager']],
        ['route' => 'dashboard.hr.employees.index', 'active' => 'dashboard.hr.employees.*', 'label' => 'الموظفون', 'icon' => 'users', 'group' => 'الموارد البشرية', 'roles' => ['super_admin', 'hr_manager', 'finance_officer']],
        ['route' => 'dashboard.attendance.index', 'active' => 'dashboard.attendance.*', 'label' => 'الحضور', 'icon' => 'clock', 'group' => 'الموارد البشرية', 'roles' => ['super_admin', 'hr_manager', 'port_supervisor']],
        ['route' => 'dashboard.payroll.index', 'active' => 'dashboard.payroll.*', 'label' => 'مسيرات الرواتب', 'icon' => 'dollar-sign', 'group' => 'الرواتب', 'roles' => ['super_admin', 'finance_officer', 'hr_manager']],
        ['route' => 'dashboard.hr.adjustments.index', 'active' => 'dashboard.hr.adjustments.*', 'label' => 'الاستحقاقات والخصومات', 'icon' => 'file-text', 'group' => 'الرواتب', 'roles' => ['super_admin', 'finance_officer', 'hr_manager']],
        ['route' => 'dashboard.hr.loans.index', 'active' => 'dashboard.hr.loans.*', 'label' => 'السلف', 'icon' => 'credit-card', 'group' => 'الرواتب', 'roles' => ['super_admin', 'finance_officer', 'hr_manager']],
        ['route' => 'dashboard.master-data.index', 'active' => 'dashboard.master-data.*', 'label' => 'البيانات الأساسية', 'icon' => 'database', 'group' => 'الإدارة', 'roles' => ['super_admin']],
        ['route' => 'dashboard.jobs.index', 'active' => 'dashboard.jobs.*', 'label' => 'الوظائف', 'icon' => 'briefcase', 'group' => 'التوظيف', 'roles' => ['super_admin', 'hr_manager']],
        ['route' => 'dashboard.applications.index', 'active' => 'dashboard.applications.*', 'label' => 'طلبات التوظيف', 'icon' => 'file-text', 'group' => 'التوظيف', 'roles' => ['super_admin', 'hr_manager']],
        ['route' => 'dashboard.hr.settings.index', 'active' => 'dashboard.hr.settings.*', 'label' => 'إعدادات الموارد البشرية', 'icon' => 'settings', 'group' => 'الإعدادات', 'roles' => ['super_admin', 'hr_manager']],
    ],
];
