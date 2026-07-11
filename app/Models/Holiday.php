<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    use BelongsToCompany, HasFactory;

    protected $fillable = [
        'company_id', 'name', 'date',
        'is_recurring', 'is_national',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'date'         => 'date',
            'is_recurring' => 'boolean',
            'is_national'  => 'boolean',
        ];
    }

    public function scopeForDate($query, string $date)
    {
        return $query->whereDate('date', $date);
    }

    public function scopeForYear($query, int $year)
    {
        return $query->whereYear('date', $year);
    }

    public function scopeRecurring($query)
    {
        return $query->where('is_recurring', true);
    }

    /**
     * Check if a given date is a holiday (including recurring year-agnostic check).
     */
    public static function isHoliday(string $date, ?int $companyId = null): bool
    {
        $parsed = \Carbon\Carbon::parse($date);

        return static::where(function ($query) use ($parsed, $companyId) {
            // Exact date match
            $query->whereDate('date', $parsed)
                ->when($companyId, fn($q) => $q->where('company_id', $companyId));
        })->orWhere(function ($query) use ($parsed, $companyId) {
            // Recurring: same month + day, different year
            $query->where('is_recurring', true)
                ->whereMonth('date', $parsed->month)
                ->whereDay('date', $parsed->day)
                ->when($companyId, fn($q) => $q->where('company_id', $companyId));
        })->exists();
    }
}
