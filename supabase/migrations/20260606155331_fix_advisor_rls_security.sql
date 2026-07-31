-- Supabase Advisor security remediation for project-kp.
-- Review before applying to a remote Supabase database.
-- Scope:
--   1. Enable RLS on all known public tables from the Laravel/Supabase schema.
--   2. Revoke Data API roles from backend-only and admin/business tables.
--   3. Keep the intentionally frontend-facing realtime notification stream read-only.
--   4. Pin the realtime trigger function search_path to resolve Advisor warning 0011.

begin;

-- Fix: Function Search Path Mutable for public.enqueue_realtime_notification.
create or replace function public.enqueue_realtime_notification()
returns trigger
language plpgsql
set search_path = pg_catalog, public
as $function$
declare
    row_data jsonb;
    row_company_id bigint;
    row_record_id bigint;
begin
    row_data := case when TG_OP = 'DELETE' then to_jsonb(OLD) else to_jsonb(NEW) end;
    row_company_id := nullif(row_data->>'company_id', '')::bigint;
    row_record_id := nullif(row_data->>'id', '')::bigint;

    insert into public.realtime_notifications (
        company_id,
        topic,
        table_name,
        event,
        record_id,
        occurred_at,
        created_at,
        updated_at
    ) values (
        row_company_id,
        TG_ARGV[0],
        TG_TABLE_NAME,
        TG_OP,
        row_record_id,
        now(),
        now(),
        now()
    );

    return case when TG_OP = 'DELETE' then OLD else NEW end;
end;
$function$;

-- Enable RLS on every known public table.
alter table if exists public.activity_logs enable row level security;
alter table if exists public.approvals enable row level security;
alter table if exists public.attendance_selfies enable row level security;
alter table if exists public.attendances enable row level security;
alter table if exists public.bpjs_configs enable row level security;
alter table if exists public.cache enable row level security;
alter table if exists public.cache_locks enable row level security;
alter table if exists public.companies enable row level security;
alter table if exists public.employees enable row level security;
alter table if exists public.failed_jobs enable row level security;
alter table if exists public.holidays enable row level security;
alter table if exists public.incident_service enable row level security;
alter table if exists public.incident_updates enable row level security;
alter table if exists public.incidents enable row level security;
alter table if exists public.job_batches enable row level security;
alter table if exists public.jobs enable row level security;
alter table if exists public.leave_requests enable row level security;
alter table if exists public.maintenance_schedules enable row level security;
alter table if exists public.migrations enable row level security;
alter table if exists public.model_has_permissions enable row level security;
alter table if exists public.model_has_roles enable row level security;
alter table if exists public.notifications enable row level security;
alter table if exists public.office_locations enable row level security;
alter table if exists public.overtime_requests enable row level security;
alter table if exists public.overtime_rules enable row level security;
alter table if exists public.password_reset_tokens enable row level security;
alter table if exists public.payroll_items enable row level security;
alter table if exists public.payrolls enable row level security;
alter table if exists public.payslips enable row level security;
alter table if exists public.permissions enable row level security;
alter table if exists public.personal_access_tokens enable row level security;
alter table if exists public.pph21_configs enable row level security;
alter table if exists public.ptkp_configs enable row level security;
alter table if exists public.pulse_aggregates enable row level security;
alter table if exists public.pulse_entries enable row level security;
alter table if exists public.pulse_values enable row level security;
alter table if exists public.realtime_notifications enable row level security;
alter table if exists public.role_has_permissions enable row level security;
alter table if exists public.roles enable row level security;
alter table if exists public.salary_components enable row level security;
alter table if exists public.service_metrics enable row level security;
alter table if exists public.sessions enable row level security;
alter table if exists public.settings enable row level security;
alter table if exists public.shift_assignments enable row level security;
alter table if exists public.shifts enable row level security;
alter table if exists public.system_services enable row level security;
alter table if exists public.uptime_logs enable row level security;
alter table if exists public.user_notifications enable row level security;
alter table if exists public.users enable row level security;

