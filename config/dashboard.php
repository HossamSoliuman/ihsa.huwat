<?php

return [
    'navigation' => [
        ['route' => 'dashboard.admin', 'active' => 'dashboard.admin', 'label' => 'الرئيسية', 'group' => 'الرئيسية', 'roles' => ['super_admin']],
        ['route' => 'dashboard.region-overview.index', 'active' => 'dashboard.region-overview.*', 'label' => 'لوحة المنطقة', 'group' => 'العمليات', 'roles' => ['super_admin', 'region_manager']],
        ['route' => 'dashboard.governorate-overview.index', 'active' => 'dashboard.governorate-overview.*', 'label' => 'لوحة المحافظة', 'group' => 'العمليات', 'roles' => ['super_admin', 'region_manager', 'gov_supervisor']],
        ['route' => 'dashboard.port-operations.index', 'active' => 'dashboard.port-operations.*', 'label' => 'مركز عمليات الميناء', 'group' => 'العمليات', 'roles' => ['super_admin', 'gov_supervisor', 'port_supervisor']],
        ['route' => 'dashboard.reports.index', 'active' => 'dashboard.reports.*', 'label' => 'التقارير والتحليلات', 'group' => 'الرقابة', 'roles' => ['super_admin', 'region_manager', 'gov_supervisor']],
        ['route' => 'dashboard.profile.show', 'active' => 'dashboard.profile.*', 'label' => 'ملفي الوظيفي', 'group' => 'الخدمات الذاتية', 'roles' => ['employee_portal']],
        ['route' => 'dashboard.employee-operations.index', 'active' => 'dashboard.employee-operations.*', 'label' => 'عمليات الإحصاء', 'group' => 'العمليات', 'roles' => ['stat_employee']],
        ['route' => 'dashboard.payroll.index', 'active' => 'dashboard.payroll.*', 'label' => 'الرواتب والمستحقات', 'group' => 'الموارد البشرية', 'roles' => ['super_admin', 'finance_officer', 'hr_manager']],
        ['route' => 'dashboard.hr.index', 'active' => 'dashboard.hr.*', 'label' => 'الموارد البشرية', 'group' => 'الموارد البشرية', 'roles' => ['super_admin', 'hr_manager']],
        ['route' => 'dashboard.employee-performance.index', 'active' => 'dashboard.employee-performance.*', 'label' => 'أداء موظفي الإحصاء', 'group' => 'الرقابة', 'roles' => ['super_admin', 'hr_manager', 'gov_supervisor']],
        ['route' => 'dashboard.coverage.index', 'active' => 'dashboard.coverage.*', 'label' => 'التغطية الجغرافية', 'group' => 'الرقابة', 'roles' => ['super_admin', 'region_manager']],
        ['route' => 'dashboard.attendance.index', 'active' => 'dashboard.attendance.*', 'label' => 'الحضور والمناوبات', 'group' => 'الموارد البشرية', 'roles' => ['super_admin', 'hr_manager', 'port_supervisor']],
        ['route' => 'dashboard.alerts.index', 'active' => 'dashboard.alerts.*', 'label' => 'التنبيهات والرقابة', 'group' => 'الرقابة', 'roles' => ['super_admin', 'gov_supervisor', 'port_supervisor']],
        ['route' => 'dashboard.master-data.index', 'active' => 'dashboard.master-data.*', 'label' => 'البيانات الأساسية', 'group' => 'الإدارة', 'roles' => ['super_admin']],
        ['route' => 'dashboard.trips.index', 'active' => 'dashboard.trips.*', 'label' => 'القوارب والرحلات', 'group' => 'العمليات', 'roles' => ['super_admin', 'gov_supervisor', 'port_supervisor', 'stat_employee']],
        ['route' => 'dashboard.harbors.index', 'active' => 'dashboard.harbors.*', 'label' => 'إدارة المرافئ', 'group' => 'العمليات', 'roles' => ['super_admin', 'region_manager', 'gov_supervisor', 'port_supervisor']],
        ['route' => 'dashboard.discrepancies.index', 'active' => 'dashboard.discrepancies.*', 'label' => 'الفروقات وجودة البيانات', 'group' => 'الجودة', 'roles' => ['super_admin', 'port_supervisor', 'quality_supervisor']],
        ['route' => 'dashboard.jobs.index', 'active' => 'dashboard.jobs.*', 'label' => 'الفرص الوظيفية', 'group' => 'التوظيف', 'roles' => ['super_admin', 'hr_manager']],
        ['route' => 'dashboard.applications.index', 'active' => 'dashboard.applications.*', 'label' => 'طلبات التوظيف', 'group' => 'التوظيف', 'roles' => ['super_admin', 'hr_manager']],
    ],
];
