<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterCodeCounter extends Model
{
    use HasFactory;

    protected $table = 'master_code_counters';

    protected $fillable = [
        'category',
        'last_number',
        'description',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'last_number' => 'integer'
    ];

    public $timestamps = true;
}