-- Revoke unsafe direct Data API access from backend-only/admin/business tables.
revoke all privileges on table public.activity_logs from anon;
revoke all privileges on table public.activity_logs from authenticated;
revoke all privileges on table public.activity_logs from public;
revoke all privileges on table public.approvals from anon;
revoke all privileges on table public.approvals from authenticated;
revoke all privileges on table public.approvals from public;
revoke all privileges on table public.attendance_selfies from anon;
revoke all privileges on table public.attendance_selfies from authenticated;
revoke all privileges on table public.attendance_selfies from public;
revoke all privileges on table public.attendances from anon;
revoke all privileges on table public.attendances from authenticated;
revoke all privileges on table public.attendances from public;
revoke all privileges on table public.bpjs_configs from anon;
revoke all privileges on table public.bpjs_configs from authenticated;
revoke all privileges on table public.bpjs_configs from public;
revoke all privileges on table public.cache from anon;
revoke all privileges on table public.cache from authenticated;
revoke all privileges on table public.cache from public;
revoke all privileges on table public.cache_locks from anon;
revoke all privileges on table public.cache_locks from authenticated;
revoke all privileges on table public.cache_locks from public;
revoke all privileges on table public.companies from anon;
revoke all privileges on table public.companies from authenticated;
revoke all privileges on table public.companies from public;
revoke all privileges on table public.employees from anon;
revoke all privileges on table public.employees from authenticated;
revoke all privileges on table public.employees from public;
revoke all privileges on table public.failed_jobs from anon;
revoke all privileges on table public.failed_jobs from authenticated;
revoke all privileges on table public.failed_jobs from public;
revoke all privileges on table public.holidays from anon;
revoke all privileges on table public.holidays from authenticated;
revoke all privileges on table public.holidays from public;
revoke all privileges on table public.incident_service from anon;
revoke all privileges on table public.incident_service from authenticated;
revoke all privileges on table public.incident_service from public;
revoke all privileges on table public.incident_updates from anon;
revoke all privileges on table public.incident_updates from authenticated;
revoke all privileges on table public.incident_updates from public;
revoke all privileges on table public.incidents from anon;
revoke all privileges on table public.incidents from authenticated;
revoke all privileges on table public.incidents from public;
revoke all privileges on table public.job_batches from anon;
revoke all privileges on table public.job_batches from authenticated;
revoke all privileges on table public.job_batches from public;
revoke all privileges on table public.jobs from anon;
revoke all privileges on table public.jobs from authenticated;
revoke all privileges on table public.jobs from public;
revoke all privileges on table public.leave_requests from anon;
revoke all privileges on table public.leave_requests from authenticated;
revoke all privileges on table public.leave_requests from public;
revoke all privileges on table public.maintenance_schedules from anon;
revoke all privileges on table public.maintenance_schedules from authenticated;
revoke all privileges on table public.maintenance_schedules from public;
revoke all privileges on table public.migrations from anon;
revoke all privileges on table public.migrations from authenticated;
revoke all privileges on table public.migrations from public;
revoke all privileges on table public.model_has_permissions from anon;
revoke all privileges on table public.model_has_permissions from authenticated;
revoke all privileges on table public.model_has_permissions from public;
revoke all privileges on table public.model_has_roles from anon;
revoke all privileges on table public.model_has_roles from authenticated;
revoke all privileges on table public.model_has_roles from public;
revoke all privileges on table public.notifications from anon;
revoke all privileges on table public.notifications from authenticated;
revoke all privileges on table public.notifications from public;
revoke all privileges on table public.office_locations from anon;
revoke all privileges on table public.office_locations from authenticated;
revoke all privileges on table public.office_locations from public;
revoke all privileges on table public.overtime_requests from anon;
revoke all privileges on table public.overtime_requests from authenticated;
revoke all privileges on table public.overtime_requests from public;
revoke all privileges on table public.overtime_rules from anon;
revoke all privileges on table public.overtime_rules from authenticated;
revoke all privileges on table public.overtime_rules from public;
revoke all privileges on table public.password_reset_tokens from anon;
revoke all privileges on table public.password_reset_tokens from authenticated;
revoke all privileges on table public.password_reset_tokens from public;
revoke all privileges on table public.payroll_items from anon;
revoke all privileges on table public.payroll_items from authenticated;
revoke all privileges on table public.payroll_items from public;
revoke all privileges on table public.payrolls from anon;
revoke all privileges on table public.payrolls from authenticated;
revoke all privileges on table public.payrolls from public;
revoke all privileges on table public.payslips from anon;
revoke all privileges on table public.payslips from authenticated;
revoke all privileges on table public.payslips from public;
revoke all privileges on table public.permissions from anon;
revoke all privileges on table public.permissions from authenticated;
revoke all privileges on table public.permissions from public;
revoke all privileges on table public.personal_access_tokens from anon;
revoke all privileges on table public.personal_access_tokens from authenticated;
revoke all privileges on table public.personal_access_tokens from public;
revoke all privileges on table public.pph21_configs from anon;
revoke all privileges on table public.pph21_configs from authenticated;
revoke all privileges on table public.pph21_configs from public;
revoke all privileges on table public.ptkp_configs from anon;
revoke all privileges on table public.ptkp_configs from authenticated;
revoke all privileges on table public.ptkp_configs from public;
revoke all privileges on table public.pulse_aggregates from anon;
revoke all privileges on table public.pulse_aggregates from authenticated;
revoke all privileges on table public.pulse_aggregates from public;
revoke all privileges on table public.pulse_entries from anon;
revoke all privileges on table public.pulse_entries from authenticated;
revoke all privileges on table public.pulse_entries from public;
revoke all privileges on table public.pulse_values from anon;
revoke all privileges on table public.pulse_values from authenticated;
revoke all privileges on table public.pulse_values from public;
revoke all privileges on table public.role_has_permissions from anon;
revoke all privileges on table public.role_has_permissions from authenticated;
revoke all privileges on table public.role_has_permissions from public;
revoke all privileges on table public.roles from anon;
revoke all privileges on table public.roles from authenticated;
revoke all privileges on table public.roles from public;
revoke all privileges on table public.salary_components from anon;
revoke all privileges on table public.salary_components from authenticated;
revoke all privileges on table public.salary_components from public;
revoke all privileges on table public.service_metrics from anon;
revoke all privileges on table public.service_metrics from authenticated;
revoke all privileges on table public.service_metrics from public;
revoke all privileges on table public.sessions from anon;
revoke all privileges on table public.sessions from authenticated;
revoke all privileges on table public.sessions from public;
revoke all privileges on table public.settings from anon;
revoke all privileges on table public.settings from authenticated;
revoke all privileges on table public.settings from public;
revoke all privileges on table public.shift_assignments from anon;
revoke all privileges on table public.shift_assignments from authenticated;
revoke all privileges on table public.shift_assignments from public;
revoke all privileges on table public.shifts from anon;
revoke all privileges on table public.shifts from authenticated;
revoke all privileges on table public.shifts from public;
revoke all privileges on table public.system_services from anon;
revoke all privileges on table public.system_services from authenticated;
revoke all privileges on table public.system_services from public;
revoke all privileges on table public.uptime_logs from anon;
revoke all privileges on table public.uptime_logs from authenticated;
revoke all privileges on table public.uptime_logs from public;
revoke all privileges on table public.user_notifications from anon;
revoke all privileges on table public.user_notifications from authenticated;
revoke all privileges on table public.user_notifications from public;
revoke all privileges on table public.users from anon;
revoke all privileges on table public.users from authenticated;
revoke all privileges on table public.users from public;

