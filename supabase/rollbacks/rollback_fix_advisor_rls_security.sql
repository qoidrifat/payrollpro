-- Conservative rollback for 20260606155331_fix_advisor_rls_security.sql.
-- This rollback does not restore broad table/sequence grants because the
-- previous grants were unsafe and should be restored only from a documented
-- pre-migration grant snapshot if the business explicitly requires them.
-- RLS remains enabled.

begin;

-- Restore the previous known realtime policy shape from the Laravel migration.
-- This may reintroduce broader anonymous visibility on realtime_notifications;
-- prefer keeping the restricted forward policy unless realtime breaks and the
-- business accepts the exposure.
drop policy if exists realtime_notifications_select on public.realtime_notifications;

grant select on table public.realtime_notifications to anon, authenticated;

create policy realtime_notifications_select
on public.realtime_notifications
for select
to anon, authenticated
using (true);

comment on policy realtime_notifications_select on public.realtime_notifications
is 'Rollback: restored original broad read policy from the Laravel realtime migration.';

-- Intentionally not disabling RLS on any table.
-- Intentionally not restoring anon/authenticated/PUBLIC grants on backend-only
-- tables or sequences without a reviewed pre-migration grant snapshot.

commit;
