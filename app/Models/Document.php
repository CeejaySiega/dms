<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Document extends Model
{
    use HasFactory, SoftDeletes;

    protected $primaryKey = 'document_id';
    protected $table = 'documents';

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName()
    {
        return 'document_id';
    }

    protected $fillable = [
        'user_id',
        'documenttype_id',
        'tracking_code',
        'file_name',
        'file_path',
        'purpose',
        'priority',
        'status',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the user who sent the document
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the document type
     */
    public function documentType()
    {
        return $this->belongsTo(DocumentType::class, 'documenttype_id');
    }

    /**
     * Get the document routes
     */
    public function routes()
    {
        return $this->hasMany(DocumentRoute::class, 'document_id', 'document_id');
    }

    /**
     * Get all recipients through routes
     */
    public function recipients()
    {
        return $this->hasManyThrough(
            Recipient::class,
            DocumentRoute::class,
            'document_id',
            'route_id',
            'document_id',
            'route_id'
        );
    }

    /**
     * Scope to get pending documents
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to get sent documents
     */
    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    /**
     * Scope to get received documents
     */
    public function scopeReceived($query)
    {
        return $query->where('status', 'received');
    }

    /**
     * Scope to get archived documents
     */
    public function scopeArchived($query)
    {
        return $query->where('status', 'archived');
    }

    /**
     * Generate tracking code if not exists
     */
    public static function generateTrackingCode()
    {
        return 'DOC-' . date('YmdHis') . '-' . strtoupper(bin2hex(random_bytes(3)));
    }
}
