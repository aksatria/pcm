<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterDataAudit extends Model
{
    use HasFactory;

    protected $fillable = [
        'master_data_id',
        'action',
        'old_data',
        'new_data',
        'user_id',
        'ip_address',
        'user_agent'
    ];

    protected $casts = [
        'old_data' => 'array',
        'new_data' => 'array'
    ];

    public function masterData()
    {
        return $this->belongsTo(MasterData::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}