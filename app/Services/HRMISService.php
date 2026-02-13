<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Department;

class HRMISService
{
    /**
     * 
     * @param array 
     * @param int 
     * @return Employee
     */

    
   public function syncEmployee($hrmisData, $userId)
{
  if (!empty($hrmisData['department'])) {
    $hrmisDept = $hrmisData['department'];

   
    $department = Department::where('department_id', $hrmisDept['id'])->first();

    if (!$department) {
        $department = Department::create([
            'department_id' => $hrmisDept['id'], 
            'department_name' => $hrmisDept['DepartmentName'],
            'campus' => $hrmisData['Campus'] ?? null,
        ]);
    }

    $departmentId = $department->department_id;
}


        
        $employee = Employee::firstOrCreate(
            ['user_id' => $userId],
            [
                'firstname' => $hrmisData['FirstName'] ?? null,
                'lastname' => $hrmisData['LastName'] ?? null,
                'campus' => $hrmisData['Campus'] ?? null,
                'department_id' => $departmentId,
            ]
        );
        $employee->update([
            'firstname' => $hrmisData['FirstName'] ?? $employee->firstname,
            'lastname' => $hrmisData['LastName'] ?? $employee->lastname,
            'campus' => $hrmisData['Campus'] ?? $employee->campus,
            'department_id' => $departmentId ?? $employee->department_id,
        ]);

        return $employee;
    }
}
