<?php

return [
    'navigation' => [
        ['route' => 'dashboard.admin', 'active' => 'dashboard.admin', 'label' => 'الرئيسية', 'icon' => 'home', 'group' => 'نظرة عامة', 'roles' => ['super_admin']],
        ['route' => 'dashboard.region-overview.index', 'active' => 'dashboard.region-overview.*', 'label' => 'المنطقة', 'icon' => 'map', 'group' => 'نظرة عامة', 'roles' => ['super_admin', 'region_manager']],
        ['route' => 'dashboard.governorate-overview.index', 'active' => 'dashboard.governorate-overview.*', 'label' => 'المحافظة', 'icon' => 'globe', 'group' => 'نظرة عامة', 'roles' => ['super_admin', 'region_manager', 'gov_supervisor']],
        ['route' => 'dashboard.port-operations.index', 'active' => 'dashboard.port-operations.*', 'label' => 'عمليات الميناء', 'icon' => 'anchor', 'group' => 'نظرة عامة', 'roles' => ['super_admin', 'gov_supervisor', 'port_supervisor']],
        ['route' => 'dashboard.profile.show', 'active' => 'dashboard.profile.*', 'label' => 'ملفي', 'icon' => 'id-card', 'group' => 'حسابي', 'roles' => ['employee_portal']],
        ['route' => 'dashboard.employee-operations.index', 'active' => 'dashboard.employee-operations.*', 'label' => 'الإحصاء', 'icon' => 'clipboard', 'group' => 'العمل اليومي', 'roles' => ['stat_employee']],
        ['route' => 'dashboard.trips.index', 'active' => 'dashboard.trips.*', 'label' => 'الرحلات', 'icon' => 'ship', 'group' => 'العمل اليومي', 'roles' => ['super_admin', 'gov_supervisor', 'port_supervisor', 'stat_employee']],
        ['route' => 'dashboard.harbors.index', 'active' => 'dashboard.harbors.*', 'label' => 'المرافئ', 'icon' => 'anchor', 'group' => 'العمل اليومي', 'roles' => ['super_admin', 'region_manager', 'gov_supervisor', 'port_supervisor']],
        ['route' => 'dashboard.attendance.index', 'active' => 'dashboard.attendance.*', 'label' => 'الحضور', 'icon' => 'clock', 'group' => 'العمل اليومي', 'roles' => ['super_admin', 'hr_manager', 'port_supervisor']],
        ['route' => 'dashboard.reports.index', 'active' => 'dashboard.reports.*', 'label' => 'التقارير', 'icon' => 'file-text', 'group' => 'المتابعة', 'roles' => ['super_admin', 'region_manager', 'gov_supervisor']],
        ['route' => 'dashboard.employee-performance.index', 'active' => 'dashboard.employee-performance.*', 'label' => 'أداء الموظفين', 'icon' => 'bar-chart', 'group' => 'المتابعة', 'roles' => ['super_admin', 'hr_manager', 'gov_supervisor']],
        ['route' => 'dashboard.coverage.index', 'active' => 'dashboard.coverage.*', 'label' => 'التغطية', 'icon' => 'map', 'group' => 'المتابعة', 'roles' => ['super_admin', 'region_manager']],
        ['route' => 'dashboard.alerts.index', 'active' => 'dashboard.alerts.*', 'label' => 'التنبيهات', 'icon' => 'bell', 'group' => 'المتابعة', 'roles' => ['super_admin', 'gov_supervisor', 'port_supervisor']],
        ['route' => 'dashboard.discrepancies.index', 'active' => 'dashboard.discrepancies.*', 'label' => 'جودة البيانات', 'icon' => 'alert-triangle', 'group' => 'المتابعة', 'roles' => ['super_admin', 'port_supervisor', 'quality_supervisor']],
        ['route' => 'dashboard.hr.index', 'active' => 'dashboard.hr.*', 'label' => 'الموظفون', 'icon' => 'users', 'group' => 'الإدارة', 'roles' => ['super_admin', 'hr_manager']],
        ['route' => 'dashboard.payroll.index', 'active' => 'dashboard.payroll.*', 'label' => 'الرواتب', 'icon' => 'dollar-sign', 'group' => 'الإدارة', 'roles' => ['super_admin', 'finance_officer', 'hr_manager']],
        ['route' => 'dashboard.master-data.index', 'active' => 'dashboard.master-data.*', 'label' => 'البيانات الأساسية', 'icon' => 'database', 'group' => 'الإدارة', 'roles' => ['super_admin']],
        ['route' => 'dashboard.jobs.index', 'active' => 'dashboard.jobs.*', 'label' => 'الوظائف', 'icon' => 'briefcase', 'group' => 'الإدارة', 'roles' => ['super_admin', 'hr_manager']],
        ['route' => 'dashboard.applications.index', 'active' => 'dashboard.applications.*', 'label' => 'طلبات التوظيف', 'icon' => 'file-text', 'group' => 'الإدارة', 'roles' => ['super_admin', 'hr_manager']],
    ],
];
