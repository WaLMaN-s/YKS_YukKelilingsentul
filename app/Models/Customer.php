<?php

namespace App\Models;

use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Customer extends Model
{
    /** @use HasFactory<CustomerFactory> */
    use HasFactory;

    protected $fillable = ['user_id', 'nik', 'address', 'birth_date', 'sim_number', 'ktp_path', 'sim_path'];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'nik' => 'encrypted',
            'sim_number' => 'encrypted',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
