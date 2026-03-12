<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Department;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class HRMISService
{
    /**
     * Sync single employee at login.
     * Called in GoogleAuthController after Google OAuth.
     *
     * @param array $hrmisData  — the 'data' key from the HRMIS API response
     * @param int   $userId     — local users.user_id
     * @return Employee
     */
    public function syncEmployee(array $hrmisData, int $userId): Employee
    {
        $departmentId = null;

        if (!empty($hrmisData['department'])) {
            $hrmisDept = $hrmisData['department'];

            $department = Department::where('department_id', $hrmisDept['id'])->first();

            if (!$department) {
                $department = Department::create([
                    'department_id'   => $hrmisDept['id'],
                    'department_name' => $hrmisDept['DepartmentName'],
                    'campus'          => $hrmisData['Campus'] ?? null,
                ]);
            }

            $departmentId = $department->department_id;
        }

        $employee = Employee::firstOrCreate(
            ['user_id' => $userId],
            [
                'firstname'     => $hrmisData['FirstName']  ?? null,
                'lastname'      => $hrmisData['LastName']   ?? null,
                'campus'        => $hrmisData['Campus']     ?? null,
                'department_id' => $departmentId,
            ]
        );

        $employee->update([
            'firstname'     => $hrmisData['FirstName']  ?? $employee->firstname,
            'lastname'      => $hrmisData['LastName']   ?? $employee->lastname,
            'campus'        => $hrmisData['Campus']     ?? $employee->campus,
            'department_id' => $departmentId            ?? $employee->department_id,
        ]);

        return $employee;
    }

    /**
     * Fetch ALL departments from HRMIS API and sync to local departments table.
     * Cached for 24 hours so we don't hit the API on every login.
     * Falls back to local DB if API is unreachable.
     *
     * @return array
     */
    public function syncAllDepartments(): array
    {
        return Cache::remember('hrmis_departments', now()->addHours(24), function () {
            try {
                $departmentUrl = config('services.hrmis_api.urlDepartment')
                    ?? config('services.hrmis_api.url_department');

                if (!is_string($departmentUrl) || trim($departmentUrl) === '') {
                    Log::error('HRMIS getDepartment URL is missing from config/services.php or .env (HRMIS_API_URL2).');
                    return Department::orderBy('campus')
                        ->orderBy('department_name')
                        ->get()
                        ->toArray();
                }

                $request = Http::timeout(10);
                $token = config('services.hrmis_api.token');

                if (is_string($token) && trim($token) !== '') {
                    $request = $request->withToken($token);
                }

                $response = $request->get($departmentUrl);

                if ($response->successful()) {
                    $departments = $response->json()['data'] ?? $response->json() ?? [];

                    foreach ($departments as $dept) {
                        Department::updateOrCreate(
                            ['department_id' => $dept['id']],
                            [
                                'department_name' => $dept['DepartmentName'],
                                'campus'          => $dept['Campus'] ?? null,
                            ]
                        );
                    }

                    Log::info('HRMIS departments synced successfully. Count: ' . count($departments));

                    return $departments;
                }

                Log::warning('HRMIS getDepartment API returned status: ' . $response->status());

            } catch (\Exception $e) {
                Log::error('HRMIS getDepartment exception: ' . $e->getMessage());
            }

            // ── Fallback: use local DB if API is down ──────────────────────
            Log::info('HRMIS fallback: using local departments table.');
            return Department::orderBy('campus')
                ->orderBy('department_name')
                ->get()
                ->toArray();
        });
    }

    /**
     * Force-refresh the departments cache.
     * Call this from an admin action when departments change.
     *
     * @return array
     */
    public function refreshDepartments(): array
    {
        Cache::forget('hrmis_departments');
        return $this->syncAllDepartments();
    }

    /**
     * Get all distinct campuses from local DB.
     * Use this to populate the Campus dropdown in routing/forwarding.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getCampuses()
    {
        return Department::select('campus')
            ->whereNotNull('campus')
            ->where('campus', '!=', '')
            ->distinct()
            ->orderBy('campus')
            ->pluck('campus');
    }

    /**
     * Get all departments for a given campus from local DB.
     * Use this to populate the Department dropdown after campus is selected.
     *
     * @param  string $campus
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getDepartmentsByCampus(string $campus)
    {
        return Department::where('campus', $campus)
            ->orderBy('department_name')
            ->get(['department_id', 'department_name']);
    }

    /**
     * Get ALL departments from local DB grouped by campus.
     * Use this wherever you need the full list.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllDepartments()
    {
        return Department::orderBy('campus')
            ->orderBy('department_name')
            ->get(['department_id', 'department_name', 'campus']);
    }

    /**
     * Get all users (employees) belonging to a department.
     * Use this to populate the User/Recipient dropdown.
     *
     * @param  int $departmentId
     * @return \Illuminate\Support\Collection
     */
    public function getUsersByDepartment(int $departmentId)
    {
        return Employee::where('department_id', $departmentId)
            ->with('user')
            ->get()
            ->map(fn($e) => [
                'user_id' => $e->user_id,
                'name'    => trim($e->firstname . ' ' . $e->lastname),
            ]);
    }
}