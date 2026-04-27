<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContactoRespuesta extends Model
{
    use HasFactory;

    protected $table = 'contacto_respuestas';
    protected $primaryKey = 'cor_id';

    protected $fillable = [
        'con_id',
        'user_id',
        'cor_mensaje',
        'cor_tipo',
    ];

    public function contacto()
    {
        return $this->belongsTo(Contacto::class, 'con_id', 'con_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
