<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GdprExport extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'status',
        'file_path',
        'download_token',
        'downloaded_at',
    ];

    protected $casts = [
        'downloaded_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

