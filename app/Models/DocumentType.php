<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DocumentType extends Model
{
    use HasFactory;

    protected $primaryKey = 'documenttype_id';
    protected $table = 'document_types';
    public $timestamps = false;

    protected $fillable = [
        'type_name',
    ];

    /**
     * Get all documents of this type
     */
    public function documents()
    {
        return $this->hasMany(Document::class, 'documenttype_id', 'documenttype_id');
    }
}
