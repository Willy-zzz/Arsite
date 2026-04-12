<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    use HasFactory;
    /**
     * Nombre de la tabla y clave primaria
     *
     * @var string
     */
    protected $table = 'banners';
    protected $primaryKey = 'ban_id';
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'ban_titulo',
        'ban_subtitulo',
        'ban_texto_boton',
        'ban_enlace_boton',
        'ban_imagen',
        'ban_fecha_publicacion',
        'ban_fecha_terminacion',
        'ban_orden',
        'ban_estatus',
        'user_id',
    ];    

    /**
     * Get the attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'ban_fecha_publicacion' => 'date',  
        'ban_fecha_terminacion' => 'date',
        ];

    /**
     * Get the user that created the banner.
     */
    public function user ()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
