<?php

use App\Models\BpjsConfig;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Update BPJS JP salary cap from the outdated Rp 10,042,300
     * to the current standard Rp 10,547,400 (PPU 2026).
     */
    public function up(): void
    {
        $updated = BpjsConfig::where('type', 'tk_jp')
            ->where('salary_cap', 10042300)
            ->update(['salary_cap' => 10547400]);

        echo "Updated {$updated} BPJS JP record(s): salary cap 10.042.300 → 10.547.400\n";
    }

    public function down(): void
    {
        BpjsConfig::where('type', 'tk_jp')
            ->where('salary_cap', 10547400)
            ->update(['salary_cap' => 10042300]);
    }
};
