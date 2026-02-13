<?php

namespace App\Models;

use App\Models\Group_user;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    use HasFactory;

    protected $table = 'groups';
    protected $primaryKey = 'group_id';
    protected $keyType = 'int';
    public $incrementing = true;

    protected $fillable = [
        'position',
        'campus'
    ];

    public $timestamps = true;

    /**
     * Get the group members
     */
    public function members()
    {
        return $this->hasMany(Group_user::class, 'group_id', 'group_id');
    }
}
