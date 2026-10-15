<?php

namespace App\Models;

use Database\Factories\MotorcycleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Motorcycle extends Model
{
    /** @use HasFactory<MotorcycleFactory> */
    use HasFactory;

    public const STATUSES = [
        'available' => 'Tersedia',
        'maintenance' => 'Maintenance',
        'inactive' => 'Nonaktif',
    ];

    public const TYPES = ['trail' => 'Motor Trail', 'jeep' => 'Jeep'];

    protected $fillable = [
        'vehicle_type', 'stock', 'motor_category_id', 'name', 'slug', 'brand', 'model', 'plate_number', 'year', 'cc', 'transmission',
        'capacity', 'color', 'price_daily', 'price_weekly', 'price_monthly', 'deposit', 'description',
        'facilities', 'terms', 'status', 'is_popular', 'is_promo',
    ];

    protected function casts(): array
    {
        return [
            'facilities' => 'array',
            'is_popular' => 'boolean',
            'is_promo' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(MotorCategory::class, 'motor_category_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(MotorImage::class)->orderByDesc('is_primary')->orderBy('sort_order')->orderBy('id');
    }

    public function rentalItems(): HasMany
    {
        return $this->hasMany(RentalItem::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function approvedReviews(): HasMany
    {
        return $this->hasMany(Review::class)->where('status', 'approved');
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('vehicle_type', $type);
    }

    public function isJeep(): bool
    {
        return $this->vehicle_type === 'jeep';
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->vehicle_type] ?? $this->vehicle_type;
    }

    public function scopeVisible($query)
    {
        return $query->where('status', '!=', 'inactive');
    }

    /** Total unit per jenis kendaraan (trail/jeep) dalam SATU query — dulu dipanggil dua kali (trail & jeep). */
    public static function stockByType(): array
    {
        return static::visible()
            ->selectRaw('vehicle_type, sum(stock) as total')
            ->groupBy('vehicle_type')
            ->pluck('total', 'vehicle_type')
            ->map(fn ($total) => (int) $total)
            ->all();
    }

    public function getImageUrlAttribute(): string
    {
        $image = $this->images->first();

        return media_url($image?->path, 'images/placeholder.svg');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getFacilitiesListAttribute(): array
    {
        return $this->facilities ?? [];
    }
}
