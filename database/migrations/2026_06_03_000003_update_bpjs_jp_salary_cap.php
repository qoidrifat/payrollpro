<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Update BPJS JP salary cap from the outdated Rp 10,042,300
     * to the current standard Rp 10,547,400 (PPU 2026).
     *
     * Uses the query builder, not the BpjsConfig Eloquent model: a migration is
     * an immutable historical snapshot, so it must not depend on the model whose
     * casts/global scopes (e.g. tenant scoping) may change later and silently
     * alter which rows this migration touches.
     */
    public function up(): void
    {
        $updated = DB::table('bpjs_configs')
            ->where('type', 'tk_jp')
            ->where('salary_cap', 10042300)
            ->update(['salary_cap' => 10547400]);

        echo "Updated {$updated} BPJS JP record(s): salary cap 10.042.300 → 10.547.400\n";
    }

    public function down(): void
    {
        DB::table('bpjs_configs')
            ->where('type', 'tk_jp')
            ->where('salary_cap', 10547400)
            ->update(['salary_cap' => 10042300]);
    }
};
