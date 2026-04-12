<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    use HasFactory;
    /**
     * Nombre de la tabla y clave primaria
     *
     * @var string
     */
    protected $table = 'productos';
    protected $primaryKey = 'pro_id';
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'pro_nombre',
        'pro_imagen',
        'pro_orden',
        'pro_estatus',
        'user_id',
    ]; 

    /**
     * Get the user that created the product.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
