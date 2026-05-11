<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import Table from '@/Components/Table.vue';
import { router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import { AuditOutlined, InfoCircleOutlined } from '@ant-design/icons-vue';
import { Modal } from 'ant-design-vue';
import { formatDateTime as formatDate } from '@/composables/useDateFormat';

interface User { id: string; name: string; email: string; }
interface Beneficiary { id: string; first_name: string; last_name: string; }
interface AuditLog {
    id: string;
    user_id: string | null;
    beneficiary_id: string | null;
    action: 'created' | 'updated' | 'deleted';
    model_type: string;
    model_id: string | null;
    old_values: Record<string, any> | null;
    new_values: Record<string, any> | null;
    ip_address: string | null;
    created_at: string;
    user?: User;
    beneficiary?: Beneficiary;
}

const props = defineProps<{
    logs: {
        data: AuditLog[];
        current_page: number;
        per_page: number;
        total: number;
    };
    modelTypes: string[];
    filters: { search?: string; action?: string; model_type?: string; date_from?: string; date_to?: string; };
}>();

const search     = ref(props.filters?.search ?? '');
const filterAction    = ref(props.filters?.action ?? undefined);
const filterModel     = ref(props.filters?.model_type ?? undefined);
const filterDateFrom  = ref(props.filters?.date_from ?? '');
const filterDateTo    = ref(props.filters?.date_to ?? '');

const selectedLog = ref<AuditLog | null>(null);
const detailVisible = ref(false);

const columns = [
    { title: 'Date & Time', key: 'created_at', width: 160 },
    { title: 'Action', key: 'action', width: 90 },
    { title: 'Model', key: 'model', width: 180 },
    { title: 'User', key: 'user', width: 160 },
    { title: 'Beneficiary', key: 'beneficiary', width: 160 },
    { title: 'IP Address', dataIndex: 'ip_address', key: 'ip_address', width: 130 },
    { title: 'Details', key: 'details', align: 'center' as const, width: 80 },
];

const applyFilters = () => {
    router.get(route('audit-logs.index'), {
        search: search.value,
        action: filterAction.value,
        model_type: filterModel.value,
        date_from: filterDateFrom.value,
        date_to: filterDateTo.value,
    }, { preserveState: true, replace: true });
};

let searchTimeout: ReturnType<typeof setTimeout>;
watch(search, () => { clearTimeout(searchTimeout); searchTimeout = setTimeout(applyFilters, 400); });
watch([filterAction, filterModel, filterDateFrom, filterDateTo], applyFilters);

const pagination = computed(() => ({
    current: props.logs.current_page,
    pageSize: props.logs.per_page,
    total: props.logs.total,
    showSizeChanger: true,
    pageSizeOptions: ['10', '25', '50', '100'],
}));

const handleTableChange = (pag: any) => {
    router.get(route('audit-logs.index'), {
        search: search.value,
        action: filterAction.value,
        model_type: filterModel.value,
        date_from: filterDateFrom.value,
        date_to: filterDateTo.value,
        page: pag.current,
        per_page: pag.pageSize,
    }, { preserveState: true, replace: true });
};

const actionColor: Record<string, string> = {
    created: 'green',
    updated: 'blue',
    deleted: 'red',
};

const shortModelType = (type: string) => type.split('\\').pop() ?? type;


const openDetail = (log: AuditLog) => {
    selectedLog.value = log;
    detailVisible.value = true;
};

const formatJson = (obj: Record<string, any> | null) => {
    if (!obj) return '—';
    return JSON.stringify(obj, null, 2);
};

// Compute diff between old and new values for updated records
const diffKeys = computed(() => {
    if (!selectedLog.value || selectedLog.value.action !== 'updated') return [];
    const oldV = selectedLog.value.old_values ?? {};
    const newV = selectedLog.value.new_values ?? {};
    return [...new Set([...Object.keys(oldV), ...Object.keys(newV)])];
});
</script>

<template>
    <AppLayout title="Audit Logs">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight flex items-center gap-2">
                <AuditOutlined /> Audit Logs
            </h2>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-4">

                    <!-- Filters -->
                    <div class="flex flex-wrap gap-2 mb-4">
                        <a-input-search
                            v-model:value="search"
                            placeholder="Search user, action, IP..."
                            style="width: 220px"
                            allow-clear
                        />
                        <a-select v-model:value="filterAction" placeholder="All actions" style="width: 140px" allow-clear>
                            <a-select-option value="created">Created</a-select-option>
                            <a-select-option value="updated">Updated</a-select-option>
                            <a-select-option value="deleted">Deleted</a-select-option>
                        </a-select>
                        <a-select v-model:value="filterModel" placeholder="All models" style="width: 170px" allow-clear>
                            <a-select-option v-for="m in modelTypes" :key="m" :value="m">
                                {{ shortModelType(m) }}
                            </a-select-option>
                        </a-select>
                        <a-input type="date" v-model:value="filterDateFrom" style="width: 145px" placeholder="Date from" />
                        <a-input type="date" v-model:value="filterDateTo"   style="width: 145px" placeholder="Date to" />
                        <span class="text-gray-400 text-xs self-center ml-auto">
                            {{ logs.total }} record(s) found
                        </span>
                    </div>

                    <Table
                        :columns="columns"
                        :pagination="pagination"
                        @change="handleTableChange"
                        :data-source="logs.data"
                        row-key="id"
                        size="small"
                    >
                        <template #bodyCell="{ column, record }">
                            <template v-if="column.key === 'created_at'">
                                <span class="text-xs">{{ formatDate(record.created_at) }}</span>
                            </template>
                            <template v-else-if="column.key === 'action'">
                                <a-tag :color="actionColor[record.action]" class="capitalize">
                                    {{ record.action }}
                                </a-tag>
                            </template>
                            <template v-else-if="column.key === 'model'">
                                <div class="font-medium">{{ shortModelType(record.model_type) }}</div>
                                <div class="text-gray-400 text-xs truncate max-w-[160px]" :title="record.model_id ?? ''">
                                    {{ record.model_id ?? '—' }}
                                </div>
                            </template>
                            <template v-else-if="column.key === 'user'">
                                <template v-if="record.user">
                                    <div>{{ record.user.name }}</div>
                                    <div class="text-gray-400 text-xs">{{ record.user.email }}</div>
                                </template>
                                <span v-else class="text-gray-400">System</span>
                            </template>
                            <template v-else-if="column.key === 'beneficiary'">
                                <template v-if="record.beneficiary">
                                    {{ record.beneficiary.last_name }}, {{ record.beneficiary.first_name }}
                                </template>
                                <span v-else class="text-gray-400">—</span>
                            </template>
                            <template v-else-if="column.key === 'details'">
                                <a-button size="small" @click="openDetail(record)">
                                    <InfoCircleOutlined />
                                </a-button>
                            </template>
                        </template>
                    </Table>
                </div>
            </div>
        </div>

        <!-- Detail Modal -->
        <a-modal
            v-model:open="detailVisible"
            title="Audit Log Detail"
            :footer="null"
            width="780px"
        >
            <template v-if="selectedLog">
                <a-descriptions bordered size="small" :column="2" class="mb-4">
                    <a-descriptions-item label="ID" :span="2">{{ selectedLog.id }}</a-descriptions-item>
                    <a-descriptions-item label="Action">
                        <a-tag :color="actionColor[selectedLog.action]" class="capitalize">{{ selectedLog.action }}</a-tag>
                    </a-descriptions-item>
                    <a-descriptions-item label="Date">{{ formatDate(selectedLog.created_at) }}</a-descriptions-item>
                    <a-descriptions-item label="Model Type">{{ selectedLog.model_type }}</a-descriptions-item>
                    <a-descriptions-item label="Model ID">{{ selectedLog.model_id ?? '—' }}</a-descriptions-item>
                    <a-descriptions-item label="User">
                        {{ selectedLog.user ? `${selectedLog.user.name} (${selectedLog.user.email})` : 'System' }}
                    </a-descriptions-item>
                    <a-descriptions-item label="IP Address">{{ selectedLog.ip_address ?? '—' }}</a-descriptions-item>
                    <a-descriptions-item label="Beneficiary">
                        {{ selectedLog.beneficiary
                            ? `${selectedLog.beneficiary.last_name}, ${selectedLog.beneficiary.first_name}`
                            : '—' }}
                    </a-descriptions-item>
                </a-descriptions>

                <!-- Diff view for updated records -->
                <template v-if="selectedLog.action === 'updated' && diffKeys.length">
                    <div class="font-semibold text-sm mb-2">Changes</div>
                    <a-table
                        :data-source="diffKeys.map(k => ({ key: k, field: k, old: selectedLog!.old_values?.[k], new: selectedLog!.new_values?.[k] }))"
                        :columns="[
                            { title: 'Field', dataIndex: 'field', key: 'field', width: 160 },
                            { title: 'Old Value', dataIndex: 'old', key: 'old' },
                            { title: 'New Value', dataIndex: 'new', key: 'new' },
                        ]"
                        size="small"
                        :pagination="false"
                        :row-class-name="(row: any) => JSON.stringify(row.old) !== JSON.stringify(row.new) ? 'bg-yellow-50 dark:bg-yellow-900/20' : ''"
                    >
                        <template #bodyCell="{ column, record: row }">
                            <template v-if="column.key === 'old'">
                                <span :class="JSON.stringify(row.old) !== JSON.stringify(row.new) ? 'text-red-500' : ''">
                                    {{ row.old !== undefined && row.old !== null ? JSON.stringify(row.old) : '—' }}
                                </span>
                            </template>
                            <template v-else-if="column.key === 'new'">
                                <span :class="JSON.stringify(row.old) !== JSON.stringify(row.new) ? 'text-green-600' : ''">
                                    {{ row.new !== undefined && row.new !== null ? JSON.stringify(row.new) : '—' }}
                                </span>
                            </template>
                        </template>
                    </a-table>
                </template>

                <!-- Raw JSON for created / deleted -->
                <template v-else>
                    <template v-if="selectedLog.old_values">
                        <div class="font-semibold text-sm mb-1">Old Values</div>
                        <pre class="bg-gray-100 dark:bg-gray-700 p-3 rounded text-xs overflow-auto max-h-48 mb-3">{{ formatJson(selectedLog.old_values) }}</pre>
                    </template>
                    <template v-if="selectedLog.new_values">
                        <div class="font-semibold text-sm mb-1">New Values</div>
                        <pre class="bg-gray-100 dark:bg-gray-700 p-3 rounded text-xs overflow-auto max-h-48">{{ formatJson(selectedLog.new_values) }}</pre>
                    </template>
                    <div v-if="!selectedLog.old_values && !selectedLog.new_values" class="text-gray-400 text-sm">No value data recorded.</div>
                </template>
            </template>
        </a-modal>
    </AppLayout>
</template>

