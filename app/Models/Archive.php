<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Archive extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'archives';
    protected $primaryKey = 'archive_id';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'document_id',
        'file_path',
        'file_name',
        'archive_at',
        'restored_at',
        'deleted_at',
    ];

    protected $casts = [
        'archive_at'  => 'datetime',
        'restored_at' => 'datetime',
        'deleted_at'  => 'datetime',
    ];

    /**
     * Get the user that archived this document
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    /**
     * Get the archived document
     */
    public function document()
    {
        return $this->belongsTo(Document::class, 'document_id', 'document_id');
    }
}
