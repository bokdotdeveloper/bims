<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import Table from '@/Components/Table.vue';
import ExportButtons from '@/Components/ExportButtons.vue';
import { router, useForm } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import { PlusOutlined, EditOutlined, DeleteOutlined, UserOutlined, TeamOutlined } from '@ant-design/icons-vue';
import { message, Modal } from 'ant-design-vue';
import { formatDate } from '@/composables/useDateFormat';

interface Project { id: string; project_name: string; }
interface Beneficiary { id: string; first_name: string; last_name: string; beneficiary_code: string; }
interface BeneficiaryGroup { id: number; group_name: string; group_type: string; total_members: number; }
interface AssistanceRecord {
    id: string;
    recipient_type: 'individual' | 'group';
    beneficiary_id: string | null;
    beneficiary_group_id: number | null;
    project_id: string | null;
    assistance_type: string;
    amount: number | null;
    date_released: string;
    released_by: string;
    remarks: string;
    beneficiary?: Beneficiary;
    beneficiary_group?: BeneficiaryGroup;
    project?: Project;
}

const props = defineProps<{
    records: {
        data: AssistanceRecord[];
        current_page: number;
        per_page: number;
        total: number;
    };
    projects: Project[];
    beneficiaries: Beneficiary[];
    groups: BeneficiaryGroup[];
    filters: { search?: string; project_id?: string; recipient_type?: string };
}>();

const search = ref(props.filters?.search ?? '');
const filterProject = ref(props.filters?.project_id ?? undefined);
const filterType = ref(props.filters?.recipient_type ?? undefined);
const showModal = ref(false);
const editing = ref<AssistanceRecord | null>(null);

const form = useForm({
    recipient_type: 'individual' as 'individual' | 'group',
    beneficiary_id: null as string | null,
    beneficiary_group_id: null as number | null,
    project_id: null as string | null,
    assistance_type: '',
    amount: '' as any,
    date_released: '',
    released_by: '',
    remarks: '',
});

const columns = [
    { title: 'Type', key: 'recipient_type', width: 90 },
    { title: 'Recipient', key: 'recipient' },
    { title: 'Assistance Type', dataIndex: 'assistance_type', key: 'assistance_type', width: 150 },
    { title: 'Amount', dataIndex: 'amount', key: 'amount', width: 130, align: 'right' as const },
    { title: 'Date Released', dataIndex: 'date_released', key: 'date_released', width: 130 },
    { title: 'Released By', dataIndex: 'released_by', key: 'released_by' },
    { title: 'Project', key: 'project' },
    { title: 'Actions', key: 'action', align: 'center' as const, width: 100 },
];

const applyFilters = () => {
    router.get(route('assistance-records.index'), {
        search: search.value,
        project_id: filterProject.value,
        recipient_type: filterType.value,
    }, { preserveState: true, replace: true });
};

let searchTimeout: ReturnType<typeof setTimeout>;
watch(search, () => { clearTimeout(searchTimeout); searchTimeout = setTimeout(applyFilters, 400); });
watch(filterProject, applyFilters);
watch(filterType, applyFilters);

const pagination = computed(() => ({
    current: props.records.current_page,
    pageSize: props.records.per_page,
    total: props.records.total,
}));

const handleTableChange = (pag: any) => {
    router.get(route('assistance-records.index'), {
        search: search.value,
        project_id: filterProject.value,
        recipient_type: filterType.value,
        page: pag.current,
        per_page: pag.pageSize,
    }, { preserveState: true, replace: true });
};

const openCreate = () => {
    editing.value = null;
    form.reset();
    form.recipient_type = 'individual';
    showModal.value = true;
};

const openEdit = (record: AssistanceRecord) => {
    editing.value = record;
    form.recipient_type = record.recipient_type;
    form.beneficiary_id = record.beneficiary_id;
    form.beneficiary_group_id = record.beneficiary_group_id;
    form.project_id = record.project_id;
    form.assistance_type = record.assistance_type;
    form.amount = record.amount ?? '';
    form.date_released = record.date_released ? record.date_released.substring(0, 10) : '';
    form.released_by = record.released_by ?? '';
    form.remarks = record.remarks ?? '';
    showModal.value = true;
};

