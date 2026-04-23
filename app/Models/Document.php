<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Document extends Model
{
    use HasFactory;

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
        'due_date',
        'restored_at',
        'unsend_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'restored_at' => 'datetime',
        'unsend_at' => 'datetime',
        'due_date' => 'date',
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
        return $query->whereIn('document_id', function($subquery) {
            $subquery->select('document_id')->from('archives');
        });
    }

    /**
     * Check if all group members have received the document
     */
    public function allGroupMembersReceived()
    {
        $routes = $this->routes()->get();
        
        foreach ($routes as $route) {
            // If this route is sent to a group
            if ($route->group_id) {
                // Check if all recipients in this group have received the message
                $unreceived = Recipient::where('route_id', $route->route_id)
                    ->whereNull('receive_at')
                    ->count();
                
                // If any recipient hasn't received, return false
                if ($unreceived > 0) {
                    return false;
                }
            }
        }
        
        return true;
    }

    /**
     * Check if the given user is the latest active receiver of this document.
     */
    public function isLastReceiver(int $userId): bool
    {
        $latestRoute = $this->routes()
            ->whereNull('unsend_at')
            ->orderByDesc('route_id')
            ->first();

        if (!$latestRoute || (int) $latestRoute->receiver_id !== (int) $userId) {
            return false;
        }

        return Recipient::where('route_id', $latestRoute->route_id)
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->exists();
    }

    /**
     * Generate tracking code if not exists
     */
    public static function generateTrackingCode()
    {
        return 'DOC-' . date('YmdHis') . '-' . strtoupper(bin2hex(random_bytes(3)));
    }
}
