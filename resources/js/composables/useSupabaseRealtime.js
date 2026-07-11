import { ref } from 'vue'
import { isSupabaseRealtimeConfigured, supabase } from '@/lib/supabase'

export function useSupabaseRealtime() {
    const status = ref(isSupabaseRealtimeConfigured ? 'idle' : 'disabled')

    const subscribeToNotifications = ({ channelName, topics, onChange }) => {
        if (!isSupabaseRealtimeConfigured || !supabase) {
            status.value = 'disabled'
            return () => {}
        }

        const channel = supabase.channel(channelName)

        topics.forEach((topic) => {
            channel.on(
                'postgres_changes',
                {
                    event: 'INSERT',
                    schema: 'public',
                    table: 'realtime_notifications',
                    filter: `topic=eq.${topic}`,
                },
                (payload) => onChange?.(payload),
            )
        })

        channel.subscribe((nextStatus) => {
            status.value = nextStatus
        })

        return () => {
            supabase.removeChannel(channel)
            status.value = isSupabaseRealtimeConfigured ? 'idle' : 'disabled'
        }
    }

    return {
        status,
        isConfigured: isSupabaseRealtimeConfigured,
        subscribeToNotifications,
    }
}
