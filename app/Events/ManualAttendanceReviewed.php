<?php

namespace App\Events;

use App\Models\ManualAttendanceRequest;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ManualAttendanceReviewed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly ManualAttendanceRequest $manualAttendanceRequest,
    ) {}
}
