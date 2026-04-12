<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'usu_nombre',
        'email',
        'password',
        'usu_rol',
        'usu_estado',
        'usu_avatar',
        'usu_avatar_tipo'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            //'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    protected $attributes = [
        'usu_rol' => 'Editor',
        'usu_estado' => 'Pendiente',
        'usu_avatar_tipo' => 'initials'
    ];

    //Avatar

    /**
     * Obtener URL completa del avatar según el tipo
     */
    public function getAvatarUrlAttribute(): string
    {
        switch ($this->usu_avatar_tipo) {
            case 'upload':
                // Avatar personalizado subido por el usuario
                return $this->usu_avatar 
                    ? Storage::disk('public')->url($this->usu_avatar)
                    : $this->getInitialsAvatarUrl();
                    
            case 'preset':
                // Avatar de la galería de presets
                return $this->usu_avatar 
                    ? asset("avatars/presets/{$this->usu_avatar}")
                    : $this->getInitialsAvatarUrl();
                    
            case 'initials':
            default:
                // Avatar generado con iniciales
                return $this->getInitialsAvatarUrl();
        }
    }

    /**
     * Generar URL para avatar de iniciales usando UI Avatars
     * https://ui-avatars.com/
     */
    private function getInitialsAvatarUrl(): string
    {
        $initials = urlencode($this->initials);
        $name = urlencode($this->usu_nombre);
        
        // Colores basados en el rol
        $background = $this->usu_rol === 'Administrador' ? 'ef4444' : '3b82f6';
        
        return "https://ui-avatars.com/api/?name={$name}&background={$background}&color=fff&size=200&bold=true";
    }

    /**
     * Actualizar avatar (desde AuthController o UserController)
     * 
     * @param mixed $avatar - Puede ser UploadedFile, string (preset) o null
     * @param string $tipo - 'upload', 'preset' o 'initials'
     * @return bool
     */
    public function updateAvatar($avatar, string $tipo = 'upload'): bool
    {
        // Eliminar avatar anterior si existe y es tipo upload
        if ($this->usu_avatar_tipo === 'upload' && $this->usu_avatar) {
            $this->deleteAvatarFile();
        }

        // Si es null, volver a iniciales
        if ($avatar === null) {
            $this->update([
                'usu_avatar' => null,
                'usu_avatar_tipo' => 'initials'
            ]);
            return true;
        }

        // Si es archivo subido (UploadedFile)
        if ($tipo === 'upload' && is_object($avatar)) {
            $filename = 'avatar_' . $this->id . '_' . time() . '.' . $avatar->getClientOriginalExtension();
            $path = $avatar->storeAs('avatars', $filename, 'public');
            
            $this->update([
                'usu_avatar' => $path,
                'usu_avatar_tipo' => 'upload'
            ]);
            return true;
        }

        // Si es preset (string con nombre del archivo)
        if ($tipo === 'preset' && is_string($avatar)) {
            $this->update([
                'usu_avatar' => $avatar,
                'usu_avatar_tipo' => 'preset'
            ]);
            return true;
        }

        return false;
    }

    /**
     * Eliminar archivo de avatar del storage
     */
    public function deleteAvatarFile(): void
    {
        if ($this->usu_avatar_tipo === 'upload' && 
            $this->usu_avatar && 
            Storage::disk('public')->exists($this->usu_avatar)) {
            Storage::disk('public')->delete($this->usu_avatar);
        }
    }

    /**
     * Obtener información completa del avatar para API
     */
    public function getAvatarDataAttribute(): array
    {
        $tipo = $this->usu_avatar_tipo;
        $avatar = $this->usu_avatar;

        $bg = ($this->usu_rol === 'Administrador') ? 'f59e0b' : '6366f1';

        if ($tipo === 'upload' && $avatar) {
            $url = Storage::disk('public')->url('avatars/' . $avatar);
        } elseif ($tipo === 'preset' && $avatar) {
            $url = asset('avatars/presets/' . $avatar);
        } else {
            // Genera la URL de UI-Avatars con el color del rol
            $url = "https://ui-avatars.com/api/?name=" . urlencode($this->usu_nombre) . "&color=fff&background={$bg}";
        }
        return [
            'url' => $this->avatar_url,
            'tipo' => $this->usu_avatar_tipo,
            'path' => $this->usu_avatar,
            'initials' => $this->initials
        ];
    }

    /**
     * Lista de avatares preset disponibles
     */
    public static function getAvailablePresets(): array
    {
        return [
            'avatar-01.svg',
            'avatar-02.svg',
            'avatar-03.svg',
            'avatar-04.svg',
            'avatar-05.svg',
            'avatar-06.svg',
            'avatar-07.svg',
            'avatar-08.svg',
            'avatar-09.svg',
            'avatar-10.svg',
        ];
    }



    // Relaciones 

    /**
     * Obtener los banners creados por este usuario.
     */

    public function banners ()
    {
        return $this->hasMany(Banner::class,'user_id', 'id');
    }

        /**
     * Obtener los destacados creados por este usuario.
     */

    public function destacados ()
    {
        return $this->hasMany(Destacado::class,'user_id', 'id');
    }

    
    /**
     * Obtener los productos creados por este usuario.
     */

    public function productos ()
    {
        return $this->hasMany(Producto::class,'user_id', 'id');
    }

        /**
     * Obtener los servicios creados por este usuario.
     */

    public function servicios ()
    {
        return $this->hasMany(Servicio::class,'user_id', 'id');
    }

        /**
     * Obtener los partners creados por este usuario.
     */

    public function partners ()
    {
        return $this->hasMany(Partner::class,'user_id', 'id');
    }

        /**
     * Obtener los clientes creados por este usuario.
     */

    public function clientes ()
    {
        return $this->hasMany(Cliente::class,'user_id', 'id');
    }

        /**
     * Obtener las noticias creados por este usuario.
     */

    public function noticias ()
    {
        return $this->hasMany(Noticia::class,'user_id', 'id');
    }

    //Scope

    public function scopeAdministrators($query)
    {
        return $query->where('usu_rol', 'Administrador');
    }

    public function scopeEditors($query)
    {
        return $query->where('usu_rol', 'Editor');
    }

    public function scopeActive($query)
    {
        return $query->where('usu_estado', 'Activo');
    }

    public function scopePending($query)
    {
        return $query->where('usu_estado', 'Pendiente');
    }

    public function scopeInactive($query)
    {
        return $query->where('usu_estado', 'Inactivo');
    }

    public function scopeThisMonth($query, $year = null, $month = null)
    {
        $year = $year ?? now()->year;
        $month = $month ?? now()->month;
        
        return $query->whereYear('created_at', $year)
                    ->whereMonth('created_at', $month);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function($q) use ($search) {
            $q->where('usu_nombre', 'LIKE', "%{$search}%")
            ->orWhere('email', 'LIKE', "%{$search}%");
        });
    }

    //Getters
    public function getFullNameAttribute(): string
    {
        return $this->usu_nombre;
    }

    public function getInitialsAttribute(): string
    {
        $words = explode(' ', trim($this->usu_nombre));
        if (count($words) >= 2) {
            return strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
        }
        return strtoupper(substr($this->usu_nombre, 0, 2));
    }

    public function getRoleBadgeAttribute(): string
    {
        return $this->usu_rol === 'Administrador' ? 'danger' : 'primary';
    }

    public function getStatusBadgeAttribute(): string
    {
        $badges = [
            'Activo' => 'success',
            'Pendiente' => 'warning',
            'Inactivo' => 'secondary'
        ];

        return $badges[$this->usu_estado] ?? 'secondary';
    }

    public function getRegistrationDateAttribute(): string
    {
        return $this->created_at->format('d/m/Y');
    }

    public function getRegistrationDateHumanAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }

    //Mutators
    public function setUsuNombreAttribute($value): void
    {
        $this->attributes['usu_nombre'] = trim($value);
    }

    public function setEmailAttribute($value): void
    {
        $this->attributes['email'] = strtolower(trim($value));
    }

    //Métodos de rol
    public function isAdmin(): bool
    {
        return $this->usu_rol === 'Administrador';
    }

    public function isEditor(): bool
    {
        return $this->usu_rol === 'Editor';
    }

    public function hasRole(string $role): bool
    {
        return $this->usu_rol === $role;
    }

    public function hasAnyRole(array $roles): bool
    {
        return in_array($this->usu_rol, $roles);
    }

    //Métodos de estatus 
    public function isActive(): bool
    {
        return $this->usu_estado === 'Activo';
    }

    public function isPending(): bool
    {
        return $this->usu_estado === 'Pendiente';
    }

    public function isInactive(): bool
    {
        return $this->usu_estado === 'Inactivo';
    }

    public function activate(): self
    {
        $this->update(['usu_estado' => 'Activo']);
        return $this;
    }

    public function deactivate(): self
    {
        $this->update(['usu_estado' => 'Inactivo']);
        return $this;
    }

    public function suspend(): self
    {
        $this->update(['usu_estado' => 'Inactivo']);
        return $this;
    }

    //Métodos de permiso
    public function canEdit(): bool
    {
        return $this->isActive() && in_array($this->usu_rol, ['Administrador', 'Editor']);
    }

    public function canDelete(): bool
    {
        return $this->isActive() && $this->isAdmin();
    }

    public function canManageUsers(): bool
    {
        return $this->isActive() && $this->isAdmin();
    }

    public function canPublish(): bool
    {
        return $this->isActive();
    }

    public function canExport(): bool
    {
        return $this->isActive();
    }

    public function canBulkOperations(): bool
    {
        return $this->isActive() && $this->isAdmin();
    }

    public function canViewStatistics(): bool
    {
        return $this->isActive();
    }

    //Métodos de actividad
    public function getTotalContent(): int
    {
        return $this->banners()->count() +
        $this->destacados()->count() +
        $this->productos()->count() +
        $this->servicios()->count() +
        $this->partners()->count() +
        $this->clientes()->count() +
        $this->noticias()->count();
    }

    public function getContentByType(): array
    {
        return [
            'banners' => $this->banners()->count(),
            'destacados' => $this->destacados()->count(),
            'productos' => $this->productos()->count(),
            'servicios' => $this->servicios()->count(),
            'partners' => $this->partners()->count(),
            'clientes' => $this->clientes()->count(),
            'noticias' => $this->noticias()->count(),
        ];
    }

    public function getLastActivity()
    {
        $lastNews = $this->noticias()->latest('updated_at')->first();
        $lastBanner = $this->banners()->latest('updated_at')->first();
        $lastService = $this->servicios()->latest('updated_at')->first();
        
        $activities = collect([
            $lastNews?->updated_at,
            $lastBanner?->updated_at,
            $lastService?->updated_at,
        ])->filter()->sort()->last();

        return $activities;
    }

    public function getActivitySummary(): array
    {
        return [
            'total_content' => $this->getTotalContent(),
            'content_by_type' => $this->getContentByType(),
            'last_activity' => $this->getLastActivity()?->diffForHumans() ?? 'Sin actividad',
            'registration_date' => $this->registration_date,
        ];
    }
    //Métodos de token (sanctum)
    public function getActiveTokensCount(): int
    {
        return $this->tokens()->count();
    }

    public function revokeAllTokens(): void
    {
        $this->tokens()->delete();
    }

    public function revokeOldTokens(int $daysOld = 30): int
    {
        return $this->tokens()
            ->where('created_at', '<', now()->subDays($daysOld))
            ->delete();
    }

    //Métodos estáticos
    public static function statistics(): array
    {
        return [
            'total' => static::count(),
            'administrators' => static::administrators()->count(),
            'editors' => static::editors()->count(),
            'active' => static::active()->count(),
            'pending' => static::pending()->count(),
            'inactive' => static::inactive()->count(),
            'new_this_month' => static::thisMonth()->count(),
            'active_admins' => static::administrators()->active()->count(),
        ];
    }

    public static function lastRegistered(int $limit = 5)
    {
        return static::latest('created_at')->limit($limit)->get();
    }

    public static function totalActiveAdmins(): int
    {
        return static::administrators()->active()->count();
    }

    public static function mostActiveUsers(int $limit = 5)
    {
        return static::withCount([
            'banners',
            'destacados',
            'productos',
            'servicios',
            'partners',
            'clientes',
            'noticias'
        ])
        ->orderByDesc('banners_count')
        ->limit($limit)
        ->get();
    }

    //Métodos de ayuda
    public function toArray(): array
    {
        $array = parent::toArray();
        
        // Agregar atributos calculados si no están ocultos
        if (!in_array('initials', $this->hidden)) {
            $array['initials'] = $this->initials;
        }
        
        return $array;
    }

    public function toPublicArray(): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->usu_nombre,
            'email' => $this->email,
            'rol' => $this->usu_rol,
            'estado' => $this->usu_estado,
            'iniciales' => $this->initials,
            'fecha_registro' => $this->registration_date,
        ];
    }

    //Métodos de arranque
    protected static function boot()
    {
        parent::boot();

        // Evento antes de crear un usuario
        static::creating(function ($user) {
            // Asegurar que el email esté en minúsculas
            if ($user->email) {
                $user->email = strtolower($user->email);
            }
        });

        // Evento antes de eliminar un usuario
        static::deleting(function ($user) {
            // Eliminar avatar si es tipo upload
            $user->deleteAvatarFile();
            
            // Revocar todos los tokens
            $user->tokens()->delete();

        });
    }
}