const handleSubmit = () => {
    if (editing.value) {
        form.put(route('assistance-records.update', editing.value.id), {
            onSuccess: () => { showModal.value = false; message.success('Record updated!'); },
        });
    } else {
        form.post(route('assistance-records.store'), {
            onSuccess: () => { showModal.value = false; form.reset(); form.recipient_type = 'individual'; message.success('Record created!'); },
        });
    }
};

const handleDelete = (record: AssistanceRecord) => {
    Modal.confirm({
        title: 'Delete Assistance Record',
        content: 'Are you sure you want to delete this record?',
        okType: 'danger',
        onOk() {
            router.delete(route('assistance-records.destroy', record.id), {
                onSuccess: () => message.success('Record deleted!'),
            });
        },
    });
};

const formatAmount = (amount: number | null) => {
    if (!amount) return '-';
    return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(amount);
};

const onRecipientTypeChange = () => {
    form.beneficiary_id = null;
    form.beneficiary_group_id = null;
};
</script>

<template>
    <AppLayout title="Assistance Records">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Assistance Records</h2>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-4">
                    <div class="flex justify-between items-center mb-4 flex-wrap gap-2">
                        <a-space wrap>
                            <a-input-search
                                v-model:value="search"
                                placeholder="Search by recipient..."
                                style="width: 230px"
                                allow-clear
                            />
                            <a-select v-model:value="filterType" placeholder="All types" style="width: 160px" allow-clear>
                                <a-select-option value="individual"><UserOutlined /> Individual</a-select-option>
                                <a-select-option value="group"><TeamOutlined /> Group</a-select-option>
                            </a-select>
                            <a-select v-model:value="filterProject" placeholder="Filter by project" style="width: 200px" allow-clear>
                                <a-select-option v-for="p in projects" :key="p.id" :value="p.id">{{ p.project_name }}</a-select-option>
                            </a-select>
                        </a-space>
                        <a-space>
                            <ExportButtons
                                :pdf-route="route('reports.assistance.pdf')"
                                :excel-route="route('reports.assistance.excel')"
                                :params="{ search, project_id: filterProject, recipient_type: filterType }"
                            />
                            <a-button type="primary" @click="openCreate">
                                <template #icon><PlusOutlined /></template>
                                Add Record
                            </a-button>
                        </a-space>
                    </div>

                    <Table
                        :columns="columns"
                        :pagination="pagination"
                        @change="handleTableChange"
                        :data-source="records.data"
                        row-key="id"
                    >
                        <template #bodyCell="{ column, record }">
                            <template v-if="column.key === 'recipient_type'">
                                <a-tag :color="record.recipient_type === 'individual' ? 'blue' : 'purple'">
                                    <UserOutlined v-if="record.recipient_type === 'individual'" />
                                    <TeamOutlined v-else />
                                    {{ record.recipient_type === 'individual' ? 'Individual' : 'Group' }}
                                </a-tag>
                            </template>
                            <template v-else-if="column.key === 'recipient'">
                                <template v-if="record.recipient_type === 'individual' && record.beneficiary">
                                    <div>{{ record.beneficiary.last_name }}, {{ record.beneficiary.first_name }}</div>
                                    <div class="text-gray-400 text-xs">{{ record.beneficiary.beneficiary_code }}</div>
                                </template>
                                <template v-else-if="record.recipient_type === 'group' && record.beneficiary_group">
                                    <div>{{ record.beneficiary_group.group_name }}</div>
                                    <div class="text-gray-400 text-xs">{{ record.beneficiary_group.group_type }} · {{ record.beneficiary_group.total_members }} members</div>
                                </template>
                                <template v-else>—</template>
                            </template>
                            <template v-else-if="column.key === 'project'">
                                {{ record.project?.project_name ?? '-' }}
                            </template>
                            <template v-else-if="column.key === 'date_released'">
                                {{ formatDate(record.date_released) }}
                            </template>
                            <template v-else-if="column.key === 'amount'">
                                {{ formatAmount(record.amount) }}
                            </template>
                            <template v-else-if="column.key === 'action'">
                                <a-space>
                                    <a-button size="small" @click="openEdit(record)"><EditOutlined /></a-button>
                                    <a-button size="small" danger @click="handleDelete(record)"><DeleteOutlined /></a-button>
                                </a-space>
                            </template>
                        </template>
                    </Table>
                </div>
            </div>
        </div>

        <a-modal
            v-model:open="showModal"
            :title="editing ? 'Edit Assistance Record' : 'Add Assistance Record'"
            :confirm-loading="form.processing"
            @ok="handleSubmit"
            width="680px"
            ok-text="Save"
        >
            <a-form layout="vertical" class="mt-2">
                <div class="grid grid-cols-2 gap-x-4">

                    <!-- Recipient Type Toggle -->
                    <a-form-item label="Recipient Type" class="col-span-2" required>
                        <a-radio-group v-model:value="form.recipient_type" button-style="solid" @change="onRecipientTypeChange">
                            <a-radio-button value="individual">
                                <UserOutlined /> Individual Beneficiary
                            </a-radio-button>
                            <a-radio-button value="group">
                                <TeamOutlined /> Beneficiary Group
                            </a-radio-button>
                        </a-radio-group>
                    </a-form-item>

                    <!-- Individual Selector -->
                    <a-form-item v-if="form.recipient_type === 'individual'" label="Beneficiary" class="col-span-2" required>
                        <a-select
                            v-model:value="form.beneficiary_id"
                            placeholder="Select beneficiary"
                            show-search
                            :filter-option="(input: string, option: any) => option.label?.toLowerCase().includes(input.toLowerCase())"
                            class="w-full"
                        >
                            <a-select-option
                                v-for="b in beneficiaries"
                                :key="b.id"
                                :value="b.id"
                                :label="`${b.last_name}, ${b.first_name} (${b.beneficiary_code})`"
                            >
                                {{ b.last_name }}, {{ b.first_name }}
                                <span class="text-gray-400 ml-1">{{ b.beneficiary_code }}</span>
                            </a-select-option>
                        </a-select>
                        <div class="text-red-500 text-xs" v-if="form.errors.beneficiary_id">{{ form.errors.beneficiary_id }}</div>
                    </a-form-item>

                    <!-- Group Selector -->
                    <a-form-item v-if="form.recipient_type === 'group'" label="Beneficiary Group" class="col-span-2" required>
                        <a-select
                            v-model:value="form.beneficiary_group_id"
                            placeholder="Select group"
                            show-search
                            :filter-option="(input: string, option: any) => option.label?.toLowerCase().includes(input.toLowerCase())"
                            class="w-full"
                        >
                            <a-select-option
                                v-for="g in groups"
                                :key="g.id"
                                :value="g.id"
                                :label="g.group_name"
                            >
                                {{ g.group_name }}
                                <span class="text-gray-400 ml-1">{{ g.group_type }} · {{ g.total_members }} members</span>
                            </a-select-option>
                        </a-select>
                        <div class="text-red-500 text-xs" v-if="form.errors.beneficiary_group_id">{{ form.errors.beneficiary_group_id }}</div>
                    </a-form-item>

                    <a-form-item label="Assistance Type" required>
                        <a-input v-model:value="form.assistance_type" />
                        <div class="text-red-500 text-xs" v-if="form.errors.assistance_type">{{ form.errors.assistance_type }}</div>
                    </a-form-item>
                    <a-form-item label="Amount">
                        <a-input-number v-model:value="form.amount" :min="0" :precision="2" class="w-full" />
                    </a-form-item>
                    <a-form-item label="Date Released" required>
                        <a-input type="date" v-model:value="form.date_released" class="w-full" />
                        <div class="text-red-500 text-xs" v-if="form.errors.date_released">{{ form.errors.date_released }}</div>
                    </a-form-item>
                    <a-form-item label="Released By">
                        <a-input v-model:value="form.released_by" />
                    </a-form-item>
                    <a-form-item label="Project">
                        <a-select v-model:value="form.project_id" placeholder="Select project" allow-clear>
                            <a-select-option v-for="p in projects" :key="p.id" :value="p.id">{{ p.project_name }}</a-select-option>
                        </a-select>
                    </a-form-item>
                    <a-form-item label="Remarks" class="col-span-2">
                        <a-textarea v-model:value="form.remarks" :rows="3" />
                    </a-form-item>
                </div>
            </a-form>
        </a-modal>
    </AppLayout>
</template>

