<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Noticia extends Model
{
    use HasFactory;
    /**
     * Nombre de la tabla y clave primaria
     *
     * @var string
     */
    protected $table = 'noticias';
    protected $primaryKey = 'not_id';
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'not_titulo',
        'not_subtitulo',
        'not_descripcion',
        'not_portada',
        'not_imagen',
        'not_video',
        'not_publicacion',
        'not_estatus',
        'user_id',
    ]; 

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'not_publicacion' => 'datetime',  
        ];
    
    /**
     * Get the user that created the news item.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
