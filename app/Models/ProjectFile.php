<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'filename',
        'original_name',
        'file_path',
        'file_type',
        'mime_type',
        'file_size',
        'description'
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the URL for the file
     */
    public function getUrlAttribute()
    {
        return asset('storage/' . $this->file_path);
    }

    /**
     * Get file icon based on mime type
     */
    public function getFileIconAttribute()
    {
        return $this->getFileIcon($this->mime_type);
    }

    /**
     * Helper method to get file icon
     */
    public static function getFileIcon($mimeType)
    {
        if (str_contains($mimeType, 'pdf')) {
            return '📄';
        } elseif (str_contains($mimeType, 'word') || str_contains($mimeType, 'document')) {
            return '📝';
        } elseif (str_contains($mimeType, 'excel') || str_contains($mimeType, 'spreadsheet')) {
            return '📊';
        } elseif (str_contains($mimeType, 'image')) {
            return '🖼️';
        } else {
            return '📎';
        }
    }
}