<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    use HasFactory;
    /**
     * Nombre de la tabla y clave primaria
     *
     * @var string
     */
    protected $table = 'clientes';
    protected $primaryKey = 'cli_id';
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'cli_nombre',
        'cli_logo',
        'cli_fecha_publicacion',
        'cli_fecha_terminacion',
        'cli_orden',
        'cli_estatus',
        'user_id',
    ]; 

    /**
     * Get the attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'cli_fecha_publicacion' => 'date',  
        'cli_fecha_terminacion' => 'date',
        ];
    
    /**
     * Get the user that created the client.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
