<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SentDocument extends Model
{
    use HasFactory;

    protected $table = 'sent_documents';
    protected $primaryKey = 'sent_id';

    protected $fillable = [
        'user_id',
        'document_id',
        'route_id',
        'recipient_id',
        'status',
        'sent_at',
        'unsend_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'unsend_at' => 'datetime',
    ];

    public $timestamps = false;

    public function document()
    {
        return $this->belongsTo(Document::class, 'document_id', 'document_id');
    }

    public function recipient()
    {
        return $this->belongsTo(User::class, 'recipient_id', 'user_id');
    }
}
