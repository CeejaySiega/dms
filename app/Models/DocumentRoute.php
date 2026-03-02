<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentRoute extends Model
{
    use HasFactory;

    protected $table = 'document_routes';
    protected $primaryKey = 'route_id';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'document_id',
        'group_id',
        'approve_at',
        'receive_at',
        'action',
        'priority',
        'unsend_at',
    ];

    protected $casts = [
        'approve_at' => 'datetime',
        'receive_at' => 'datetime',
        'unsend_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function document()
    {
        return $this->belongsTo(Document::class, 'document_id', 'document_id');
    }

    public function recipients()
    {
        return $this->hasMany(Recipient::class, 'route_id', 'route_id');
    }

    public function group()
    {
        return $this->belongsTo(Group::class, 'group_id', 'group_id');
    }
}
