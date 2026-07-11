<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('realtime_notifications')) {
            Schema::create('realtime_notifications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
                $table->string('topic', 80);
                $table->string('table_name', 80);
                $table->string('event', 20);
                $table->unsignedBigInteger('record_id')->nullable();
                $table->timestamp('occurred_at')->useCurrent();
                $table->timestamps();

                $table->index(['topic', 'occurred_at']);
                $table->index(['company_id', 'topic']);
            });
        }

        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE public.realtime_notifications ENABLE ROW LEVEL SECURITY');
        DB::statement('GRANT SELECT ON public.realtime_notifications TO anon, authenticated');

        DB::statement(<<<'SQL'
            DO $$
            BEGIN
                IF NOT EXISTS (
                    SELECT 1
                    FROM pg_policies
                    WHERE schemaname = 'public'
                      AND tablename = 'realtime_notifications'
                      AND policyname = 'realtime_notifications_select'
                ) THEN
                    CREATE POLICY realtime_notifications_select
                    ON public.realtime_notifications
                    FOR SELECT
                    TO anon, authenticated
                    USING (true);
                END IF;
            END
            $$;
        SQL);

        DB::statement(<<<'SQL'
            CREATE OR REPLACE FUNCTION public.enqueue_realtime_notification()
            RETURNS trigger
            LANGUAGE plpgsql
            AS $$
            DECLARE
                row_data jsonb;
                row_company_id bigint;
                row_record_id bigint;
            BEGIN
                row_data := CASE WHEN TG_OP = 'DELETE' THEN to_jsonb(OLD) ELSE to_jsonb(NEW) END;
                row_company_id := NULLIF(row_data->>'company_id', '')::bigint;
                row_record_id := NULLIF(row_data->>'id', '')::bigint;

                INSERT INTO public.realtime_notifications (
                    company_id,
                    topic,
                    table_name,
                    event,
                    record_id,
                    occurred_at,
                    created_at,
                    updated_at
                ) VALUES (
                    row_company_id,
                    TG_ARGV[0],
                    TG_TABLE_NAME,
                    TG_OP,
                    row_record_id,
                    now(),
                    now(),
                    now()
                );

                RETURN CASE WHEN TG_OP = 'DELETE' THEN OLD ELSE NEW END;
            END;
            $$;
        SQL);

        foreach ([
            'attendances' => 'attendance',
            'payrolls' => 'payroll',
            'leave_requests' => 'leave',
        ] as $table => $topic) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            DB::statement("DROP TRIGGER IF EXISTS {$table}_realtime_notify ON public.{$table}");
            DB::statement("
                CREATE TRIGGER {$table}_realtime_notify
                AFTER INSERT OR UPDATE OR DELETE
                ON public.{$table}
                FOR EACH ROW
                EXECUTE FUNCTION public.enqueue_realtime_notification('{$topic}')
            ");
        }

        DB::statement(<<<'SQL'
            DO $$
            BEGIN
                IF EXISTS (SELECT 1 FROM pg_publication WHERE pubname = 'supabase_realtime')
                   AND NOT EXISTS (
                       SELECT 1
                       FROM pg_publication_tables
                       WHERE pubname = 'supabase_realtime'
                         AND schemaname = 'public'
                         AND tablename = 'realtime_notifications'
                   ) THEN
                    ALTER PUBLICATION supabase_realtime ADD TABLE public.realtime_notifications;
                END IF;
            END
            $$;
        SQL);
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            foreach (['attendances', 'payrolls', 'leave_requests'] as $table) {
                DB::statement("DROP TRIGGER IF EXISTS {$table}_realtime_notify ON public.{$table}");
            }

            DB::statement('DROP FUNCTION IF EXISTS public.enqueue_realtime_notification()');
        }

        Schema::dropIfExists('realtime_notifications');
    }
};
