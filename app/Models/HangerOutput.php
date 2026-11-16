<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HangerOutput extends Model
{
    use HasFactory;

    protected $table = 'hanger_outputs';

    protected $fillable = [
        'hanger_number',
        'part_code',
        'part_name',
        'target_pcs',
        'detected_pcs',
        'is_empty',
        'sensor_pin',
        'confidence',
        'image_path',
        'exited_at',
    ];

    protected $casts = [
        'is_empty' => 'boolean',
        'exited_at' => 'datetime',
        'confidence' => 'float',
    ];
}

