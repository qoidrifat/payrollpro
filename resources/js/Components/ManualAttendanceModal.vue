<script setup>
import { computed, reactive, ref, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import Modal from '@/Components/Modal.vue'

const props = defineProps({
    show: { type: Boolean, default: false },
    defaultType: { type: String, default: 'manual_clock_in' },
})

const emit = defineEmits(['close', 'submitted'])
const page = usePage()
const processing = ref(false)
const evidenceInput = ref(null)

// Local (not UTC) Y-m-d so the :max date bound is correct in WIB near midnight.
const localDate = () => {
    const d = new Date()
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`
}
const localTime = () => new Date().toTimeString().slice(0, 5)

// Reactive so a modal left open past midnight refreshes its date bound on reopen.
const todayDate = ref(localDate())

const form = reactive({
    request_type: props.defaultType,
    requested_date: todayDate.value,
    requested_time: localTime(),
    reason: '',
    evidence: null,
})

watch(() => props.show, (isShown) => {
    if (!isShown) return

    todayDate.value = localDate()
    form.request_type = props.defaultType
    form.requested_date = todayDate.value
    form.requested_time = localTime()
    form.reason = ''
    form.evidence = null

    if (page.props.errors) {
        Object.keys(page.props.errors).forEach(key => delete page.props.errors[key])
    }

    if (evidenceInput.value) {
        evidenceInput.value.value = ''
    }
})

const titleLabel = computed(() =>
    form.request_type === 'manual_clock_in' ? 'Manual Clock-In' : 'Manual Clock-Out'
)

const canSubmit = computed(() =>
    form.request_type &&
    form.requested_date &&
    form.requested_time &&
    form.reason.trim().length >= 10 &&
    !processing.value
)

const submit = () => {
    if (!canSubmit.value) return

    processing.value = true
    router.post(route('manual-attendance-requests.store'), {
        request_type: form.request_type,
        requested_date: form.requested_date,
        requested_time: form.requested_time,
        reason: form.reason,
        evidence: form.evidence,
    }, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            emit('submitted')
            emit('close')
        },
        onFinish: () => {
            processing.value = false
        },
    })
}
</script>

<template>
    <Modal :show="show" max-width="xl" title="Pengajuan Absen Manual" @close="emit('close')">
        <div class="space-y-5">
            <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-800 dark:bg-gray-950">
                <p class="text-sm font-semibold text-gray-950 dark:text-white">{{ titleLabel }}</p>
                <p class="mt-1 text-sm leading-6 text-gray-500 dark:text-gray-400">
                    Pengajuan ini akan masuk ke HR/Admin terlebih dahulu. Attendance resmi dan payroll baru berubah setelah disetujui.
                </p>
            </div>

            <div>
                <label class="form-label">Tipe Pengajuan</label>
                <select v-model="form.request_type" class="form-input">
                    <option value="manual_clock_in">Manual Clock-In</option>
                    <option value="manual_clock_out">Manual Clock-Out</option>
                </select>
                <p v-if="page.props.errors?.request_type" class="mt-1 text-xs text-red-600">{{ page.props.errors.request_type }}</p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="form-label">Tanggal</label>
                    <input v-model="form.requested_date" type="date" class="form-input" :max="todayDate">
                    <p v-if="page.props.errors?.requested_date" class="mt-1 text-xs text-red-600">{{ page.props.errors.requested_date }}</p>
                </div>
                <div>
                    <label class="form-label">Jam yang Diajukan</label>
                    <input v-model="form.requested_time" type="time" class="form-input">
                    <p v-if="page.props.errors?.requested_time" class="mt-1 text-xs text-red-600">{{ page.props.errors.requested_time }}</p>
                </div>
            </div>

            <div>
                <label class="form-label">Alasan/Keterangan Kendala</label>
                <textarea
                    v-model="form.reason"
                    rows="4"
                    class="form-input"
                    placeholder="Contoh: kamera tidak bisa membaca QR karena koneksi kantor bermasalah."
                />
                <div class="mt-1 flex items-center justify-between gap-3">
                    <p v-if="page.props.errors?.reason" class="text-xs text-red-600">{{ page.props.errors.reason }}</p>
                    <p v-else class="text-xs text-gray-400">Minimal 10 karakter.</p>
                    <p class="text-xs text-gray-400">{{ form.reason.trim().length }}/1000</p>
                </div>
            </div>

            <div>
                <label class="form-label">Bukti/Screenshot Opsional</label>
                <input
                    ref="evidenceInput"
                    type="file"
                    accept=".jpg,.jpeg,.png,.webp,.pdf"
                    class="block w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 file:mr-3 file:rounded-lg file:border-0 file:bg-gray-100 file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-gray-700 hover:file:bg-gray-200 dark:border-gray-800 dark:bg-gray-950 dark:text-gray-200 dark:file:bg-gray-800 dark:file:text-gray-200"
                    @change="form.evidence = $event.target.files?.[0] || null"
                >
                <p v-if="page.props.errors?.evidence" class="mt-1 text-xs text-red-600">{{ page.props.errors.evidence }}</p>
                <p v-else class="mt-1 text-xs text-gray-400">Format JPG, PNG, WEBP, atau PDF. Maksimal 2 MB.</p>
            </div>
        </div>

        <template #footer>
            <button class="btn-secondary" :disabled="processing" @click="emit('close')">Batal</button>
            <button class="btn-primary" :disabled="!canSubmit" @click="submit">
                {{ processing ? 'Mengirim...' : 'Kirim Pengajuan' }}
            </button>
        </template>
    </Modal>
</template>
