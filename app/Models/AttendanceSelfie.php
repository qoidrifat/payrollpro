<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceSelfie extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_id', 'employee_id',
        'image_path', 'verified_at', 'verification_score',
        'device_info', 'gps_latitude', 'gps_longitude',
        'gps_accuracy', 'captured_at',
    ];

    protected function casts(): array
    {
        return [
            'verified_at'        => 'datetime',
            'captured_at'        => 'datetime',
            'verification_score' => 'decimal:4',
            'gps_latitude'       => 'decimal:8',
            'gps_longitude'      => 'decimal:8',
        ];
    }

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function scopeVerified($query)
    {
        return $query->whereNotNull('verified_at');
    }

    public function scopePending($query)
    {
        return $query->whereNull('verified_at');
    }
}
