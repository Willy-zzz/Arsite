<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Partner extends Model
{
    use HasFactory;
    /**
     * Nombre de la tabla y clave primaria
     *
     * @var string
     */
    protected $table = 'partners';
    protected $primaryKey = 'par_id';
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'par_nombre',
        'par_logo',
        'par_fecha_publicacion',
        'par_fecha_terminacion',
        'par_orden',
        'par_estatus',
        'user_id',
    ]; 

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'par_fecha_publicacion' => 'date',  
        'par_fecha_terminacion' => 'date',
        ];
    
    /**
     * Get the user that created the partner.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
