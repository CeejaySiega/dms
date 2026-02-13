<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;

    protected $table = 'employees';
    
    protected $primaryKey = 'employee_id';

    protected $fillable = [
        'user_id',
        'firstname',
        'lastname',
        'campus',
        'department_id',
        'role',
        'create_at',
        'updated_at',
    ];

 
    public function user()
    {
        return $this->belongsTo(User::class);
    }

   
    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id', 'department_id');
    }
   
    public function employee()
    {
        return $this->hasOne(Employee::class, 'user_id', 'user_id');
    }
    public function documents()
    {
        return $this->hasMany(Document::class, 'user_id', 'user_id');
    }
    public function groupMembers()
    {
        return $this->hasMany(Group_user::class, 'user_id', 'user_id');
    }
}
