<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Recipient extends Model
{
    use HasFactory;

    protected $table = 'recipients';
    protected $primaryKey = 'recipient_id';
    public $timestamps = false;

    protected $fillable = [
        'route_id',
        'user_id',
        'role',
        'action',
        'receive_at',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'receive_at' => 'datetime',
    ];

    public function route()
    {
        return $this->belongsTo(DocumentRoute::class, 'route_id', 'route_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
