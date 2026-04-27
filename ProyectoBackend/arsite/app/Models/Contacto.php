<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contacto extends Model
{
    use HasFactory;
    /**
     * Nombre de la tabla y clave primaria
     *
     * @var string
     */
    protected $table = 'contactos';
    protected $primaryKey = 'con_id';
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'con_nombre',
        'con_email',
        'con_telefono',
        'con_asunto',
        'con_mensaje',
        'con_empresa',
        'con_estado',
        'con_ip'
    ];

    protected $attributes = [
        'con_estado' => 'Nuevo'
    ];

    // Scopes por estado
    public function scopeNuevos($query)
    {
        return $query->where('con_estado', 'Nuevo');
    }

    public function scopeLeidos($query)
    {
        return $query->where('con_estado', 'Leido');
    }

    public function scopeRespondidos($query)
    {
        return $query->where('con_estado', 'Respondido');
    }

    public function scopeArchivados($query)
    {
        return $query->where('con_estado', 'Archivado');
    }

    // Scopes por tiempo
    public function scopeRecientes($query, $dias = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($dias));
    }

    public function scopeDelMes($query, $year = null, $month = null)
    {
        $year = $year ?? now()->year;
        $month = $month ?? now()->month;
        
        return $query->whereYear('created_at', $year)
                    ->whereMonth('created_at', $month);
    }

    public function scopeDeLaSemana($query)
    {
        return $query->whereBetween('created_at', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ]);
    }

    public function scopeDeHoy($query)
    {
        return $query->whereDate('created_at', now());
    }

    // Accessors

    //Fechas con formato
    public function getFechaFormateadaAttribute()
    {
        return $this->created_at->format('d/m/Y H:i:s');
    }

    public function getFechaHumanaAttribute()
    {
        return $this->created_at->diffForHumans();
    }

    //Estado
    public function getEstadoBadgeAttribute()
    {
        $badges = [
            'Nuevo' => 'primary',
            'Leido' => 'info',
            'Respondido' => 'success',
            'Archivado' => 'secondary'
        ];

        return $badges[$this->con_estado] ?? 'secondary';
    }

    public function getNombreCompletoAttribute()
    {
        return trim($this->con_nombre);
    }

    //Mensaje resumido
    public function getMensajeResumeAttribute()
    {
        return \Str::limit($this->con_mensaje, 100);
    }

    // Mutators
    public function setConNombreAttribute($value)
    {
        $this->attributes['con_nombre'] = trim($value);
    }

    public function setConEmailAttribute($value)
    {
        $this->attributes['con_email'] = strtolower(trim($value));
    }

    public function setConEmpresaAttribute($value)
    {
        $this->attributes['con_empresa'] = trim($value);
    }

    public function setConAsuntoAttribute($value)
    {
        $this->attributes['con_asunto'] = trim($value);
    }

    // Métodos auxiliares para vertificar estado
    public function isNuevo(): bool
    {
        return $this->con_estado === 'Nuevo';
    }

    public function isLeido(): bool
    {
        return $this->con_estado === 'Leido';
    }

    public function isRespondido(): bool
    {
        return $this->con_estado === 'Respondido';
    }

    public function isArchivado(): bool
    {
        return $this->con_estado === 'Archivado';
    }

    public function marcarComoLeido()
    {
        if ($this->isNuevo()) {
            $this->update(['con_estado' => 'Leido']);
        }
        return $this;
    }

    public function marcarComoRespondido()
    {
        $this->update(['con_estado' => 'Respondido']);
        return $this;
    }

    public function archivar()
    {
        $this->update(['con_estado' => 'Archivado']);
        return $this;
    }

    public function restaurar()
    {
        $this->update(['con_estado' => 'Leido']);
        return $this;
    }

    public function getDiasDesdeCreacion(): int
    {
        return $this->created_at->diffInDays(now());
    }

    public function respuestas()
    {
        return $this->hasMany(ContactoRespuesta::class, 'con_id', 'con_id')->latest('created_at');
    }

    public function isPendiente(): bool
    {
        return in_array($this->con_estado, ['Nuevo', 'Leido']);
    }

    public function requiresAtencion(): bool
    {
        return $this->isNuevo() && $this->getDiasDesdeCreacion() > 3;
    }

    // Métodos estáticos
    public static function totalNuevos(): int
    {
        return static::nuevos()->count();
    }

    public static function totalPendientes(): int
    {
        return static::whereIn('con_estado', ['Nuevo', 'Leido'])->count();
    }

    public static function estadisticas()
    {
        return [
            'total' => static::count(),
            'nuevos' => static::nuevos()->count(),
            'leidos' => static::leidos()->count(),
            'respondidos' => static::respondidos()->count(),
            'archivados' => static::archivados()->count(),
            'este_mes' => static::delMes()->count(),
            'esta_semana' => static::deLaSemana()->count(),
            'hoy' => static::deHoy()->count(),
            'pendientes' => static::totalPendientes(),
        ];
    }
}
