<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LegacyDataSeeder extends Seeder
{
    public function run(): void
    {
        $data = $this->data();

        DB::transaction(function () use ($data): void {
            $this->upsertById('regions', $data['regions']);
            $this->upsertById('governorates', $data['governorates']);
            $this->upsertById('ports', $data['ports']);

            $roleIds = $this->seedRoles($data['roles']);
            $shiftIds = $this->seedShifts($data['shifts']);
            $this->seedFishSpecies($data['fish_species']);
            $userIds = $this->seedUsers($data['users'], $roleIds);

            $this->seedBoats($data['boats']);
            $this->seedHarborBoatCapacities($data['harbor_boat_capacities']);
            $this->upsertById('harbor_workers', $data['harbor_workers']);

            $jobIds = $this->seedEmploymentJobs($data['employment_jobs'], $userIds);
            $applicationIds = $this->seedEmploymentApplications(
                $data['employment_applications'],
                $jobIds,
                $userIds,
            );
            $this->seedEmploymentApplicationAttachments(
                $data['employment_application_attachments'],
                $applicationIds,
            );
            $this->seedEmploymentApplicationEvents(
                $data['employment_application_events'],
                $applicationIds,
                $userIds,
            );

            $employeeIds = $this->seedEmployees($data['employees'], $userIds, $applicationIds);
            $this->seedEmployeeAssignments(
                $data['employee_assignments'],
                $employeeIds,
                $shiftIds,
            );
            $this->seedAttendance($data['attendance'], $employeeIds, $shiftIds);
            $this->seedLoginAttempts($data['login_attempts']);
        });
    }

    /**
     * @param  list<array<string, mixed>>  $roles
     * @return array<int, int>
     */
    private function seedRoles(array $roles): array
    {
        $roleIds = [];

        foreach ($roles as $role) {
            DB::table('roles')->updateOrInsert(
                ['code' => $role['code']],
                Arr::except($role, ['id', 'code']),
            );

            $roleIds[(int) $role['id']] = (int) DB::table('roles')
                ->where('code', $role['code'])
                ->value('id');
        }

        return $roleIds;
    }

    /**
     * @param  list<array<string, mixed>>  $shifts
     * @return array<int, int>
     */
    private function seedShifts(array $shifts): array
    {
        $shiftIds = [];

        foreach ($shifts as $shift) {
            $code = (string) $shift['name'];

            DB::table('shifts')->updateOrInsert(
                ['code' => $code],
                [
                    ...Arr::except($shift, ['id', 'name']),
                    'name' => (string) config('attendance.shifts.'.$code, $code),
                    'crosses_midnight' => (string) $shift['end_time'] <= (string) $shift['start_time'],
                    'grace_minutes' => 15,
                    'is_active' => true,
                ],
            );

            $shiftIds[(int) $shift['id']] = (int) DB::table('shifts')
                ->where('code', $code)
                ->value('id');
        }

        return $shiftIds;
    }

    /** @param list<array<string, mixed>> $fishSpecies */
    private function seedFishSpecies(array $fishSpecies): void
    {
        /** The name is no longer unique in the table — the coding sheet repeats plenty of them — so the check is made here. */
        foreach ($fishSpecies as $species) {
            DB::table('fish_species')->updateOrInsert(['name_ar' => $species['name_ar']]);
        }
    }

    /**
     * @param  list<array<string, mixed>>  $users
     * @param  array<int, int>  $roleIds
     * @return array<int, int>
     */
    private function seedUsers(array $users, array $roleIds): array
    {
        $userIds = [];

        foreach ($users as $user) {
            $attributes = Arr::except($user, ['id', 'username']);
            $attributes['role_id'] = $roleIds[(int) $user['role_id']];

            DB::table('users')->updateOrInsert(
                ['username' => $user['username']],
                $attributes,
            );

            $userIds[(int) $user['id']] = (int) DB::table('users')
                ->where('username', $user['username'])
                ->value('id');
        }

        return $userIds;
    }

    /** @param list<array<string, mixed>> $boats */
    private function seedBoats(array $boats): void
    {
        foreach ($boats as $boat) {
            DB::table('boats')->updateOrInsert(
                ['registration_no' => $boat['registration_no']],
                Arr::except($boat, ['id', 'registration_no']),
            );
        }
    }

    /** @param list<array<string, mixed>> $capacities */
    private function seedHarborBoatCapacities(array $capacities): void
    {
        foreach ($capacities as $capacity) {
            DB::table('harbor_boat_capacities')->updateOrInsert(
                Arr::only($capacity, ['port_id', 'boat_type']),
                Arr::except($capacity, ['id', 'port_id', 'boat_type']),
            );
        }
    }

    /**
     * @param  list<array<string, mixed>>  $jobs
     * @param  array<int, int>  $userIds
     * @return array<int, int>
     */
    private function seedEmploymentJobs(array $jobs, array $userIds): array
    {
        $jobIds = [];

        foreach ($jobs as $job) {
            $attributes = Arr::except($job, ['id', 'reference_no']);
            $attributes['created_by'] = $userIds[(int) $job['created_by']];
            $attributes['updated_by'] = $this->mappedNullableId($userIds, $job['updated_by']);

            DB::table('employment_jobs')->updateOrInsert(
                ['reference_no' => $job['reference_no']],
                $attributes,
            );

            $jobIds[(int) $job['id']] = (int) DB::table('employment_jobs')
                ->where('reference_no', $job['reference_no'])
                ->value('id');
        }

        return $jobIds;
    }

    /**
     * @param  list<array<string, mixed>>  $applications
     * @param  array<int, int>  $jobIds
     * @param  array<int, int>  $userIds
     * @return array<int, int>
     */
    private function seedEmploymentApplications(
        array $applications,
        array $jobIds,
        array $userIds,
    ): array {
        $applicationIds = [];

        foreach ($applications as $application) {
            $attributes = Arr::except($application, ['id', 'reference_no']);
            $attributes['job_id'] = $jobIds[(int) $application['job_id']];
            $attributes['reviewed_by'] = $this->mappedNullableId($userIds, $application['reviewed_by']);
            $attributes['employee_user_id'] = $this->mappedNullableId(
                $userIds,
                $application['employee_user_id'],
            );

            DB::table('employment_applications')->updateOrInsert(
                ['reference_no' => $application['reference_no']],
                $attributes,
            );

            $applicationIds[(int) $application['id']] = (int) DB::table('employment_applications')
                ->where('reference_no', $application['reference_no'])
                ->value('id');
        }

        return $applicationIds;
    }

    /**
     * @param  list<array<string, mixed>>  $attachments
     * @param  array<int, int>  $applicationIds
     */
    private function seedEmploymentApplicationAttachments(
        array $attachments,
        array $applicationIds,
    ): void {
        foreach ($attachments as &$attachment) {
            $attachment['application_id'] = $applicationIds[(int) $attachment['application_id']];
        }
        unset($attachment);

        $this->upsertById('employment_application_attachments', $attachments);
    }

    /**
     * @param  list<array<string, mixed>>  $events
     * @param  array<int, int>  $applicationIds
     * @param  array<int, int>  $userIds
     */
    private function seedEmploymentApplicationEvents(
        array $events,
        array $applicationIds,
        array $userIds,
    ): void {
        foreach ($events as &$event) {
            $event['application_id'] = $applicationIds[(int) $event['application_id']];
            $event['actor_user_id'] = $this->mappedNullableId($userIds, $event['actor_user_id']);
        }
        unset($event);

        $this->upsertById('employment_application_events', $events);
    }

    /**
     * @param  list<array<string, mixed>>  $employees
     * @param  array<int, int>  $userIds
     * @param  array<int, int>  $applicationIds
     * @return array<int, int>
     */
    private function seedEmployees(
        array $employees,
        array $userIds,
        array $applicationIds,
    ): array {
        $employeeIds = [];
        $nextEmployeeNumber = 1;
        $nextContractNumber = 1;

        foreach ($employees as $employee) {
            $legacyEmployeeId = (int) $employee['id'];
            $userId = $userIds[(int) $employee['user_id']];
            $applicationId = $this->mappedNullableId(
                $applicationIds,
                $employee['employment_application_id'],
            );
            $application = $applicationId === null
                ? null
                : DB::table('employment_applications')->where('id', $applicationId)->first();
            $employeeNumber = filled($employee['employee_number'])
                ? (string) $employee['employee_number']
                : $this->nextSeededNumber('employees', 'employee_number', (string) config('employment.employee_number_prefix', 'HWT'), $nextEmployeeNumber);

            DB::table('employees')->updateOrInsert(
                ['user_id' => $userId],
                [
                    'employment_application_id' => $applicationId,
                    'employee_number' => $employeeNumber,
                    'national_id' => $employee['national_id'],
                    'nationality' => $this->nationalityCode($application?->nationality),
                    'date_of_birth' => $application?->birth_date,
                    'gender' => $application?->gender,
                    'phone' => $application?->mobile,
                    'email' => $application?->email ?? DB::table('users')->where('id', $userId)->value('email'),
                    'department_id' => $this->employmentLookupId('departments', $employee['department']),
                    'job_title_id' => $this->employmentLookupId('job_titles', $employee['job_title']),
                    'port_id' => $application?->preferred_port_id,
                    'hire_date' => $employee['hire_date'],
                    'status' => $employee['status'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );

            $employeeIds[$legacyEmployeeId] = (int) DB::table('employees')
                ->where('user_id', $userId)
                ->value('id');

            $basicSalaryComponentId = (int) DB::table('salary_components')
                ->where('code', 'basic')
                ->value('id');
            DB::table('employee_salary_components')->updateOrInsert(
                [
                    'employee_id' => $employeeIds[$legacyEmployeeId],
                    'salary_component_id' => $basicSalaryComponentId,
                    'effective_from' => $employee['hire_date'],
                ],
                [
                    'amount' => $employee['base_salary'],
                    'percentage' => null,
                    'effective_to' => null,
                    'created_by' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );

            $contractNumber = DB::table('employee_contracts')
                ->where('employee_id', $employeeIds[$legacyEmployeeId])
                ->value('contract_number')
                ?? $this->nextSeededNumber('employee_contracts', 'contract_number', (string) config('employment.contract_number_prefix', 'HWT-C'), $nextContractNumber);

            DB::table('employee_contracts')->updateOrInsert(
                ['employee_id' => $employeeIds[$legacyEmployeeId], 'contract_number' => $contractNumber],
                [
                    'contract_type' => $employee['contract_type'],
                    'start_date' => $employee['hire_date'],
                    'end_date' => $employee['contract_end_date'],
                    'working_hours_per_day' => 8,
                    'working_days_per_week' => 6,
                    'status' => 'active',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }

        return $employeeIds;
    }

    /**
     * @param  list<array<string, mixed>>  $assignments
     * @param  array<int, int>  $employeeIds
     * @param  array<int, int>  $shiftIds
     */
    private function seedEmployeeAssignments(
        array $assignments,
        array $employeeIds,
        array $shiftIds,
    ): void {
        foreach ($assignments as $assignment) {
            $assignment['employee_id'] = $employeeIds[(int) $assignment['employee_id']];
            $assignment['shift_id'] = $shiftIds[(int) $assignment['shift_id']];

            DB::table('employee_assignments')->updateOrInsert(
                Arr::only($assignment, ['employee_id', 'assignment_date']),
                Arr::except($assignment, ['id', 'employee_id', 'assignment_date']),
            );

            DB::table('employees')
                ->where('id', $assignment['employee_id'])
                ->whereNull('port_id')
                ->update(['port_id' => $assignment['port_id'], 'updated_at' => now()]);
        }
    }

    private function employmentLookupId(string $table, mixed $name): ?int
    {
        $name = trim((string) $name);

        if ($name === '') {
            return null;
        }

        $existingId = DB::table($table)->where('name', $name)->value('id');

        if ($existingId !== null) {
            return (int) $existingId;
        }

        $code = Str::of($name)->slug('_')->limit(60, '')->toString();

        if ($code === '') {
            $code = 'legacy_'.Str::substr(hash('sha256', $name), 0, 12);
        }

        return (int) DB::table($table)->insertGetId([
            'code' => $code,
            'name' => $name,
            'sort_order' => (int) DB::table($table)->max('sort_order') + 10,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function nationalityCode(mixed $nationality): ?string
    {
        if ($nationality === null) {
            return null;
        }

        $nationalities = (array) config('information.nationalities', []);

        if (array_key_exists((string) $nationality, $nationalities)) {
            return (string) $nationality;
        }

        $code = array_search((string) $nationality, $nationalities, true);

        return $code === false ? null : (string) $code;
    }

    private function nextSeededNumber(string $table, string $column, string $prefix, int &$next): string
    {
        do {
            $number = $prefix.'-'.str_pad((string) $next++, 5, '0', STR_PAD_LEFT);
        } while (DB::table($table)->where($column, $number)->exists());

        return $number;
    }

    /**
     * @param  list<array<string, mixed>>  $attendanceRows
     * @param  array<int, int>  $employeeIds
     * @param  array<int, int>  $shiftIds
     */
    private function seedAttendance(
        array $attendanceRows,
        array $employeeIds,
        array $shiftIds,
    ): void {
        foreach ($attendanceRows as $attendance) {
            $attendance['employee_id'] = $employeeIds[(int) $attendance['employee_id']];
            $attendance['shift_id'] = $shiftIds[(int) $attendance['shift_id']];

            DB::table('attendance')->updateOrInsert(
                Arr::only($attendance, ['employee_id', 'attendance_date', 'shift_id']),
                Arr::except($attendance, ['id', 'employee_id', 'attendance_date', 'shift_id']),
            );
        }
    }

    /** @param list<array<string, mixed>> $loginAttempts */
    private function seedLoginAttempts(array $loginAttempts): void
    {
        foreach ($loginAttempts as $loginAttempt) {
            $attributes = Arr::except($loginAttempt, ['id']);

            if (! DB::table('login_attempts')->where($attributes)->exists()) {
                DB::table('login_attempts')->insert($attributes);
            }
        }
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function upsertById(string $table, array $rows): void
    {
        if ($rows === []) {
            return;
        }

        DB::table($table)->upsert(
            $rows,
            ['id'],
            array_values(array_diff(array_keys($rows[0]), ['id'])),
        );
    }

    /** @param array<int, int> $idMap */
    private function mappedNullableId(array $idMap, mixed $legacyId): ?int
    {
        if ($legacyId === null) {
            return null;
        }

        return $idMap[(int) $legacyId];
    }

    /** @return array<string, list<array<string, mixed>>> */
    private function data(): array
    {
        return json_decode(
            json: <<<'JSON'
{
    "attendance": [
        {
            "id": 1,
            "employee_id": 1,
            "attendance_date": "2026-07-27",
            "shift_id": 1,
            "check_in": "2026-07-27 17:26:17",
            "check_out": "2026-07-27 17:26:27",
            "status": "late"
        },
        {
            "id": 2,
            "employee_id": 2,
            "attendance_date": "2026-07-28",
            "shift_id": 1,
            "check_in": null,
            "check_out": null,
            "status": "absent"
        }
    ],
    "boats": [
        {
            "id": 1,
            "name": "شاكر",
            "registration_no": "16211",
            "boat_type": "small",
            "harbor_status": "occupied",
            "home_port_id": 93
        }
    ],
    "employees": [
        {
            "id": 1,
            "user_id": 3,
            "employment_application_id": null,
            "employee_number": null,
            "national_id": null,
            "job_title": null,
            "department": null,
            "job_grade": null,
            "supervisor_name": null,
            "supervisor_phone": null,
            "hire_date": "2026-07-27",
            "contract_type": "permanent",
            "contract_end_date": "2027-01-27",
            "base_salary": 0,
            "status": "active"
        },
        {
            "id": 2,
            "user_id": 4,
            "employment_application_id": 1,
            "employee_number": "EMP-2026-000001",
            "national_id": "1111698112",
            "job_title": "أخصائي موارد بشرية",
            "department": "إدارة الموارد البشرية",
            "job_grade": null,
            "supervisor_name": null,
            "supervisor_phone": null,
            "hire_date": "2026-07-27",
            "contract_type": "permanent",
            "contract_end_date": null,
            "base_salary": 7500,
            "status": "active"
        }
    ],
    "employee_assignments": [
        {
            "id": 1,
            "employee_id": 1,
            "port_id": 93,
            "shift_id": 1,
            "assignment_date": "2026-07-27",
            "is_temporary": 0
        },
        {
            "id": 2,
            "employee_id": 2,
            "port_id": 48,
            "shift_id": 1,
            "assignment_date": "2026-07-28",
            "is_temporary": 0
        }
    ],
    "employment_applications": [
        {
            "id": 1,
            "job_id": 4,
            "reference_no": "APP-F69541F80B7FEFE6D044FFE0",
            "status": "account_created",
            "full_name": "خالد محمد عمر اليعقوبي",
            "nationality": "سعودي",
            "identity_type": "national_id",
            "identity_number": "1111698112",
            "birth_date": "2001-05-18",
            "gender": "male",
            "marital_status": "single",
            "children_count": 0,
            "mobile": "0535002843",
            "phone": null,
            "email": "khyaquobi@gmail.com",
            "city": "القنفذة",
            "address": "الخالدية شارع الرياض",
            "preferred_port_id": 93,
            "work_type": "full_time",
            "source": "website",
            "education_level": "bachelor",
            "specialization": "لغة انجليزية",
            "institution": "ام القرى",
            "graduation_year": 2023,
            "experience_years": 3,
            "current_employer": "مؤسسة دار الحوت التجارية",
            "current_job_title": "مراقب اعمال",
            "professional_summary": "الاشراف على رواتب الصيادين و ايداعها \nالاشراف على العلاقات الحكومية",
            "skills": "تحليل البيانات , الموارد البشرية , الاكسل , اللغة الانجليزية",
            "availability_date": "2026-08-01",
            "cover_letter": "انا خالد محمد اليعقوبي ارى اني مناسب لهذة الوظيفة بناء على خبراتي ومهاراتي المذكورة",
            "consent": 1,
            "admin_note": null,
            "reviewed_by": 1,
            "reviewed_at": "2026-07-27 17:05:33",
            "accepted_at": "2026-07-27 20:02:09",
            "employee_user_id": 4,
            "submitted_at": "2026-07-27 16:59:12",
            "created_at": "2026-07-27 16:59:12",
            "updated_at": "2026-07-27 17:05:33"
        }
    ],
    "employment_application_attachments": [
        {
            "id": 1,
            "application_id": 1,
            "attachment_type": "cv",
            "original_name": "Khalid - HR specialist.pdf",
            "stored_path": "storage/employment_uploads/APP-F69541F80B7FEFE6D044FFE0/1b7f1c6ae5297c68a82982f50785c394a683.pdf",
            "mime_type": "application/pdf",
            "file_size": 34201,
            "created_at": "2026-07-27 16:59:12"
        },
        {
            "id": 2,
            "application_id": 1,
            "attachment_type": "identity",
            "original_name": "IMG_5902.pdf",
            "stored_path": "storage/employment_uploads/APP-F69541F80B7FEFE6D044FFE0/8c822ed0d006870c669741b6e83d4fbf6e29.pdf",
            "mime_type": "application/pdf",
            "file_size": 2566664,
            "created_at": "2026-07-27 16:59:12"
        },
        {
            "id": 3,
            "application_id": 1,
            "attachment_type": "certificate",
            "original_name": "��وثيقة التخرج- طبق الأصل�.pdf",
            "stored_path": "storage/employment_uploads/APP-F69541F80B7FEFE6D044FFE0/9128cce2b4567ce9127794e84094d756ed81.pdf",
            "mime_type": "application/pdf",
            "file_size": 931460,
            "created_at": "2026-07-27 16:59:12"
        },
        {
            "id": 4,
            "application_id": 1,
            "attachment_type": "certificate",
            "original_name": "��شهادة إدارة الموارد البشرية� 2.pdf",
            "stored_path": "storage/employment_uploads/APP-F69541F80B7FEFE6D044FFE0/4d89b3c91359afe1aad112a6000315414fb9.pdf",
            "mime_type": "application/pdf",
            "file_size": 876422,
            "created_at": "2026-07-27 16:59:12"
        },
        {
            "id": 5,
            "application_id": 1,
            "attachment_type": "certificate",
            "original_name": "��شهادة مهارات تحليل البيانات بإستخدام برنامج Microsoft Excel�.pdf",
            "stored_path": "storage/employment_uploads/APP-F69541F80B7FEFE6D044FFE0/5dbc6e3cc63f97c786f50891c51529818966.pdf",
            "mime_type": "application/pdf",
            "file_size": 830323,
            "created_at": "2026-07-27 16:59:12"
        }
    ],
    "employment_application_events": [
        {
            "id": 1,
            "application_id": 1,
            "event_type": "submitted",
            "from_status": null,
            "to_status": "submitted",
            "note": "تم إرسال الطلب عبر بوابة التوظيف العامة.",
            "actor_user_id": null,
            "created_at": "2026-07-27 16:59:12"
        },
        {
            "id": 2,
            "application_id": 1,
            "event_type": "status_changed",
            "from_status": "submitted",
            "to_status": "under_review",
            "note": null,
            "actor_user_id": 1,
            "created_at": "2026-07-27 17:00:24"
        },
        {
            "id": 3,
            "application_id": 1,
            "event_type": "status_changed",
            "from_status": "under_review",
            "to_status": "interview",
            "note": null,
            "actor_user_id": 1,
            "created_at": "2026-07-27 17:01:49"
        },
        {
            "id": 4,
            "application_id": 1,
            "event_type": "status_changed",
            "from_status": "interview",
            "to_status": "accepted",
            "note": null,
            "actor_user_id": 1,
            "created_at": "2026-07-27 17:02:09"
        },
        {
            "id": 5,
            "application_id": 1,
            "event_type": "account_created",
            "from_status": "accepted",
            "to_status": "account_created",
            "note": "تم إنشاء حساب الموظف وربطه بالرقم الوظيفي EMP-2026-000001.",
            "actor_user_id": 1,
            "created_at": "2026-07-27 17:05:33"
        }
    ],
    "employment_jobs": [
        {
            "id": 1,
            "reference_no": "JOB-SEED-001",
            "title_ar": "موظف إحصاء مصيد",
            "department": "إدارة الإحصاء الميداني",
            "summary": "تسجيل بيانات رحلات الصيد والتحقق من كميات وأنواع المصيد في الميناء.",
            "description": "ينضم موظف الإحصاء إلى فريق العمل الميداني المسؤول عن توثيق حركة القوارب وبيانات المصيد اليومية ورفعها بدقة إلى النظام.",
            "responsibilities": "استقبال رحلات الصيد وتسجيل بياناتها\r\nمراجعة أوزان وأنواع المصيد\r\nالتنسيق مع مشرف الميناء\r\nإعداد ملخص العمل اليومي",
            "requirements": "ثانوية او دبلوم أو بكالوريوس \r\nإجادة استخدام الحاسب والأجهزة اللوحية\r\nالقدرة على العمل الميداني وبنظام المناوبات\r\nالدقة والالتزام",
            "employment_type": "full_time",
            "vacancies": 6,
            "port_id": 93,
            "city": "القنفذة",
            "salary_min": null,
            "salary_max": null,
            "application_deadline": "2026-08-31",
            "status": "open",
            "published_at": "2026-07-27 16:39:54",
            "created_by": 1,
            "updated_by": 1,
            "created_at": "2026-07-27 16:39:54",
            "updated_at": "2026-07-27 18:13:27"
        },
        {
            "id": 2,
            "reference_no": "JOB-SEED-002",
            "title_ar": "مراقب جودة البيانات",
            "department": "إدارة الجودة والامتثال",
            "summary": "مراجعة بيانات المصيد والرحلات واكتشاف الفروقات قبل اعتماد السجلات.",
            "description": "يتولى مراقب الجودة فحص السجلات التشغيلية ومطابقتها مع بيانات الوزن والوصول، مع متابعة الملاحظات حتى إغلاقها.",
            "responsibilities": "مراجعة السجلات اليومية\nتحليل حالات الاختلاف\nتوثيق نتائج التدقيق\nمتابعة الإجراءات التصحيحية",
            "requirements": "بكالوريوس في الإحصاء أو إدارة الجودة أو تخصص ذي صلة\nخبرة في مراجعة البيانات\nإجادة الجداول الإلكترونية\nمهارات تحليل وكتابة تقارير",
            "employment_type": "full_time",
            "vacancies": 2,
            "port_id": null,
            "city": "الرياض",
            "salary_min": 7000,
            "salary_max": 9000,
            "application_deadline": "2026-09-07",
            "status": "closed",
            "published_at": "2026-07-27 16:39:54",
            "created_by": 1,
            "updated_by": 1,
            "created_at": "2026-07-27 16:39:54",
            "updated_at": "2026-07-27 18:14:28"
        },
        {
            "id": 3,
            "reference_no": "JOB-SEED-003",
            "title_ar": "مشرف عمليات ميناء",
            "department": "إدارة عمليات الموانئ",
            "summary": "الإشراف على فريق الإحصاء وضمان انتظام تغطية الرحلات وجودة التسجيل اليومي.",
            "description": "يقود مشرف العمليات الفريق الميداني داخل الميناء، وينظم المناوبات ويتابع حركة الوصول وجودة تنفيذ إجراءات التسجيل.",
            "responsibilities": "إعداد خطة المناوبات\r\nتوزيع المهام ومتابعة الحضور\r\nمراجعة تقارير الرحلات\r\nرفع التنبيهات التشغيلية للإدارة",
            "requirements": "بكالوريوس في الإدارة أو تخصص مناسب\r\nخبرة لا تقل عن ثلاث سنوات في الإشراف التشغيلي\r\nمهارات قيادة وتواصل\r\nالاستعداد للعمل الميداني",
            "employment_type": "full_time",
            "vacancies": 1,
            "port_id": 16,
            "city": "أملج",
            "salary_min": null,
            "salary_max": null,
            "application_deadline": "2026-08-26",
            "status": "open",
            "published_at": "2026-07-27 16:39:54",
            "created_by": 1,
            "updated_by": 1,
            "created_at": "2026-07-27 16:39:54",
            "updated_at": "2026-07-27 18:16:00"
        },
        {
            "id": 4,
            "reference_no": "JOB-SEED-004",
            "title_ar": "أخصائي موارد بشرية",
            "department": "إدارة الموارد البشرية",
            "summary": "دعم عمليات التوظيف وإدارة ملفات الموظفين والعقود والتقارير الدورية.",
            "description": "يساند الأخصائي دورة حياة الموظف من الاستقطاب وحتى تحديث الملفات، ويضمن اكتمال الوثائق ودقة بيانات الموارد البشرية.",
            "responsibilities": "فرز طلبات التوظيف\r\nتنسيق المقابلات\r\nتحديث ملفات الموظفين والعقود\r\nإعداد تقارير الموارد البشرية",
            "requirements": "بكالوريوس في الموارد البشرية أو إدارة الأعمال\r\nخبرة عملية لا تقل عن سنتين\r\nمعرفة بأنظمة الموارد البشرية\r\nسرية عالية وتنظيم ممتاز",
            "employment_type": "full_time",
            "vacancies": 1,
            "port_id": null,
            "city": "الرياض",
            "salary_min": null,
            "salary_max": null,
            "application_deadline": "2026-09-10",
            "status": "open",
            "published_at": "2026-07-27 16:39:54",
            "created_by": 1,
            "updated_by": 1,
            "created_at": "2026-07-27 16:39:54",
            "updated_at": "2026-07-27 18:15:43"
        },
        {
            "id": 5,
            "reference_no": "JOB-SEED-005",
            "title_ar": "محلل بيانات مصايد",
            "department": "إدارة التحليل والتقارير",
            "summary": "تحليل مؤشرات الرحلات والإنتاج وبناء تقارير تساعد الإدارات على اتخاذ القرار.",
            "description": "يحوّل محلل البيانات السجلات التشغيلية إلى مؤشرات واضحة، ويتابع اتجاهات الإنتاج وجودة التغطية ويرصد الأنماط غير المعتادة.",
            "responsibilities": "إعداد لوحات المؤشرات\nتحليل اتجاهات المصيد\nالتحقق من جودة مجموعات البيانات\nتقديم تقارير وتوصيات دورية",
            "requirements": "بكالوريوس في الإحصاء أو علوم البيانات أو نظم المعلومات\nإجادة Excel وأدوات ذكاء الأعمال\nمعرفة جيدة بقواعد البيانات\nقدرة على عرض النتائج بوضوح",
            "employment_type": "contract",
            "vacancies": 2,
            "port_id": null,
            "city": "جدة",
            "salary_min": 9000,
            "salary_max": 12000,
            "application_deadline": "2026-09-15",
            "status": "closed",
            "published_at": "2026-07-27 16:39:54",
            "created_by": 1,
            "updated_by": 1,
            "created_at": "2026-07-27 16:39:54",
            "updated_at": "2026-07-27 18:15:09"
        },
        {
            "id": 6,
            "reference_no": "JOB-SEED-006",
            "title_ar": "منسق السلامة البحرية",
            "department": "إدارة السلامة والتشغيل",
            "summary": "متابعة اشتراطات السلامة في نقاط العمل الميدانية وتوثيق الملاحظات والإجراءات.",
            "description": "يتابع المنسق جاهزية مواقع العمل ويلتزم بجولات السلامة المجدولة، كما يرفع الملاحظات ويتابع معالجتها مع فرق التشغيل.",
            "responsibilities": "تنفيذ جولات السلامة\nتوثيق المخاطر والملاحظات\nمتابعة معدات الوقاية\nالمشاركة في التوعية وخطط الطوارئ",
            "requirements": "دبلوم أو بكالوريوس في السلامة المهنية أو تخصص ذي صلة\nشهادة سلامة معتمدة ميزة إضافية\nالقدرة على العمل في المواقع البحرية\nمهارات توثيق ومتابعة",
            "employment_type": "full_time",
            "vacancies": 1,
            "port_id": 10,
            "city": "الخفجي",
            "salary_min": 6500,
            "salary_max": 8500,
            "application_deadline": "2026-09-05",
            "status": "archived",
            "published_at": "2026-07-27 16:39:54",
            "created_by": 1,
            "updated_by": 1,
            "created_at": "2026-07-27 16:39:54",
            "updated_at": "2026-07-28 20:04:22"
        }
    ],
    "fish_species": [
        {
            "id": 1,
            "name_ar": "هامور"
        },
        {
            "id": 4,
            "name_ar": "الكنعد"
        },
        {
            "id": 11,
            "name_ar": "ربيان جامبو"
        },
        {
            "id": 12,
            "name_ar": "ربيان كبير"
        },
        {
            "id": 13,
            "name_ar": "ربيان وسط"
        },
        {
            "id": 14,
            "name_ar": "ربيان خشن"
        },
        {
            "id": 15,
            "name_ar": "ربيان ابيض"
        },
        {
            "id": 16,
            "name_ar": "ربيان احمر"
        },
        {
            "id": 17,
            "name_ar": "كابوريا"
        },
        {
            "id": 18,
            "name_ar": "سمك موسى"
        },
        {
            "id": 19,
            "name_ar": "مرجان"
        },
        {
            "id": 20,
            "name_ar": "قاص"
        },
        {
            "id": 21,
            "name_ar": "ناجم"
        },
        {
            "id": 22,
            "name_ar": "شعور"
        },
        {
            "id": 23,
            "name_ar": "مكرونة مصري"
        },
        {
            "id": 24,
            "name_ar": "مكرونة هندي"
        },
        {
            "id": 25,
            "name_ar": "كمل"
        },
        {
            "id": 26,
            "name_ar": "بياض"
        },
        {
            "id": 27,
            "name_ar": "قرش"
        },
        {
            "id": 28,
            "name_ar": "ديراك"
        },
        {
            "id": 29,
            "name_ar": "عقام"
        },
        {
            "id": 30,
            "name_ar": "مشكل"
        },
        {
            "id": 31,
            "name_ar": "ناجل"
        },
        {
            "id": 32,
            "name_ar": "طرادي"
        },
        {
            "id": 33,
            "name_ar": "شعفل"
        },
        {
            "id": 34,
            "name_ar": "فارس"
        },
        {
            "id": 35,
            "name_ar": "ابو بصيل"
        },
        {
            "id": 36,
            "name_ar": "ابو عين"
        },
        {
            "id": 37,
            "name_ar": "ثمد"
        },
        {
            "id": 38,
            "name_ar": "تونه"
        },
        {
            "id": 39,
            "name_ar": "بهار"
        },
        {
            "id": 40,
            "name_ar": "حريد"
        },
        {
            "id": 41,
            "name_ar": "لوسن"
        },
        {
            "id": 42,
            "name_ar": "سردين"
        },
        {
            "id": 43,
            "name_ar": "باغه"
        },
        {
            "id": 44,
            "name_ar": "عربي"
        },
        {
            "id": 45,
            "name_ar": "خني"
        },
        {
            "id": 46,
            "name_ar": "قرمع"
        }
    ],
    "governorates": [
        {
            "id": 3,
            "region_id": 9,
            "name": "الخفجي",
            "created_at": "2026-07-25 16:11:31"
        },
        {
            "id": 5,
            "region_id": 10,
            "name": "القطان",
            "created_at": "2026-07-25 16:12:06"
        },
        {
            "id": 6,
            "region_id": 12,
            "name": "الوجه",
            "created_at": "2026-07-25 16:19:48"
        },
        {
            "id": 7,
            "region_id": 14,
            "name": "ينبع",
            "created_at": "2026-07-25 16:24:52"
        },
        {
            "id": 8,
            "region_id": 12,
            "name": "الصورة",
            "created_at": "2026-07-25 16:25:36"
        },
        {
            "id": 9,
            "region_id": 10,
            "name": "الوسقة",
            "created_at": "2026-07-25 16:25:47"
        },
        {
            "id": 11,
            "region_id": 13,
            "name": "صير",
            "created_at": "2026-07-25 16:26:07"
        },
        {
            "id": 12,
            "region_id": 9,
            "name": "الثقبة",
            "created_at": "2026-07-25 16:26:17"
        },
        {
            "id": 13,
            "region_id": 12,
            "name": "الحره",
            "created_at": "2026-07-25 16:26:39"
        },
        {
            "id": 14,
            "region_id": 12,
            "name": "املج",
            "created_at": "2026-07-25 16:26:47"
        },
        {
            "id": 15,
            "region_id": 10,
            "name": "رأس محيسن",
            "created_at": "2026-07-25 16:26:59"
        },
        {
            "id": 16,
            "region_id": 10,
            "name": "القنفذة",
            "created_at": "2026-07-25 16:27:10"
        },
        {
            "id": 17,
            "region_id": 14,
            "name": "خليص",
            "created_at": "2026-07-25 16:27:21"
        },
        {
            "id": 18,
            "region_id": 14,
            "name": "النباة",
            "created_at": "2026-07-25 16:28:23"
        },
        {
            "id": 19,
            "region_id": 13,
            "name": "السقيد",
            "created_at": "2026-07-25 16:28:46"
        },
        {
            "id": 21,
            "region_id": 10,
            "name": "درة العروس",
            "created_at": "2026-07-25 16:29:21"
        },
        {
            "id": 22,
            "region_id": 11,
            "name": "الحريضة",
            "created_at": "2026-07-25 16:29:34"
        },
        {
            "id": 23,
            "region_id": 9,
            "name": "الجبيل",
            "created_at": "2026-07-25 16:30:03"
        },
        {
            "id": 25,
            "region_id": 10,
            "name": "مسطبة",
            "created_at": "2026-07-25 16:30:21"
        },
        {
            "id": 26,
            "region_id": 11,
            "name": "البرك",
            "created_at": "2026-07-25 16:30:29"
        },
        {
            "id": 27,
            "region_id": 14,
            "name": "النزهة",
            "created_at": "2026-07-25 16:30:38"
        },
        {
            "id": 28,
            "region_id": 9,
            "name": "القطيف",
            "created_at": "2026-07-25 16:30:50"
        },
        {
            "id": 31,
            "region_id": 12,
            "name": "العمود",
            "created_at": "2026-07-25 16:31:19"
        },
        {
            "id": 32,
            "region_id": 10,
            "name": "عنيكر",
            "created_at": "2026-07-25 16:31:49"
        },
        {
            "id": 33,
            "region_id": 14,
            "name": "رابغ",
            "created_at": "2026-07-25 16:31:57"
        },
        {
            "id": 34,
            "region_id": 14,
            "name": "المرجان",
            "created_at": "2026-07-25 16:32:07"
        },
        {
            "id": 35,
            "region_id": 13,
            "name": "السويس",
            "created_at": "2026-07-25 16:32:20"
        },
        {
            "id": 36,
            "region_id": 14,
            "name": "الشعبان",
            "created_at": "2026-07-25 16:32:31"
        },
        {
            "id": 37,
            "region_id": 10,
            "name": "ذهبان",
            "created_at": "2026-07-25 16:32:43"
        },
        {
            "id": 38,
            "region_id": 14,
            "name": "الحنو",
            "created_at": "2026-07-25 16:32:57"
        },
        {
            "id": 39,
            "region_id": 13,
            "name": "عثر",
            "created_at": "2026-07-25 16:33:17"
        },
        {
            "id": 40,
            "region_id": 13,
            "name": "خاب",
            "created_at": "2026-07-25 16:33:26"
        },
        {
            "id": 41,
            "region_id": 14,
            "name": "الحسي",
            "created_at": "2026-07-25 16:33:35"
        },
        {
            "id": 42,
            "region_id": 14,
            "name": "مستورة",
            "created_at": "2026-07-25 16:33:45"
        },
        {
            "id": 43,
            "region_id": 13,
            "name": "جزيرة القرين",
            "created_at": "2026-07-25 16:34:06"
        },
        {
            "id": 44,
            "region_id": 13,
            "name": "أبو طوق",
            "created_at": "2026-07-25 16:34:20"
        },
        {
            "id": 46,
            "region_id": 9,
            "name": "رأس أبو قميص",
            "created_at": "2026-07-25 16:35:07"
        },
        {
            "id": 47,
            "region_id": 12,
            "name": "رأس الشيخ حميد",
            "created_at": "2026-07-25 16:35:24"
        },
        {
            "id": 48,
            "region_id": 10,
            "name": "المظيلف",
            "created_at": "2026-07-25 16:35:36"
        },
        {
            "id": 49,
            "region_id": 14,
            "name": "الرايس",
            "created_at": "2026-07-25 16:35:46"
        },
        {
            "id": 50,
            "region_id": 9,
            "name": "رأس تنورة",
            "created_at": "2026-07-25 16:36:04"
        },
        {
            "id": 51,
            "region_id": 10,
            "name": "الكدوة",
            "created_at": "2026-07-25 16:36:42"
        },
        {
            "id": 52,
            "region_id": 13,
            "name": "السهي",
            "created_at": "2026-07-25 16:36:50"
        },
        {
            "id": 53,
            "region_id": 9,
            "name": "المزروعية",
            "created_at": "2026-07-25 16:37:01"
        },
        {
            "id": 54,
            "region_id": 12,
            "name": "ضباء",
            "created_at": "2026-07-25 16:37:11"
        },
        {
            "id": 55,
            "region_id": 11,
            "name": "عمق",
            "created_at": "2026-07-25 16:37:19"
        },
        {
            "id": 56,
            "region_id": 13,
            "name": "الموسم",
            "created_at": "2026-07-25 16:37:26"
        },
        {
            "id": 57,
            "region_id": 9,
            "name": "الهفوف",
            "created_at": "2026-07-25 16:37:36"
        },
        {
            "id": 58,
            "region_id": 9,
            "name": "العزيزية",
            "created_at": "2026-07-25 16:37:46"
        },
        {
            "id": 61,
            "region_id": 9,
            "name": "الأحساء",
            "created_at": "2026-07-25 16:39:53"
        },
        {
            "id": 62,
            "region_id": 12,
            "name": "زبيدة",
            "created_at": "2026-07-25 16:40:03"
        },
        {
            "id": 63,
            "region_id": 13,
            "name": "القصار",
            "created_at": "2026-07-25 16:40:37"
        },
        {
            "id": 64,
            "region_id": 12,
            "name": "رأس دبر",
            "created_at": "2026-07-25 16:43:40"
        },
        {
            "id": 65,
            "region_id": 10,
            "name": "المجيرمة",
            "created_at": "2026-07-25 16:44:00"
        },
        {
            "id": 66,
            "region_id": 10,
            "name": "ثول",
            "created_at": "2026-07-25 16:44:14"
        },
        {
            "id": 67,
            "region_id": 13,
            "name": "المضايا",
            "created_at": "2026-07-25 16:44:23"
        },
        {
            "id": 68,
            "region_id": 11,
            "name": "شقيق",
            "created_at": "2026-07-25 16:44:35"
        },
        {
            "id": 69,
            "region_id": 14,
            "name": "الخرج",
            "created_at": "2026-07-25 16:44:51"
        },
        {
            "id": 72,
            "region_id": 10,
            "name": "الليث",
            "created_at": "2026-07-25 16:45:31"
        },
        {
            "id": 73,
            "region_id": 10,
            "name": "رابغ",
            "created_at": "2026-07-25 16:46:01"
        },
        {
            "id": 74,
            "region_id": 13,
            "name": "السميرات",
            "created_at": "2026-07-25 16:46:15"
        },
        {
            "id": 75,
            "region_id": 9,
            "name": "دارين",
            "created_at": "2026-07-25 16:46:25"
        },
        {
            "id": 76,
            "region_id": 13,
            "name": "قصار",
            "created_at": "2026-07-25 16:46:34"
        },
        {
            "id": 77,
            "region_id": 9,
            "name": "منيفة",
            "created_at": "2026-07-25 16:46:45"
        },
        {
            "id": 78,
            "region_id": 12,
            "name": "التبه",
            "created_at": "2026-07-25 16:46:54"
        },
        {
            "id": 79,
            "region_id": 10,
            "name": "مدينة الملك عبدالله الإقتصادية",
            "created_at": "2026-07-25 16:47:14"
        },
        {
            "id": 80,
            "region_id": 12,
            "name": "جزيرة أم الحصاني",
            "created_at": "2026-07-25 16:47:28"
        },
        {
            "id": 82,
            "region_id": 12,
            "name": "المويلح",
            "created_at": "2026-07-25 16:47:54"
        },
        {
            "id": 83,
            "region_id": 10,
            "name": "البحيرات",
            "created_at": "2026-07-25 16:48:04"
        },
        {
            "id": 88,
            "region_id": 14,
            "name": "الجار",
            "created_at": "2026-07-25 16:50:26"
        },
        {
            "id": 90,
            "region_id": 10,
            "name": "عشارا",
            "created_at": "2026-07-25 16:52:38"
        },
        {
            "id": 91,
            "region_id": 11,
            "name": "القحمة",
            "created_at": "2026-07-25 16:52:47"
        },
        {
            "id": 92,
            "region_id": 13,
            "name": "المحرق",
            "created_at": "2026-07-25 16:52:56"
        },
        {
            "id": 93,
            "region_id": 9,
            "name": "العقير",
            "created_at": "2026-07-25 16:53:04"
        },
        {
            "id": 94,
            "region_id": 12,
            "name": "قيال",
            "created_at": "2026-07-25 16:53:11"
        },
        {
            "id": 95,
            "region_id": 11,
            "name": "نهود",
            "created_at": "2026-07-25 16:53:25"
        },
        {
            "id": 96,
            "region_id": 13,
            "name": "بيش",
            "created_at": "2026-07-25 16:53:34"
        },
        {
            "id": 97,
            "region_id": 12,
            "name": "الخريبة",
            "created_at": "2026-07-25 16:53:47"
        },
        {
            "id": 98,
            "region_id": 13,
            "name": "قماح",
            "created_at": "2026-07-25 16:58:35"
        },
        {
            "id": 100,
            "region_id": 10,
            "name": "الياقوت",
            "created_at": "2026-07-25 17:07:09"
        },
        {
            "id": 101,
            "region_id": 10,
            "name": "الؤلؤ",
            "created_at": "2026-07-25 17:08:07"
        },
        {
            "id": 102,
            "region_id": 10,
            "name": "أبحر الشمالية",
            "created_at": "2026-07-25 17:08:16"
        },
        {
            "id": 103,
            "region_id": 10,
            "name": "أبحر الجنوبية",
            "created_at": "2026-07-25 17:08:27"
        },
        {
            "id": 104,
            "region_id": 10,
            "name": "جدة",
            "created_at": "2026-07-25 17:54:58"
        },
        {
            "id": 105,
            "region_id": 9,
            "name": "جزيرة تاروت",
            "created_at": "2026-07-25 18:04:22"
        },
        {
            "id": 106,
            "region_id": 13,
            "name": "جزيرة فرسان",
            "created_at": "2026-07-25 18:13:28"
        },
        {
            "id": 107,
            "region_id": 10,
            "name": "جدة الإقتصادية",
            "created_at": "2026-07-25 18:23:54"
        },
        {
            "id": 108,
            "region_id": 10,
            "name": "الشراع",
            "created_at": "2026-07-25 18:25:04"
        },
        {
            "id": 109,
            "region_id": 10,
            "name": "الزمرد",
            "created_at": "2026-07-25 18:25:15"
        },
        {
            "id": 110,
            "region_id": 13,
            "name": "جيزان",
            "created_at": "2026-07-25 18:28:33"
        },
        {
            "id": 111,
            "region_id": 10,
            "name": "الخمره",
            "created_at": "2026-07-25 18:33:41"
        }
    ],
    "harbor_boat_capacities": [
        {
            "id": 1,
            "port_id": 10,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 2,
            "port_id": 54,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 3,
            "port_id": 3,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 4,
            "port_id": 6,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 5,
            "port_id": 22,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 6,
            "port_id": 27,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 7,
            "port_id": 55,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 8,
            "port_id": 94,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 9,
            "port_id": 25,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 10,
            "port_id": 36,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 11,
            "port_id": 46,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 12,
            "port_id": 11,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 13,
            "port_id": 12,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 14,
            "port_id": 14,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 15,
            "port_id": 15,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 16,
            "port_id": 7,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 17,
            "port_id": 16,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 18,
            "port_id": 73,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 19,
            "port_id": 17,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 20,
            "port_id": 2,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 21,
            "port_id": 18,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 22,
            "port_id": 93,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 23,
            "port_id": 19,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 24,
            "port_id": 20,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 25,
            "port_id": 21,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 26,
            "port_id": 23,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 27,
            "port_id": 132,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 28,
            "port_id": 24,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 29,
            "port_id": 26,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 30,
            "port_id": 88,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 31,
            "port_id": 13,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 32,
            "port_id": 28,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 33,
            "port_id": 29,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 34,
            "port_id": 30,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 35,
            "port_id": 43,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 36,
            "port_id": 69,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 37,
            "port_id": 74,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 38,
            "port_id": 90,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 39,
            "port_id": 95,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 40,
            "port_id": 101,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 41,
            "port_id": 59,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 42,
            "port_id": 33,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 43,
            "port_id": 34,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 44,
            "port_id": 35,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 45,
            "port_id": 85,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 46,
            "port_id": 37,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 47,
            "port_id": 38,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 48,
            "port_id": 39,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 49,
            "port_id": 40,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 50,
            "port_id": 41,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 51,
            "port_id": 42,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 52,
            "port_id": 44,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 53,
            "port_id": 45,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 54,
            "port_id": 80,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 55,
            "port_id": 111,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 56,
            "port_id": 47,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 57,
            "port_id": 48,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 58,
            "port_id": 49,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 59,
            "port_id": 50,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 60,
            "port_id": 51,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 61,
            "port_id": 52,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 62,
            "port_id": 53,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 63,
            "port_id": 56,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 64,
            "port_id": 57,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 65,
            "port_id": 58,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 66,
            "port_id": 60,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 67,
            "port_id": 61,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 68,
            "port_id": 62,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 69,
            "port_id": 63,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 70,
            "port_id": 87,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 71,
            "port_id": 64,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 72,
            "port_id": 66,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 73,
            "port_id": 67,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 74,
            "port_id": 71,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 75,
            "port_id": 75,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 76,
            "port_id": 126,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 77,
            "port_id": 76,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 78,
            "port_id": 77,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 79,
            "port_id": 78,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 80,
            "port_id": 79,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 81,
            "port_id": 84,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 82,
            "port_id": 105,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 83,
            "port_id": 86,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 84,
            "port_id": 89,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 85,
            "port_id": 70,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 86,
            "port_id": 91,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 87,
            "port_id": 92,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 88,
            "port_id": 97,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 89,
            "port_id": 98,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 90,
            "port_id": 99,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 91,
            "port_id": 32,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 92,
            "port_id": 103,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 93,
            "port_id": 108,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 94,
            "port_id": 116,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 95,
            "port_id": 127,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 96,
            "port_id": 128,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 97,
            "port_id": 134,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 98,
            "port_id": 129,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 99,
            "port_id": 130,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 100,
            "port_id": 131,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 101,
            "port_id": 135,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 102,
            "port_id": 136,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 103,
            "port_id": 137,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 104,
            "port_id": 4,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 105,
            "port_id": 114,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 106,
            "port_id": 115,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 107,
            "port_id": 65,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 108,
            "port_id": 100,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 109,
            "port_id": 110,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 110,
            "port_id": 112,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 111,
            "port_id": 120,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 112,
            "port_id": 124,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 113,
            "port_id": 8,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 114,
            "port_id": 113,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 115,
            "port_id": 68,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 116,
            "port_id": 102,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 117,
            "port_id": 104,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 118,
            "port_id": 106,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 119,
            "port_id": 107,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 120,
            "port_id": 109,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 121,
            "port_id": 82,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 122,
            "port_id": 83,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 123,
            "port_id": 96,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 124,
            "port_id": 117,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 125,
            "port_id": 119,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 126,
            "port_id": 122,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 127,
            "port_id": 118,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 128,
            "port_id": 121,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 129,
            "port_id": 123,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 130,
            "port_id": 125,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 131,
            "port_id": 133,
            "boat_type": "large",
            "capacity": 20,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 256,
            "port_id": 10,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 257,
            "port_id": 54,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 258,
            "port_id": 3,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 259,
            "port_id": 6,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 260,
            "port_id": 22,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 261,
            "port_id": 27,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 262,
            "port_id": 55,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 263,
            "port_id": 94,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 264,
            "port_id": 25,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 265,
            "port_id": 36,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 266,
            "port_id": 46,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 267,
            "port_id": 11,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 268,
            "port_id": 12,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 269,
            "port_id": 14,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 270,
            "port_id": 15,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 271,
            "port_id": 7,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 272,
            "port_id": 16,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 273,
            "port_id": 73,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 274,
            "port_id": 17,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 275,
            "port_id": 2,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 276,
            "port_id": 18,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 277,
            "port_id": 93,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 278,
            "port_id": 19,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 279,
            "port_id": 20,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 280,
            "port_id": 21,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 281,
            "port_id": 23,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 282,
            "port_id": 132,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 283,
            "port_id": 24,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 284,
            "port_id": 26,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 285,
            "port_id": 88,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 286,
            "port_id": 13,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 287,
            "port_id": 28,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 288,
            "port_id": 29,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 289,
            "port_id": 30,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 290,
            "port_id": 43,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 291,
            "port_id": 69,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 292,
            "port_id": 74,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 293,
            "port_id": 90,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 294,
            "port_id": 95,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 295,
            "port_id": 101,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 296,
            "port_id": 59,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 297,
            "port_id": 33,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 298,
            "port_id": 34,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 299,
            "port_id": 35,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 300,
            "port_id": 85,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 301,
            "port_id": 37,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 302,
            "port_id": 38,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 303,
            "port_id": 39,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 304,
            "port_id": 40,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 305,
            "port_id": 41,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 306,
            "port_id": 42,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 307,
            "port_id": 44,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 308,
            "port_id": 45,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 309,
            "port_id": 80,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 310,
            "port_id": 111,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 311,
            "port_id": 47,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 312,
            "port_id": 48,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 313,
            "port_id": 49,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 314,
            "port_id": 50,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 315,
            "port_id": 51,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 316,
            "port_id": 52,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 317,
            "port_id": 53,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 318,
            "port_id": 56,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 319,
            "port_id": 57,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 320,
            "port_id": 58,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 321,
            "port_id": 60,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 322,
            "port_id": 61,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 323,
            "port_id": 62,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 324,
            "port_id": 63,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 325,
            "port_id": 87,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 326,
            "port_id": 64,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 327,
            "port_id": 66,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 328,
            "port_id": 67,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 329,
            "port_id": 71,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 330,
            "port_id": 75,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 331,
            "port_id": 126,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 332,
            "port_id": 76,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 333,
            "port_id": 77,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 334,
            "port_id": 78,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 335,
            "port_id": 79,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 336,
            "port_id": 84,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 337,
            "port_id": 105,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 338,
            "port_id": 86,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 339,
            "port_id": 89,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 340,
            "port_id": 70,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 341,
            "port_id": 91,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 342,
            "port_id": 92,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 343,
            "port_id": 97,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 344,
            "port_id": 98,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 345,
            "port_id": 99,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 346,
            "port_id": 32,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 347,
            "port_id": 103,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 348,
            "port_id": 108,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 349,
            "port_id": 116,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 350,
            "port_id": 127,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 351,
            "port_id": 128,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 352,
            "port_id": 134,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 353,
            "port_id": 129,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 354,
            "port_id": 130,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 355,
            "port_id": 131,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 356,
            "port_id": 135,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 357,
            "port_id": 136,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 358,
            "port_id": 137,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 359,
            "port_id": 4,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 360,
            "port_id": 114,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 361,
            "port_id": 115,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 362,
            "port_id": 65,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 363,
            "port_id": 100,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 364,
            "port_id": 110,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 365,
            "port_id": 112,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 366,
            "port_id": 120,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 367,
            "port_id": 124,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 368,
            "port_id": 8,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 369,
            "port_id": 113,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 370,
            "port_id": 68,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 371,
            "port_id": 102,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 372,
            "port_id": 104,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 373,
            "port_id": 106,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 374,
            "port_id": 107,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 375,
            "port_id": 109,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 376,
            "port_id": 82,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 377,
            "port_id": 83,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 378,
            "port_id": 96,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 379,
            "port_id": 117,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 380,
            "port_id": 119,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 381,
            "port_id": 122,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 382,
            "port_id": 118,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 383,
            "port_id": 121,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 384,
            "port_id": 123,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 385,
            "port_id": 125,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 386,
            "port_id": 133,
            "boat_type": "small",
            "capacity": 300,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 511,
            "port_id": 10,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 512,
            "port_id": 54,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 513,
            "port_id": 3,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 514,
            "port_id": 6,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 515,
            "port_id": 22,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 516,
            "port_id": 27,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 517,
            "port_id": 55,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 518,
            "port_id": 94,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 519,
            "port_id": 25,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 520,
            "port_id": 36,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 521,
            "port_id": 46,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 522,
            "port_id": 11,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 523,
            "port_id": 12,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 524,
            "port_id": 14,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 525,
            "port_id": 15,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 526,
            "port_id": 7,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 527,
            "port_id": 16,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 528,
            "port_id": 73,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 529,
            "port_id": 17,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 530,
            "port_id": 2,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 531,
            "port_id": 18,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 532,
            "port_id": 93,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 533,
            "port_id": 19,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 534,
            "port_id": 20,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 535,
            "port_id": 21,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 536,
            "port_id": 23,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 537,
            "port_id": 132,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 538,
            "port_id": 24,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 539,
            "port_id": 26,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 540,
            "port_id": 88,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 541,
            "port_id": 13,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 542,
            "port_id": 28,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 543,
            "port_id": 29,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 544,
            "port_id": 30,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 545,
            "port_id": 43,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 546,
            "port_id": 69,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 547,
            "port_id": 74,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 548,
            "port_id": 90,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 549,
            "port_id": 95,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 550,
            "port_id": 101,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 551,
            "port_id": 59,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 552,
            "port_id": 33,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 553,
            "port_id": 34,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 554,
            "port_id": 35,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 555,
            "port_id": 85,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 556,
            "port_id": 37,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 557,
            "port_id": 38,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 558,
            "port_id": 39,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 559,
            "port_id": 40,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 560,
            "port_id": 41,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 561,
            "port_id": 42,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 562,
            "port_id": 44,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 563,
            "port_id": 45,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 564,
            "port_id": 80,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 565,
            "port_id": 111,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 566,
            "port_id": 47,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 567,
            "port_id": 48,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 568,
            "port_id": 49,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 569,
            "port_id": 50,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 570,
            "port_id": 51,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 571,
            "port_id": 52,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 572,
            "port_id": 53,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 573,
            "port_id": 56,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 574,
            "port_id": 57,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 575,
            "port_id": 58,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 576,
            "port_id": 60,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 577,
            "port_id": 61,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 578,
            "port_id": 62,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 579,
            "port_id": 63,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 580,
            "port_id": 87,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 581,
            "port_id": 64,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 582,
            "port_id": 66,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 583,
            "port_id": 67,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 584,
            "port_id": 71,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 585,
            "port_id": 75,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 586,
            "port_id": 126,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 587,
            "port_id": 76,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 588,
            "port_id": 77,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 589,
            "port_id": 78,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 590,
            "port_id": 79,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 591,
            "port_id": 84,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 592,
            "port_id": 105,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 593,
            "port_id": 86,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 594,
            "port_id": 89,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 595,
            "port_id": 70,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 596,
            "port_id": 91,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 597,
            "port_id": 92,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 598,
            "port_id": 97,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 599,
            "port_id": 98,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 600,
            "port_id": 99,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 601,
            "port_id": 32,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 602,
            "port_id": 103,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 603,
            "port_id": 108,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 604,
            "port_id": 116,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 605,
            "port_id": 127,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 606,
            "port_id": 128,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 607,
            "port_id": 134,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 608,
            "port_id": 129,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 609,
            "port_id": 130,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 610,
            "port_id": 131,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 611,
            "port_id": 135,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 612,
            "port_id": 136,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 613,
            "port_id": 137,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 614,
            "port_id": 4,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 615,
            "port_id": 114,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 616,
            "port_id": 115,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 617,
            "port_id": 65,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 618,
            "port_id": 100,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 619,
            "port_id": 110,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 620,
            "port_id": 112,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 621,
            "port_id": 120,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 622,
            "port_id": 124,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 623,
            "port_id": 8,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 624,
            "port_id": 113,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 625,
            "port_id": 68,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 626,
            "port_id": 102,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 627,
            "port_id": 104,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 628,
            "port_id": 106,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 629,
            "port_id": 107,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 630,
            "port_id": 109,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 631,
            "port_id": 82,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 632,
            "port_id": 83,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 633,
            "port_id": 96,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 634,
            "port_id": 117,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 635,
            "port_id": 119,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 636,
            "port_id": 122,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 637,
            "port_id": 118,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 638,
            "port_id": 121,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 639,
            "port_id": 123,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 640,
            "port_id": 125,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        },
        {
            "id": 641,
            "port_id": 133,
            "boat_type": "recreational",
            "capacity": 22,
            "status": "available",
            "updated_at": "2026-07-26 10:55:35"
        }
    ],
    "harbor_workers": [
        {
            "id": 1,
            "port_id": 93,
            "employee_name": "محمد اليعقوبي",
            "identity_number": "$2y$10$R/aEwXcs6fbkiQZrZVhOq.Che2EPEVkmo0Otnmn1H8Jbt.uVzGmfS",
            "nationality": "saudi",
            "worker_type": "fisherman",
            "mobile_number": "0505284043",
            "employment_status": "active",
            "start_date": null,
            "end_date": null,
            "created_at": "2026-07-26 13:49:12"
        },
        {
            "id": 2,
            "port_id": 93,
            "employee_name": "جون",
            "identity_number": "$2y$10$1mTIcJNoeba1lbg8K/KjxeXou862Jtd6YD3yhvRIKO4OSeH8r8oMy",
            "nationality": "non_saudi",
            "worker_type": "foreign_worker",
            "mobile_number": "0505284043",
            "employment_status": "active",
            "start_date": null,
            "end_date": null,
            "created_at": "2026-07-26 13:50:10"
        }
    ],
    "login_attempts": [
        {
            "id": 1,
            "username": "admin",
            "ip_address": "2c0f:fc89:a8:2caf:810b:ccd2:f943:3c51",
            "success": 1,
            "created_at": "2026-07-25 09:05:14"
        },
        {
            "id": 2,
            "username": "admin",
            "ip_address": "2001:16a3:1147:d400:b5f9:f97d:8258:cd7f",
            "success": 1,
            "created_at": "2026-07-25 10:45:25"
        },
        {
            "id": 3,
            "username": "admin",
            "ip_address": "2001:16a2:7121:db00:9076:3037:87b8:6398",
            "success": 1,
            "created_at": "2026-07-25 14:20:57"
        },
        {
            "id": 4,
            "username": "admin",
            "ip_address": "2001:16a3:1147:d400:f5a8:9810:29be:fab0",
            "success": 1,
            "created_at": "2026-07-25 17:33:56"
        },
        {
            "id": 5,
            "username": "admin",
            "ip_address": "41.46.57.105",
            "success": 1,
            "created_at": "2026-07-26 07:09:20"
        },
        {
            "id": 6,
            "username": "admin",
            "ip_address": "41.46.57.105",
            "success": 1,
            "created_at": "2026-07-26 07:17:11"
        },
        {
            "id": 7,
            "username": "admin",
            "ip_address": "2001:16a3:1147:d400:b863:43d5:27cc:f824",
            "success": 1,
            "created_at": "2026-07-26 08:42:13"
        },
        {
            "id": 8,
            "username": "admin",
            "ip_address": "2001:16a3:1147:d400:8828:4c20:cd3b:275c",
            "success": 1,
            "created_at": "2026-07-26 10:08:34"
        },
        {
            "id": 9,
            "username": "admin",
            "ip_address": "2001:16a3:1147:d400:8828:4c20:cd3b:275c",
            "success": 1,
            "created_at": "2026-07-26 11:50:43"
        },
        {
            "id": 10,
            "username": "admin",
            "ip_address": "2001:16a3:1147:d400:8828:4c20:cd3b:275c",
            "success": 1,
            "created_at": "2026-07-26 13:35:49"
        },
        {
            "id": 11,
            "username": "admin",
            "ip_address": "2001:16a3:1149:4200:e070:567e:e0c:54e8",
            "success": 1,
            "created_at": "2026-07-27 09:36:37"
        },
        {
            "id": 12,
            "username": "admin",
            "ip_address": "41.42.252.246",
            "success": 1,
            "created_at": "2026-07-27 16:36:52"
        },
        {
            "id": 13,
            "username": "KHALID-ALYAQUOBI",
            "ip_address": "2001:16a3:1149:4200:e070:567e:e0c:54e8",
            "success": 1,
            "created_at": "2026-07-27 17:06:51"
        },
        {
            "id": 14,
            "username": "ADMIN",
            "ip_address": "2001:16a2:755c:df00:6154:e06e:dbfc:7c8d",
            "success": 1,
            "created_at": "2026-07-27 17:22:28"
        },
        {
            "id": 15,
            "username": "admin",
            "ip_address": "2001:16a3:1149:4200:e070:567e:e0c:54e8",
            "success": 1,
            "created_at": "2026-07-27 17:24:53"
        },
        {
            "id": 16,
            "username": "admin",
            "ip_address": "2001:16a3:1149:4200:99da:727b:702f:4344",
            "success": 1,
            "created_at": "2026-07-27 22:31:31"
        },
        {
            "id": 17,
            "username": "admin",
            "ip_address": "2001:16a3:1149:4200:9448:f453:db0:139e",
            "success": 1,
            "created_at": "2026-07-28 09:16:17"
        },
        {
            "id": 18,
            "username": "admin",
            "ip_address": "2001:16a3:1149:4200:9448:f453:db0:139e",
            "success": 1,
            "created_at": "2026-07-28 19:57:33"
        },
        {
            "id": 19,
            "username": "admin",
            "ip_address": "2001:16a3:1149:4200:1082:8427:cf94:9fb2",
            "success": 1,
            "created_at": "2026-07-29 09:46:14"
        },
        {
            "id": 20,
            "username": "admin",
            "ip_address": "2001:16a3:1149:4200:29ec:16e6:a34b:bb5",
            "success": 1,
            "created_at": "2026-07-29 10:41:48"
        },
        {
            "id": 21,
            "username": "admin",
            "ip_address": "2c0f:fc89:128:cac7:108e:b94b:aae:b091",
            "success": 1,
            "created_at": "2026-07-29 13:30:31"
        }
    ],
    "ports": [
        {
            "id": 2,
            "governorate_id": 16,
            "name": "مرسى أبو النور",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 16:54:49"
        },
        {
            "id": 3,
            "governorate_id": 5,
            "name": "مرسى القطان",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 16:57:13"
        },
        {
            "id": 4,
            "governorate_id": 98,
            "name": "مرسى قماح",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 16:58:59"
        },
        {
            "id": 6,
            "governorate_id": 6,
            "name": "مرسى منيبرة",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 17:03:53"
        },
        {
            "id": 7,
            "governorate_id": 13,
            "name": "مرسى الحره",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 17:05:36"
        },
        {
            "id": 8,
            "governorate_id": 103,
            "name": "مرسى صروم",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 17:09:06"
        },
        {
            "id": 10,
            "governorate_id": 3,
            "name": "مرسى السفانية",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 17:09:43"
        },
        {
            "id": 11,
            "governorate_id": 8,
            "name": "مرسى الصورة",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 17:10:03"
        },
        {
            "id": 12,
            "governorate_id": 9,
            "name": "مرسى المصنع",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 17:11:05"
        },
        {
            "id": 13,
            "governorate_id": 25,
            "name": "مرسى الشعيبة المسدودة",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 17:11:29"
        },
        {
            "id": 14,
            "governorate_id": 11,
            "name": "مرسى الصدين",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 17:11:47"
        },
        {
            "id": 15,
            "governorate_id": 12,
            "name": "مرسى الخبر",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 17:11:59"
        },
        {
            "id": 16,
            "governorate_id": 14,
            "name": "مرسى قطاع املج",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 17:12:19"
        },
        {
            "id": 17,
            "governorate_id": 15,
            "name": "مرسى رأس محيسن",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 17:12:47"
        },
        {
            "id": 18,
            "governorate_id": 16,
            "name": "قوز مرخه",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 17:13:07"
        },
        {
            "id": 19,
            "governorate_id": 17,
            "name": "كاوست مارينا",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 17:13:38"
        },
        {
            "id": 20,
            "governorate_id": 18,
            "name": "مرسى المخرف",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 17:14:31"
        },
        {
            "id": 21,
            "governorate_id": 19,
            "name": "مرسى السقيد",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 17:14:57"
        },
        {
            "id": 22,
            "governorate_id": 6,
            "name": "مرسى الدميغه",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 17:15:33"
        },
        {
            "id": 23,
            "governorate_id": 21,
            "name": "مرسى نقطة البحيرات",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 17:19:23"
        },
        {
            "id": 24,
            "governorate_id": 22,
            "name": "مرسى الحريضة",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 17:20:32"
        },
        {
            "id": 25,
            "governorate_id": 7,
            "name": "رضوى الرمادة",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 17:20:58"
        },
        {
            "id": 26,
            "governorate_id": 23,
            "name": "مرسى الفريع",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 17:22:00"
        },
        {
            "id": 27,
            "governorate_id": 6,
            "name": "مرسى المريسي",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 17:22:17"
        },
        {
            "id": 28,
            "governorate_id": 25,
            "name": "مرسى الشعيبة المفتوحة",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 17:22:37"
        },
        {
            "id": 29,
            "governorate_id": 26,
            "name": "مرسى البرك",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 17:23:02"
        },
        {
            "id": 30,
            "governorate_id": 27,
            "name": "مرسى أم علي",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 17:23:39"
        },
        {
            "id": 32,
            "governorate_id": 82,
            "name": "مرسى المويلح",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 17:24:26"
        },
        {
            "id": 33,
            "governorate_id": 31,
            "name": "مرسى القف",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 17:24:49"
        },
        {
            "id": 34,
            "governorate_id": 32,
            "name": "مرسى الجمعيات",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 17:25:14"
        },
        {
            "id": 35,
            "governorate_id": 33,
            "name": "مرسى الخرار",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 17:25:47"
        },
        {
            "id": 36,
            "governorate_id": 7,
            "name": "مرسى العباسي",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 17:26:22"
        },
        {
            "id": 37,
            "governorate_id": 35,
            "name": "مرسى الأحلام",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 17:26:45"
        },
        {
            "id": 38,
            "governorate_id": 36,
            "name": "مرسى الشعبان",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 17:27:11"
        },
        {
            "id": 39,
            "governorate_id": 37,
            "name": "مرسى ذهبان",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 17:27:25"
        },
        {
            "id": 40,
            "governorate_id": 38,
            "name": "مرسى الحنو",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 17:28:43"
        },
        {
            "id": 41,
            "governorate_id": 39,
            "name": "مرسى المحراق",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 17:29:03"
        },
        {
            "id": 42,
            "governorate_id": 40,
            "name": "مرسى الماشي",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 17:32:13"
        },
        {
            "id": 43,
            "governorate_id": 27,
            "name": "مرسى جولدن مارينا",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 17:39:04"
        },
        {
            "id": 44,
            "governorate_id": 41,
            "name": "مرسى الحسي",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 17:39:26"
        },
        {
            "id": 45,
            "governorate_id": 42,
            "name": "مرسى السطح",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 17:39:43"
        },
        {
            "id": 46,
            "governorate_id": 7,
            "name": "مرسى العزيزية",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 17:40:33"
        },
        {
            "id": 47,
            "governorate_id": 43,
            "name": "مرسى المقعد",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 17:40:53"
        },
        {
            "id": 48,
            "governorate_id": 44,
            "name": "أبو طوق",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 17:41:21"
        },
        {
            "id": 49,
            "governorate_id": 46,
            "name": "ميناء أبو قميص",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 17:41:43"
        },
        {
            "id": 50,
            "governorate_id": 47,
            "name": "مرسى الشيخ حميد",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 17:42:00"
        },
        {
            "id": 51,
            "governorate_id": 48,
            "name": "مرسى الملاوحة",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 17:42:40"
        },
        {
            "id": 52,
            "governorate_id": 49,
            "name": "مرسى الرايس",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 17:43:23"
        },
        {
            "id": 53,
            "governorate_id": 50,
            "name": "مرسى أبو مريخه",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 17:43:47"
        },
        {
            "id": 54,
            "governorate_id": 3,
            "name": "ميناء الخفجي",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 17:44:03"
        },
        {
            "id": 55,
            "governorate_id": 6,
            "name": "مرسى الوجه",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 17:45:02"
        },
        {
            "id": 56,
            "governorate_id": 51,
            "name": "مرسى الكدوف",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 17:45:29"
        },
        {
            "id": 57,
            "governorate_id": 52,
            "name": "مرسى السهي",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 17:47:13"
        },
        {
            "id": 58,
            "governorate_id": 53,
            "name": "مرسى المزروعية",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 17:47:28"
        },
        {
            "id": 59,
            "governorate_id": 28,
            "name": "مرسى القطيف",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 17:47:42"
        },
        {
            "id": 60,
            "governorate_id": 54,
            "name": "مرسى مرفأ ضباء",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 17:49:18"
        },
        {
            "id": 61,
            "governorate_id": 55,
            "name": "مرسى سوبان",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 17:49:41"
        },
        {
            "id": 62,
            "governorate_id": 56,
            "name": "مرسى النصايب",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 17:50:12"
        },
        {
            "id": 63,
            "governorate_id": 57,
            "name": "مرسى شاطئ نصف القمر",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 17:50:34"
        },
        {
            "id": 64,
            "governorate_id": 58,
            "name": "مرسى شاطئ الغروب",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 17:50:51"
        },
        {
            "id": 65,
            "governorate_id": 102,
            "name": "مرسى أبحر",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 17:53:05"
        },
        {
            "id": 66,
            "governorate_id": 61,
            "name": "الفناتير الجنوبية",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 17:53:26"
        },
        {
            "id": 67,
            "governorate_id": 62,
            "name": "مرسى زبيدة",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 17:53:47"
        },
        {
            "id": 68,
            "governorate_id": 104,
            "name": "مرسى الطفيه",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 17:55:34"
        },
        {
            "id": 69,
            "governorate_id": 27,
            "name": "مرسى القريشي",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 17:56:00"
        },
        {
            "id": 70,
            "governorate_id": 76,
            "name": "خور فرسان",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 17:57:52"
        },
        {
            "id": 71,
            "governorate_id": 64,
            "name": "مرسى السلطانية",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 17:58:21"
        },
        {
            "id": 73,
            "governorate_id": 14,
            "name": "مرسى الخوارة",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 17:59:35"
        },
        {
            "id": 74,
            "governorate_id": 27,
            "name": "منتجع شاطئ الدولفين",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 18:00:21"
        },
        {
            "id": 75,
            "governorate_id": 65,
            "name": "مرسى السوداء",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 18:00:53"
        },
        {
            "id": 76,
            "governorate_id": 66,
            "name": "مرسى ثول",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 18:01:12"
        },
        {
            "id": 77,
            "governorate_id": 67,
            "name": "مرسى المضايا",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 18:01:39"
        },
        {
            "id": 78,
            "governorate_id": 68,
            "name": "مرسى الشقيق",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 18:02:05"
        },
        {
            "id": 79,
            "governorate_id": 69,
            "name": "مرسى الخرج",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 18:02:28"
        },
        {
            "id": 80,
            "governorate_id": 42,
            "name": "مرسى مستورة",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 18:02:43"
        },
        {
            "id": 82,
            "governorate_id": 105,
            "name": "مرسى دارين",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 18:04:36"
        },
        {
            "id": 83,
            "governorate_id": 105,
            "name": "مرسى الزور",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 18:04:51"
        },
        {
            "id": 84,
            "governorate_id": 72,
            "name": "ميناء الليث",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 18:05:18"
        },
        {
            "id": 85,
            "governorate_id": 33,
            "name": "مرسى السرج",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 18:05:33"
        },
        {
            "id": 86,
            "governorate_id": 74,
            "name": "مرسى السميرات",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 18:06:00"
        },
        {
            "id": 87,
            "governorate_id": 57,
            "name": "مرسى دوحة حماة",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 18:06:18"
        },
        {
            "id": 88,
            "governorate_id": 23,
            "name": "مرسى الجداف",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 18:06:33"
        },
        {
            "id": 89,
            "governorate_id": 75,
            "name": "مارينا الجبيل",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 18:06:46"
        },
        {
            "id": 90,
            "governorate_id": 27,
            "name": "مرسى الأحلام",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 18:07:10"
        },
        {
            "id": 91,
            "governorate_id": 76,
            "name": "مرسى الغدير",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 18:09:27"
        },
        {
            "id": 92,
            "governorate_id": 77,
            "name": "مرسى منيفة",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 18:10:32"
        },
        {
            "id": 93,
            "governorate_id": 16,
            "name": "مرفأ الصيادين بقطاع القنفذة",
            "location_name": "مرفأ الصيادين بقطاع القنفذة",
            "location_url": "https://www.google.com/maps/place/Qunfudah+Boats+Port/@19.1212014,41.0738902,308m/data=!3m1!1e3!4m6!3m5!1s0x15e691011d271eb1:0x26503a9e666c7217!8m2!3d19.1210948!4d41.0741301!16s%2Fg%2F11gm8gx1bt?authuser=0&entry=ttu&g_ep=EgoyMDI2MDcyMi4wIKXMDSoASAFQAw%3D%3D",
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 18:11:10"
        },
        {
            "id": 94,
            "governorate_id": 6,
            "name": "نقطة القراير",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 18:12:29"
        },
        {
            "id": 95,
            "governorate_id": 27,
            "name": "مرسى الأحمدي",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 18:12:49"
        },
        {
            "id": 96,
            "governorate_id": 106,
            "name": "مرسى الحسين",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 18:13:46"
        },
        {
            "id": 97,
            "governorate_id": 78,
            "name": "مرسى التبه",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 18:14:33"
        },
        {
            "id": 98,
            "governorate_id": 79,
            "name": "مرسى البيلسان",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 18:14:47"
        },
        {
            "id": 99,
            "governorate_id": 80,
            "name": "مرسى ام الحصاني",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 18:15:13"
        },
        {
            "id": 100,
            "governorate_id": 102,
            "name": "مرسى جدة لليخوت",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 18:15:36"
        },
        {
            "id": 101,
            "governorate_id": 27,
            "name": "مرسى الأحلام السياحي",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 18:16:00"
        },
        {
            "id": 102,
            "governorate_id": 104,
            "name": "مرسى البحر الأحمر",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 18:16:19"
        },
        {
            "id": 103,
            "governorate_id": 82,
            "name": "مرسى اليخوت",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 18:16:39"
        },
        {
            "id": 104,
            "governorate_id": 104,
            "name": "مرسى سماكو البحرية",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 18:17:05"
        },
        {
            "id": 105,
            "governorate_id": 72,
            "name": "مرسى الأحلام السياحي",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 18:17:29"
        },
        {
            "id": 106,
            "governorate_id": 104,
            "name": "مرسى الأمانة",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 18:17:48"
        },
        {
            "id": 107,
            "governorate_id": 104,
            "name": "ميناء نادي جدة لليخوت",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 18:18:08"
        },
        {
            "id": 108,
            "governorate_id": 83,
            "name": "مرسى الأحلام السياحي",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 18:19:22"
        },
        {
            "id": 109,
            "governorate_id": 104,
            "name": "مرسى الأحلام السياحي",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 18:19:51"
        },
        {
            "id": 110,
            "governorate_id": 102,
            "name": "مرسى قرية اللؤلؤ",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 18:20:27"
        },
        {
            "id": 111,
            "governorate_id": 42,
            "name": "خور سعود",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 18:20:43"
        },
        {
            "id": 112,
            "governorate_id": 102,
            "name": "كورال بيتش",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 18:21:14"
        },
        {
            "id": 113,
            "governorate_id": 103,
            "name": "مرسى اللؤلؤة",
            "location_name": null,
            "location_url": null,
            "is_active": 0,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 18:21:41"
        },
        {
            "id": 114,
            "governorate_id": 100,
            "name": "أويا",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 18:21:57"
        },
        {
            "id": 115,
            "governorate_id": 101,
            "name": "الصهيل",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 18:22:51"
        },
        {
            "id": 116,
            "governorate_id": 88,
            "name": "مرسى خليج الجار السياحي",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 18:23:09"
        },
        {
            "id": 117,
            "governorate_id": 107,
            "name": "الشاطئ الهادئ",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 18:24:21"
        },
        {
            "id": 118,
            "governorate_id": 108,
            "name": "قراند مارينا",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 18:25:48"
        },
        {
            "id": 119,
            "governorate_id": 107,
            "name": "بلاج الرمال",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 18:26:04"
        },
        {
            "id": 120,
            "governorate_id": 102,
            "name": "مانجروف",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 18:26:23"
        },
        {
            "id": 121,
            "governorate_id": 109,
            "name": "بوهو",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 18:26:42"
        },
        {
            "id": 122,
            "governorate_id": 107,
            "name": "شمس",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 18:27:00"
        },
        {
            "id": 123,
            "governorate_id": 109,
            "name": "أنديجو",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 18:27:31"
        },
        {
            "id": 124,
            "governorate_id": 102,
            "name": "ارض السعادة ١",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 18:28:03"
        },
        {
            "id": 125,
            "governorate_id": 110,
            "name": "مرسى الحافة",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 18:29:01"
        },
        {
            "id": 126,
            "governorate_id": 65,
            "name": "مرسى مجيرمه",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 18:29:38"
        },
        {
            "id": 127,
            "governorate_id": 90,
            "name": "مرسى سمار",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 18:29:55"
        },
        {
            "id": 128,
            "governorate_id": 91,
            "name": "مرسى البحث والإنقاذ",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 18:30:22"
        },
        {
            "id": 129,
            "governorate_id": 92,
            "name": "مرسى ثبته",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 18:30:43"
        },
        {
            "id": 130,
            "governorate_id": 93,
            "name": "نقطة مراقبة القوارب بالقصار",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 18:31:05"
        },
        {
            "id": 131,
            "governorate_id": 94,
            "name": "مرسى قيال",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 18:31:26"
        },
        {
            "id": 132,
            "governorate_id": 21,
            "name": "مرسى درة العروس",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 18:31:55"
        },
        {
            "id": 133,
            "governorate_id": 111,
            "name": "مرسى البضيع",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 18:34:07"
        },
        {
            "id": 134,
            "governorate_id": 91,
            "name": "مرسى القحمة",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 18:35:17"
        },
        {
            "id": 135,
            "governorate_id": 95,
            "name": "مرسى النهود",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 18:35:46"
        },
        {
            "id": 136,
            "governorate_id": 96,
            "name": "مرسى بيش",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 18:36:30"
        },
        {
            "id": 137,
            "governorate_id": 97,
            "name": "مرسى الخريبة",
            "location_name": null,
            "location_url": null,
            "is_active": 1,
            "latitude": null,
            "longitude": null,
            "created_at": "2026-07-25 18:36:52"
        }
    ],
    "regions": [
        {
            "id": 9,
            "name": "المنطقة الشرقية",
            "created_at": "2026-07-25 14:22:39"
        },
        {
            "id": 10,
            "name": "منطقة مكة المكرمة",
            "created_at": "2026-07-25 16:06:37"
        },
        {
            "id": 11,
            "name": "منطقة عسير",
            "created_at": "2026-07-25 16:06:45"
        },
        {
            "id": 12,
            "name": "منطقة تبوك",
            "created_at": "2026-07-25 16:06:52"
        },
        {
            "id": 13,
            "name": "منطقة جازان",
            "created_at": "2026-07-25 16:07:08"
        },
        {
            "id": 14,
            "name": "منطقة المدينة المنورة",
            "created_at": "2026-07-25 16:08:57"
        }
    ],
    "roles": [
        {
            "id": 1,
            "code": "super_admin",
            "name_ar": "الإدارة العليا",
            "dashboard_route": "admin.php"
        },
        {
            "id": 2,
            "code": "region_manager",
            "name_ar": "مدير المنطقة",
            "dashboard_route": "region.php"
        },
        {
            "id": 3,
            "code": "gov_supervisor",
            "name_ar": "مشرف المحافظة",
            "dashboard_route": "governorate.php"
        },
        {
            "id": 4,
            "code": "port_supervisor",
            "name_ar": "مشرف الميناء",
            "dashboard_route": "port.php"
        },
        {
            "id": 5,
            "code": "stat_employee",
            "name_ar": "موظف الإحصاء",
            "dashboard_route": "employee.php"
        },
        {
            "id": 6,
            "code": "hr_manager",
            "name_ar": "مدير الموارد البشرية",
            "dashboard_route": "hr.php"
        },
        {
            "id": 7,
            "code": "finance_officer",
            "name_ar": "مسؤول الرواتب والمالية",
            "dashboard_route": "payroll.php"
        },
        {
            "id": 8,
            "code": "quality_supervisor",
            "name_ar": "مراقب الجودة",
            "dashboard_route": "discrepancies.php"
        },
        {
            "id": 9,
            "code": "employee_portal",
            "name_ar": "بوابة الموظف",
            "dashboard_route": "employment_profile.php"
        }
    ],
    "shifts": [
        {
            "id": 1,
            "name": "morning",
            "start_time": "06:00:00",
            "end_time": "14:00:00"
        },
        {
            "id": 2,
            "name": "evening",
            "start_time": "14:00:00",
            "end_time": "22:00:00"
        },
        {
            "id": 3,
            "name": "night",
            "start_time": "22:00:00",
            "end_time": "06:00:00"
        }
    ],
    "users": [
        {
            "id": 1,
            "role_id": 1,
            "full_name": "admin",
            "username": "admin",
            "email": null,
            "password_hash": "$2y$10$la0sdjHQJCrvN9vMBG9H8.QWBe3NEKQZQjg04EegZH0KTFgAqHpRu",
            "region_id": null,
            "governorate_id": null,
            "port_id": null,
            "is_active": 1,
            "last_login_at": "2026-07-29 13:30:31",
            "created_at": "2026-07-25 09:05:05"
        },
        {
            "id": 3,
            "role_id": 5,
            "full_name": "محمد",
            "username": "yaquobi",
            "email": null,
            "password_hash": "$2y$10$P2UGgRJ7BDQ0JlNwv2pD8up5mEw1BooF2TfEh/wabeyCmDXkUQVnW",
            "region_id": null,
            "governorate_id": null,
            "port_id": null,
            "is_active": 1,
            "last_login_at": null,
            "created_at": "2026-07-27 16:43:45"
        },
        {
            "id": 4,
            "role_id": 9,
            "full_name": "خالد محمد عمر اليعقوبي",
            "username": "KHALID-ALYAQUOBI",
            "email": "khyaquobi@gmail.com",
            "password_hash": "$2y$10$T6nC3d.WC73YQ9sQnaQxS.7OUuGc1Xe7TKcGKAGj1zreT0nGBpgSe",
            "region_id": null,
            "governorate_id": null,
            "port_id": null,
            "is_active": 1,
            "last_login_at": "2026-07-27 17:06:51",
            "created_at": "2026-07-27 17:05:33"
        }
    ]
}
JSON,
            associative: true,
            flags: JSON_THROW_ON_ERROR,
        );
    }
}