-- Realtime notifications are intentionally consumed from the browser through
-- Supabase Realtime postgres_changes. Keep read-only access only.
revoke all privileges on table public.realtime_notifications from anon;
revoke all privileges on table public.realtime_notifications from authenticated;
revoke all privileges on table public.realtime_notifications from public;
grant select on table public.realtime_notifications to anon, authenticated;

drop policy if exists realtime_notifications_select on public.realtime_notifications;
create policy realtime_notifications_select
on public.realtime_notifications
for select
to anon, authenticated
using (topic in ('attendance', 'payroll', 'leave'));

comment on policy realtime_notifications_select on public.realtime_notifications
is 'Read-only browser realtime stream for known project-kp dashboard topics only.';

-- Sequence access is not needed by anon/authenticated because browser clients
-- do not insert directly into public tables.
revoke all privileges on all sequences in schema public from anon;
revoke all privileges on all sequences in schema public from authenticated;
revoke all privileges on all sequences in schema public from public;

-- Reduce future accidental exposure for tables/sequences created by the role
-- that runs this migration. Tables that intentionally use the Data API should
-- add explicit GRANT statements and least-privilege RLS policies in their own
-- migrations.
alter default privileges in schema public revoke all on tables from anon;
alter default privileges in schema public revoke all on tables from authenticated;
alter default privileges in schema public revoke all on tables from public;
alter default privileges in schema public revoke all on sequences from anon;
alter default privileges in schema public revoke all on sequences from authenticated;
alter default privileges in schema public revoke all on sequences from public;

commit;
