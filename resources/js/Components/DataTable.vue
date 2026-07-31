<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { ChevronUpIcon, ChevronDownIcon, MagnifyingGlassIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    columns: { type: Array, required: true },
    rows: { type: Array, required: true },
    searchPlaceholder: { type: String, default: 'Cari...' },
    hideSearch: { type: Boolean, default: false },
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

let searchTimeout = null

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
    if (props.serverSide) navigateToPage(1)
}

const onSearch = () => {
    if (props.serverSide) {
        if (searchTimeout) clearTimeout(searchTimeout)
        searchTimeout = setTimeout(() => {
            navigateToPage(1)
        }, 300)
    }
}

const goToPage = (page) => {
    if (props.serverSide) navigateToPage(page)
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
        <!-- Toolbar -->
        <div v-if="!hideSearch && (searchPlaceholder || $slots.toolbar)" class="px-5 py-4 border-b border-gray-100 dark:border-gray-800 bg-gray-50/30 dark:bg-gray-900/20">
            <div class="flex flex-wrap items-center justify-between gap-3">
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
                <div v-if="$slots.toolbar" class="flex items-center gap-3 flex-wrap">
                    <slot name="toolbar" />
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full table-sticky">
                <thead>
                    <tr class="border-b border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-900/30">
                        <th
                            v-for="col in columns"
                            :key="col.key"
                            :class="[
                                'px-5 py-3.5 text-[11px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider',
                                col.numeric ? 'text-right' : 'text-left',
                                col.sortable !== false ? 'cursor-pointer select-none hover:text-gray-700 dark:hover:text-gray-200' : '',
                            ]"
                            @click="col.sortable !== false && sort(col)"
                        >
                            <div :class="['flex items-center gap-1.5', col.numeric ? 'justify-end' : '']">
                                {{ col.label }}
                                <span v-if="sortKey === col.key" class="inline-flex text-primary-500">
                                    <ChevronUpIcon v-if="sortDir === 'asc'" class="w-3 h-3" />
                                    <ChevronDownIcon v-else class="w-3 h-3" />
                                </span>
                            </div>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-gray-800/50">
                    <tr
                        v-for="(row, i) in displayRows"
                        :key="row.id ?? i"
                        class="hover:bg-gray-50/80 dark:hover:bg-gray-800/30 transition-colors duration-150 cursor-pointer group"
                        @click="emit('row-click', row)"
                    >
                        <td
                            v-for="col in columns"
                            :key="col.key"
                            :class="[
                                'px-5 py-3.5 text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap',
                                col.numeric ? 'text-right nums' : '',
                            ]"
                        >
                            <slot :name="`cell-${col.key}`" :row="row" :value="row[col.key]">
                                {{ row[col.key] }}
                            </slot>
                        </td>
                    </tr>
                    <tr v-if="!displayRows.length">
                        <td :colspan="columns.length" class="px-5 py-16 text-center text-sm text-gray-400">
                            <div class="flex flex-col items-center gap-2">
                                <svg class="w-8 h-8 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                </svg>
                                Tidak ada hasil.
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div v-if="totalPages > 1" class="px-5 py-3.5 border-t border-gray-100 dark:border-gray-800 bg-gray-50/30 dark:bg-gray-900/20 flex items-center justify-between flex-wrap gap-3">
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Menampilkan
                <span class="font-semibold text-gray-700 dark:text-gray-300">{{ ((displayPage - 1) * perPage) + 1 }}</span>
                -
                <span class="font-semibold text-gray-700 dark:text-gray-300">{{ Math.min(displayPage * perPage, displayTotal) }}</span>
                dari
                <span class="font-semibold text-gray-700 dark:text-gray-300">{{ displayTotal }}</span>
            </p>
            <div class="flex items-center gap-1">                    <button
                        class="px-3 py-1.5 text-sm font-medium rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 disabled:opacity-40 disabled:cursor-not-allowed transition-colors text-gray-600 dark:text-gray-400"
                        :disabled="displayPage === 1"
                        aria-label="Halaman sebelumnya"
                        @click="goToPage(displayPage - 1)"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                    </button>
                <template v-for="page in pageRange" :key="page">
                    <span v-if="page === '...'" class="px-2 text-gray-400">...</span>
                    <button
                        v-else
                        :class="[
                            'min-w-[32px] h-8 text-sm font-medium rounded-lg transition-all duration-150',
                            page === displayPage
                                ? 'bg-primary-600 text-white shadow-sm'
                                : 'hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-600 dark:text-gray-400',
                        ]"
                        @click="goToPage(page)"
                    >
                        {{ page }}
                    </button>
                </template>                    <button
                        class="px-3 py-1.5 text-sm font-medium rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 disabled:opacity-40 disabled:cursor-not-allowed transition-colors text-gray-600 dark:text-gray-400"
                        :disabled="displayPage === totalPages"
                        aria-label="Halaman selanjutnya"
                        @click="goToPage(displayPage + 1)"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                    </button>
            </div>
        </div>
    </div>
</template>
