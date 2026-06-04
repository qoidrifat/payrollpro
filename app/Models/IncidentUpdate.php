<?php

namespace App\Models;

use App\Enums\IncidentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IncidentUpdate extends Model
{
    use HasFactory;

    protected $fillable = [
        'incident_id', 'message', 'status', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => IncidentStatus::class,
        ];
    }

    public function incident()
    {
        return $this->belongsTo(Incident::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
