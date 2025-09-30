<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GdprDeleteRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'status',
        'reason',
        'approved_by',
        'approved_at',
        'processed_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}

