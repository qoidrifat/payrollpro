import { createClient } from '@supabase/supabase-js'

const supabaseUrl = import.meta.env.VITE_SUPABASE_URL
const supabasePublishableKey = import.meta.env.VITE_SUPABASE_PUBLISHABLE_KEY || import.meta.env.VITE_SUPABASE_ANON_KEY
const realtimeEnabled = import.meta.env.VITE_SUPABASE_REALTIME_ENABLED === 'true'

export const isSupabaseRealtimeConfigured = Boolean(realtimeEnabled && supabaseUrl && supabasePublishableKey)

export const supabase = isSupabaseRealtimeConfigured
    ? createClient(supabaseUrl, supabasePublishableKey, {
        auth: {
            persistSession: false,
            autoRefreshToken: false,
            detectSessionInUrl: false,
        },
        realtime: {
            params: {
                eventsPerSecond: 5,
            },
        },
    })
    : null
