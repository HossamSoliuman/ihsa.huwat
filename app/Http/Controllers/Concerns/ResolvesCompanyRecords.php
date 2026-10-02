<?php

namespace App\Http\Controllers\Concerns;

use App\Models\CounterApplication;
use App\Models\HiringRound;
use App\Models\OperatingCompany;
use App\Models\Port;
use App\Models\StatisticsOfficer;
use App\Models\User;

/**
 * سجلات بوابة الشركة تُقرأ مقيّدةً بشركة الموظف: جولة أو طلب أو عدّاد أو
 * ميناء لشركة أخرى = 404. وموظف بلا شركة، أو شركته موقوفة، = 403.
 */
trait ResolvesCompanyRecords
{
    protected function company(User $user): OperatingCompany
    {
        $company = $user->operatingCompany;

        abort_if($company === null, 403, 'حسابك غير مربوط بشركة تشغيل — راجع المدير العام.');
        abort_unless($company->isActive(), 403, 'الشركة موقوفة — راجع المدير العام.');

        return $company;
    }

    protected function companyRound(OperatingCompany $company, int|string $id): HiringRound
    {
        return $company->hiringRounds()->findOrFail($id);
    }

    protected function companyApplication(OperatingCompany $company, int|string $id): CounterApplication
    {
        return $company->applications()->verified()->findOrFail($id);
    }

    protected function companyCounter(OperatingCompany $company, int|string $id): StatisticsOfficer
    {
        return $company->counters()->findOrFail($id);
    }

    protected function companyPort(OperatingCompany $company, int|string $id): Port
    {
        return $company->ports()->findOrFail($id);
    }
}
