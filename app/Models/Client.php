<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'company',
        'email', 
        'phone',
        'address',
        'category',
        'notes',
        'delete_status',
        'delete_requested_by',
        'delete_requested_at',
        'delete_reason',
        'delete_reviewed_by',
        'delete_reviewed_at',
        'delete_review_note'
    ];

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }
}
