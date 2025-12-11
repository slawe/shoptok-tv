<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TvProduct extends Model
{
    use HasFactory;

    // nothing is “mass-assignment” protected
    // you can fill in all columns
    // fast and practical, but less “strict”
    protected $guarded = [];
}
