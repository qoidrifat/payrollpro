<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { ChevronUpIcon, ChevronDownIcon, MagnifyingGlassIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    columns: { type: Array, required: true }, // [{key, label, sortable}]
    rows: { type: Array, required: true },
    searchPlaceholder: { type: String, default: 'Cari...' },
    hideSearch: { type: Boolean, default: false },
    // Server-side pagination props
    serverSide: { type: Boolean, default: false },
    total: { type: Number, default: 0 },
    currentPage: { type: Number, default: 1 },
    lastPage: { type: Number, default: 1 },
    perPage: { type: Number, default: 25 },
    filters: { type: Object, default: () => ({}) },
    baseRoute: { type: String, default: '' },
})

const emit = defineEmits(['row-click'])

const search = ref(props.filters.search || '')
const sortKey = ref(props.filters.sort || '')
const sortDir = ref(props.filters.dir || 'asc')
const pageSize = ref(props.perPage)

// Debounce timer for server-side search
let searchTimeout = null

// ─── Client-side pagination (fallback) ──────────────────────────
const filteredRows = computed(() => {
    if (props.serverSide) return props.rows

    let data = props.rows
    if (search.value) {
        const q = search.value.toLowerCase()
        data = data.filter(row =>
            props.columns.some(col => {
                const val = row[col.key]
                return val != null && String(val).toLowerCase().includes(q)
            })
        )
    }
    if (sortKey.value) {
        data = [...data].sort((a, b) => {
            const aVal = a[sortKey.value] ?? ''
            const bVal = b[sortKey.value] ?? ''
            const cmp = String(aVal).localeCompare(String(bVal), undefined, { numeric: true })
            return sortDir.value === 'asc' ? cmp : -cmp
        })
    }
    return data
})

const totalPages = computed(() => {
    if (props.serverSide) return props.lastPage
    return Math.ceil(filteredRows.value.length / pageSize.value)
})

const paginatedRows = computed(() => {
    if (props.serverSide) return props.rows
    const start = (props.currentPage - 1) * pageSize.value
    return filteredRows.value.slice(start, start + pageSize.value)
})

const displayRows = computed(() => props.serverSide ? props.rows : paginatedRows.value)
const displayTotal = computed(() => props.serverSide ? props.total : filteredRows.value.length)

// ─── Server-side actions ─────────────────────────────────────────
const navigateToPage = (page) => {
    if (!props.serverSide) return
    router.get(props.baseRoute, {
        ...props.filters,
        page,
        per_page: pageSize.value,
        sort: sortKey.value || null,
        dir: sortDir.value || null,
        search: search.value || null,
    }, { preserveState: true, preserveScroll: true, replace: true })
}

const sort = (key) => {
    if (key.sortable === false) return
    if (sortKey.value === key.key) {
        sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc'
    } else {
        sortKey.value = key.key
        sortDir.value = 'asc'
    }
    if (props.serverSide) {
        navigateToPage(1)
    }
}

const onSearch = () => {
    if (props.serverSide) {
        // Debounce: tunggu 300ms setelah user selesai mengetik
        if (searchTimeout) clearTimeout(searchTimeout)
        searchTimeout = setTimeout(() => {
            navigateToPage(1)
        }, 300)
    }
    // client-side: handled by computed (no debounce needed)
}

const goToPage = (page) => {
    if (props.serverSide) {
        navigateToPage(page)
    }
}

const displayPage = computed(() => props.serverSide ? props.currentPage : 1)

const pageRange = computed(() => {
    const total = totalPages.value
    const current = displayPage.value
    const range = []
    const start = Math.max(1, current - 2)
    const end = Math.min(total, current + 2)
    if (start > 1) range.push(1)
    if (start > 2) range.push('...')
    for (let i = start; i <= end; i++) range.push(i)
    if (end < total - 1) range.push('...')
    if (end < total) range.push(total)
    return range
})
</script>

<template>
    <div class="table-container">
        <!-- Toolbar: Search + Filters -->
        <div v-if="!hideSearch && (searchPlaceholder || $slots.toolbar)" class="px-6 py-4 border-b border-gray-100 dark:border-gray-800">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <!-- Search -->
                <div v-if="searchPlaceholder" class="relative flex-1 min-w-[200px] max-w-sm">
                    <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                    <input
                        v-model="search"
                        type="text"
                        :placeholder="searchPlaceholder"
                        class="form-input pl-10"
                        @input="onSearch"
                    />
                </div>
                <!-- Toolbar slot for filters & counts -->
                <div v-if="$slots.toolbar" class="flex items-center gap-3">
                    <slot name="toolbar" />
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-gray-100 dark:border-gray-800">
                        <th
                            v-for="col in columns"
                            :key="col.key"
                            :class="[
                                'px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider',
                                col.sortable !== false ? 'cursor-pointer select-none hover:text-gray-700 dark:hover:text-gray-200' : '',
                            ]"
                            @click="col.sortable !== false && sort(col)"
                        >
                            <div class="flex items-center gap-1">
                                {{ col.label }}
                                <span v-if="sortKey === col.key" class="inline-flex">
                                    <ChevronUpIcon v-if="sortDir === 'asc'" class="w-3 h-3" />
                                    <ChevronDownIcon v-else class="w-3 h-3" />
                                </span>
                            </div>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                    <tr
                        v-for="(row, i) in displayRows"
                        :key="row.id ?? i"
                        class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors cursor-pointer"
                        @click="emit('row-click', row)"
                    >
                        <td
                            v-for="col in columns"
                            :key="col.key"
                            class="px-6 py-3.5 text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap"
                        >
                            <slot :name="`cell-${col.key}`" :row="row" :value="row[col.key]">
                                {{ row[col.key] }}
                            </slot>
                        </td>
                    </tr>
                    <tr v-if="!displayRows.length">
                        <td :colspan="columns.length" class="px-6 py-12 text-center text-sm text-gray-400">
                            Tidak ada hasil.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div v-if="totalPages > 1" class="px-6 py-3 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between">
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Showing {{ ((displayPage - 1) * perPage) + 1 }} - {{ Math.min(displayPage * perPage, displayTotal) }} of {{ displayTotal }}
            </p>
            <div class="flex items-center gap-1">
                <button
                    class="px-3 py-1.5 text-sm rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 disabled:opacity-40 transition-colors"
                    :disabled="displayPage === 1"
                    @click="goToPage(displayPage - 1)"
                >
                    Prev
                </button>
                <template v-for="page in pageRange" :key="page">
                    <span v-if="page === '...'" class="px-2 text-gray-400">...</span>
                    <button
                        v-else
                        :class="[
                            'px-3 py-1.5 text-sm rounded-lg transition-colors',
                            page === displayPage
                                ? 'bg-primary-600 text-white'
                                : 'hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-600 dark:text-gray-400',
                        ]"
                        @click="goToPage(page)"
                    >
                        {{ page }}
                    </button>
                </template>
                <button
                    class="px-3 py-1.5 text-sm rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 disabled:opacity-40 transition-colors"
                    :disabled="displayPage === totalPages"
                    @click="goToPage(displayPage + 1)"
                >
                    Next
                </button>
            </div>
        </div>
    </div>
</template>
