<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use  HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
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
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }


    /**
         * کیف پول کاربر
     */
    public function wallet()
    {
        return $this->hasOne(Wallet::class);
    }

    /**
     * فاکتورهای کاربر
     */
    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * پرداخت‌های کاربر (از طریق relation مورف یا مستقیم)
     * اگر Payment جدول user_id دارد
     */
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * اگر Payment user_id نداشته باشه
     * می‌تونیم از طریق invoices برسیم
     */
    public function paymentsViaInvoices()
    {
        return $this->hasManyThrough(
            Payment::class,
            Invoice::class,
            'user_id',     // کلید خارجی در جدول Invoice
            'payable_id',  // کلید خارجی در جدول Payment
            'id',          // کلید محلی در جدول User
            'id'           // کلید محلی در جدول Invoice
        )->where('payable_type', Invoice::class);
    }
}
