<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

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
    public function isSalesAdmin(): bool { return $this->role === 'sales_admin'; }
    public function isSales(): bool { return $this->role === 'sales'; }
    public function isDrafter(): bool { return $this->role === 'drafter'; }
    public function isSalesSpv(): bool { return $this->role === 'sales_spv'; }

    /** True untuk administrator maupun sales_admin (level admin ke atas). */
    public function isAdminLevel(): bool
    {
        return in_array($this->role, ['administrator', 'sales_admin'], true);
    }

    public function roleLabel(): string
    {
        return match ($this->role) {
            'administrator' => 'Administrator',
            'sales_admin' => 'Sales Admin',
            'sales_spv' => 'SPV Sales',
            'sales' => 'Sales',
            'drafter' => 'Produksi / Drafter',
            default => $this->role,
        };
    }

    /** Daftar role yang bisa dipilih saat manage user. */
    public static function roles(): array
    {
        return [
            'administrator' => 'Administrator',
            'sales_admin' => 'Sales Admin',
            'sales_spv' => 'SPV Sales',
            'sales' => 'Sales',
            'drafter' => 'Produksi / Drafter',
        ];
    }

    public function leads(): HasMany { return $this->hasMany(Lead::class, 'sales_id'); }
    public function customers(): HasMany { return $this->hasMany(Customer::class, 'sales_id'); }
    public function quotations(): HasMany { return $this->hasMany(Quotation::class, 'sales_id'); }
    public function assignedPraLeads(): HasMany { return $this->hasMany(PraLead::class, 'assigned_sales_id'); }
}
