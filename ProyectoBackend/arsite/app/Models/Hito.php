<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Hito extends Model
{
    use HasFactory;

    protected $table = 'hitos';
    protected $primaryKey = 'hit_id';

    protected $fillable = [
        'hit_titulo',
        'hit_descripcion',
        'hit_imagen',
        'hit_categoria',
        'hit_fecha',
        'hit_orden',
        'hit_estatus',
        'user_id'
    ];

    protected $casts = [
        'hit_fecha' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
