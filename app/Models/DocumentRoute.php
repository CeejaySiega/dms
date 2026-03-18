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
        'sender_id',
        'receiver_id',
        'action',
        'priority',
        'unsend_at',
        'forward_at',
    ];

    protected $casts = [
        'forward_at' => 'datetime',
        'unsend_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id', 'user_id');
    }

    public function receiverUser()
    {
        return $this->belongsTo(User::class, 'receiver_id', 'user_id');
    }

    public function receiverGroup()
    {
        return $this->belongsTo(Group::class, 'receiver_id', 'group_id');
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
