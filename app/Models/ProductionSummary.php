<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionSummary extends Model
{
    use HasFactory;

    protected $table = 'production_summaries';

    protected $fillable = [
        'date',
        'shift',
        'total_input_hangers',
        'total_output_hangers',
        'total_output_pcs',
        'total_empty_hangers',
        'wip_hangers',
        'is_active',
    ];

    protected $casts = [
        'date' => 'date',
        'is_active' => 'boolean',
    ];
}

