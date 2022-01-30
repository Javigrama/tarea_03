<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
// use Illuminate\Database\Eloquent\Relations\Pivot;

class Puja extends Model
{
    use HasFactory;

    public function usuario(){
        return $this->belongsTo(Users::class);
    }
    public function producto(){
        return $this->belongsTo(Producto::class);
    }
}
