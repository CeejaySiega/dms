<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use HasFactory;

    protected $table = 'departments';

    protected $primaryKey = 'department_id';

    protected $fillable = [
        'department_id',
        'department_name',
        'campus',
    ];

    public $timestamps = false;

    /**
     * Get the employees in this department.
     */
    public function employees()
    {
        return $this->hasMany(Employee::class, 'department_id', 'department_id');
    }
}
