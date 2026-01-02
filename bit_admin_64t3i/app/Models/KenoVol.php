<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KenoVol extends Model
{
    use HasFactory;
    protected $table = 'keno_vol';
    protected $guarded = [];
    
    public function getCreatetimeAttribute($value)
    {
        return date('Y-m-d H:i', $value);
    }
}
