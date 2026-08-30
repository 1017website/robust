<?php

namespace App\Models;

use App\Models\Concerns\HasDeploymentSafeSoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasDeploymentSafeSoftDeletes;

    protected $fillable = [
        'name', 'email', 'password', 'role', 'job_title', 'phone', 'avatar', 'is_active',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function isAdministrator(): bool { return $this->role === 'administrator'; }
    public function isSales(): bool { return $this->role === 'sales'; }
    public function isDrafter(): bool { return $this->role === 'drafter'; }
    public function isProduction(): bool { return $this->role === 'production'; }
    public function isQc(): bool { return $this->role === 'qc'; }
    public function isDelivery(): bool { return $this->role === 'delivery'; }
    public function isAdministration(): bool { return $this->role === 'administration'; }
    public function isSalesSpv(): bool { return $this->role === 'sales_spv'; }

    /** Harga komersial hanya dapat dilihat administrator dan sales pemilik. */
    public function canViewPrices(): bool
    {
        return in_array($this->role, ['administrator', 'sales'], true);
    }

    /**
     * True hanya untuk Administrator (super admin).
     *
     * Dipakai untuk hak "melihat seluruh data lintas sales", bukan untuk pekerjaan
     * back office. Untuk itu gunakan canManageBackOffice().
     */
    public function isAdminLevel(): bool
    {
        return $this->role === 'administrator';
    }

    /**
     * Pekerjaan back office eks Sales Admin: Pra Lead, Assignment, proses Accurate
     * pada Request PO, dan Invoice. Setelah role Sales Admin dihapus, kewenangan ini
     * dipegang Administrator dan Sales.
     */
    public function canManageBackOffice(): bool
    {
        return in_array($this->role, ['administrator', 'sales'], true);
    }

    /** Kolom administrasi (comment, KP, bukti potong PPh) pada Project Monitoring. */
    public function canManageProjectAdministration(): bool
    {
        return in_array($this->role, ['administrator', 'sales', 'administration'], true);
    }

    public function roleLabel(): string
    {
        return match ($this->role) {
            'administrator' => 'Administrator',
            'sales_spv' => 'SPV Sales',
            'sales' => 'Sales',
            'drafter' => 'Drafter',
            'production' => 'Produksi',
            'qc' => 'Quality Control',
            'delivery' => 'Delivery',
            'administration' => 'Administration',
            default => $this->role,
        };
    }

    /** Daftar role yang bisa dipilih saat manage user. */
    public static function roles(): array
    {
        return [
            'administrator' => 'Administrator',
            'sales_spv' => 'SPV Sales',
            'sales' => 'Sales',
            'drafter' => 'Drafter',
            'production' => 'Produksi',
            'qc' => 'Quality Control',
            'delivery' => 'Delivery',
            'administration' => 'Administration',
        ];
    }

    /**
     * Query user sales aktif untuk dropdown assignment.
     *
     * Dibuat sedikit lebih toleran agar data lama yang is_active NULL tetap tampil,
     * dan role dibandingkan secara case-insensitive. Ini mencegah dropdown sales kosong
     * pada database yang sudah ada sebelum kolom role/is_active dirapikan.
     */
    public static function assignableSalesQuery(): Builder
    {
        return static::query()
            ->whereRaw('LOWER(role) = ?', ['sales'])
            ->where(function (Builder $query) {
                $query->where('is_active', true)
                    ->orWhereNull('is_active');
            })
            ->orderBy('name');
    }

    public static function assignableSales()
    {
        return static::assignableSalesQuery()->get();
    }

    public static function assignableDraftersQuery(): Builder
    {
        return static::query()
            ->whereRaw('LOWER(role) = ?', ['drafter'])
            ->where(fn (Builder $query) => $query->where('is_active', true)->orWhereNull('is_active'))
            ->orderBy('name');
    }

    public function leads(): HasMany { return $this->hasMany(Lead::class, 'sales_id'); }
    public function customers(): HasMany { return $this->hasMany(Customer::class, 'sales_id'); }
    public function quotations(): HasMany { return $this->hasMany(Quotation::class, 'sales_id'); }
    public function assignedPraLeads(): HasMany { return $this->hasMany(PraLead::class, 'assigned_sales_id'); }
}
