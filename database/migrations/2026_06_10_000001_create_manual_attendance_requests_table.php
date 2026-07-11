<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manual_attendance_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attendance_id')->nullable()->constrained()->nullOnDelete();
            $table->string('request_type', 30);
            $table->date('requested_date');
            $table->time('requested_time');
            $table->text('reason');
            $table->string('evidence_path')->nullable();
            $table->string('status', 20)->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->string('source', 20)->default('manual');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'requested_date']);
            $table->index(['status', 'created_at']);
            $table->index(['request_type', 'requested_date']);
        });

        Schema::table('attendances', function (Blueprint $table) {
            if (! Schema::hasColumn('attendances', 'source')) {
                $table->string('source', 20)->default('qr');
            }

            if (! Schema::hasColumn('attendances', 'approved_by')) {
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('attendances', 'approved_at')) {
                $table->timestamp('approved_at')->nullable();
            }
        });

        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE public.manual_attendance_requests ENABLE ROW LEVEL SECURITY');
        DB::statement('REVOKE ALL ON public.manual_attendance_requests FROM anon, authenticated');

        DB::statement('DROP TRIGGER IF EXISTS manual_attendance_requests_realtime_notify ON public.manual_attendance_requests');
        DB::statement("
            CREATE TRIGGER manual_attendance_requests_realtime_notify
            AFTER INSERT OR UPDATE OR DELETE
            ON public.manual_attendance_requests
            FOR EACH ROW
            EXECUTE FUNCTION public.enqueue_realtime_notification('manual_attendance')
        ");
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('DROP TRIGGER IF EXISTS manual_attendance_requests_realtime_notify ON public.manual_attendance_requests');
        }

        Schema::table('attendances', function (Blueprint $table) {
            foreach (['approved_at', 'approved_by', 'source'] as $column) {
                if (Schema::hasColumn('attendances', $column)) {
                    if ($column === 'approved_by') {
                        $table->dropForeign(['approved_by']);
                    }

                    $table->dropColumn($column);
                }
            }
        });

        Schema::dropIfExists('manual_attendance_requests');
    }
};
