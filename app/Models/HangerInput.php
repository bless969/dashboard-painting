<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HangerInput extends Model
{
    use HasFactory;

    protected $table = 'hanger_inputs';

    protected $fillable = [
        'hanger_number',
        'part_code',
        'part_name',
        'sensor_pin',
        'wip_at_time',
        'status',
        'entered_at',
    ];

    protected $casts = [
        'entered_at' => 'datetime',
    ];
}

