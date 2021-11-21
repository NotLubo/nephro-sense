<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PatientData extends Model
{
    use HasFactory;
    protected $fillable=[
        'last_value',
        'value_updated_at',
        'variable_name',
    ];
}
