<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payslip extends Model
{
    use HasFactory;

    protected $fillable = [
        'payroll_item_id', 'payslip_number', 'pdf_path',
        'generated_at', 'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'generated_at' => 'datetime',
            'sent_at' => 'datetime',
        ];
    }

    public function payrollItem()
    {
        return $this->belongsTo(PayrollItem::class);
    }
}
