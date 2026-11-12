<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
  use HasApiTokens, HasRoles;

  /** @use HasFactory<\Database\Factories\UserFactory> */
  use HasFactory;
  use HasProfilePhoto;
  use Notifiable;
  use TwoFactorAuthenticatable;

  /**
   * The attributes that are mass assignable.
   *
   * @var array<int, string>
   */
  protected $fillable = [
    'name',
    'user_name',
    'email',
    'password',
    'last_login_at',
  ];

  /**
   * The attributes that should be hidden for serialization.
   *
   * @var array<int, string>
   */
  protected $hidden = [
    'password',
    'remember_token',
    'two_factor_recovery_codes',
    'two_factor_secret',
  ];

  /**
   * The accessors to append to the model's array form.
   *
   * @var array<int, string>
   */
  protected $appends = [
    'profile_photo_url',
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
      'last_login_at' => 'datetime',
    ];
  }

  public function Ptk(): HasOne
  {
    return $this->hasOne(Ptk::class, 'user_id');
  }

  public function Siswa(): HasOne
  {
    return $this->hasOne(Siswa::class, 'user_id');
  }

  // Formulir K3S yang dibuat pengguna ini (sebagai Admin)
  public function formulirDibuat(): HasMany
  {
    return $this->hasMany(Formulir::class, 'created_by');
  }

  // Jawaban formulir K3S yang dikirim pengguna ini
  public function jawabanFormulir(): HasMany
  {
    return $this->hasMany(FormulirJawaban::class, 'user_id');
  }
}
