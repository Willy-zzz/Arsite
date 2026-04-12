<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Servicio extends Model
{
    use HasFactory;
    /**
     * Nombre de la tabla y clave primaria
     *
     * @var string
     */
    protected $table = 'servicios';
    protected $primaryKey = 'ser_id';
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'ser_titulo',
        'ser_descripcion',
        'ser_imagen',
        'ser_orden',
        'ser_fecha_publicacion',
        'ser_fecha_terminacion',
        'ser_estatus',
        'user_id',
    ]; 

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'ser_fecha_publicacion' => 'date',  
        'ser_fecha_terminacion' => 'date',
        ];
    
    /**
     * Get the user that created the service.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
