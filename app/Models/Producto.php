<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Producto extends Model {
    use HasFactory;

    protected $fillable=[
        'name',
        'description',
        'precio_salida',
        'limite_pujas',
        'user_id',
        'categoria_id'
    ];

    public function user(){
        return $this->belongsTo(User::class);
    }

    public function categoria(){
        return $this->belongsTo(Categoria::class);
    }

    public function pujas(){
        return $this->hasMany(Puja::class);
    }
}
