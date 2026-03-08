<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentCounter extends Model
{
    use HasFactory;

    protected $table = 'document_counters';

    protected $fillable = [
        'project_id',
        'doc_type',
        'last_number',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
