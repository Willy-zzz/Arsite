<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Destacado extends Model
{
    use HasFactory;
    /**
     * Nombre de la tabla y clave primaria
     *
     * @var string
     */
    protected $table = 'destacados';
    protected $primaryKey = 'des_id';
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'des_titulo',
        'des_subtitulo',
        'des_texto_boton',
        'des_enlace_boton',
        'des_imagen',
        'des_fecha_publicacion',
        'des_fecha_terminacion',
        'des_orden',
        'des_estatus',
        'user_id',
    ]; 

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'des_fecha_publicacion' => 'date',  
        'des_fecha_terminacion' => 'date',
        ];

    /**
     * Get the user that created the highlighted item.
     */
    public function user ()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
