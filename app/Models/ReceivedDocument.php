<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReceivedDocument extends Model
{
    use HasFactory;

    protected $table = 'received_documents';
    protected $primaryKey = 'received_id';

    protected $fillable = [
        'user_id',
        'sent_id',
        'document_id',
        'route_id',
        'status',
        'receive_at',
        'archive_at',
        'unsend_at',
    ];

    protected $casts = [
        'receive_at' => 'datetime',
        'archive_at' => 'datetime',
        'unsend_at' => 'datetime',
    ];

    public $timestamps = false;

    public function document()
    {
        return $this->belongsTo(Document::class, 'document_id', 'document_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function route()
    {
        return $this->belongsTo(DocumentRoute::class, 'route_id', 'route_id');
    }
    public function sentDocument()
    {
        return $this->belongsTo(SentDocument::class, 'sent_id', 'sent_id');
    }
}
