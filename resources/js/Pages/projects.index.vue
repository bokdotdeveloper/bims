<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import Table from '@/Components/Table.vue';
import ExportButtons from '@/Components/ExportButtons.vue';
import { router, useForm } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import { PlusOutlined, EditOutlined, DeleteOutlined, TeamOutlined, UserDeleteOutlined } from '@ant-design/icons-vue';
import { message, Modal } from 'ant-design-vue';
import { formatDate } from '@/composables/useDateFormat';
import axios from 'axios';

interface Project {
    id: string;
    project_name: string;
    project_code: string;
    description: string;
    date_started: string;
    date_ended: string;
    fund_source: string;
    is_active: boolean;
    beneficiaries_count?: number;
}

interface BeneficiaryItem {
    id: string;
    first_name: string;
    last_name: string;
    beneficiary_code: string;
    barangay?: string;
    date_enrolled?: string;
    status?: string;
}

const props = defineProps<{
    projects: {
        data: Project[];
        current_page: number;
        per_page: number;
        total: number;
    };
    filters: { search?: string };
}>();

const search = ref(props.filters?.search ?? '');
const showModal = ref(false);
const editing = ref<Project | null>(null);

// Beneficiaries Drawer state
const drawerVisible = ref(false);
const drawerProject = ref<Project | null>(null);
const drawerMembers = ref<BeneficiaryItem[]>([]);
const drawerLoading = ref(false);
const availableBeneficiaries = ref<BeneficiaryItem[]>([]);
const addForm = useForm({ beneficiary_id: null as string | null, date_enrolled: '', status: 'Active', remarks: '' });
const showAddForm = ref(false);

const form = useForm({
    project_name: '',
    project_code: '',
    description: '',
    date_started: '',
    date_ended: '',
    fund_source: '',
    is_active: true,
});

const columns = [
    { title: 'Code', dataIndex: 'project_code', key: 'project_code', width: 120 },
    { title: 'Project Name', dataIndex: 'project_name', key: 'project_name' },
    { title: 'Fund Source', dataIndex: 'fund_source', key: 'fund_source' },
    { title: 'Date Started', dataIndex: 'date_started', key: 'date_started', width: 130 },
    { title: 'Date Ended', dataIndex: 'date_ended', key: 'date_ended', width: 130 },
    { title: 'Beneficiaries', dataIndex: 'beneficiaries_count', key: 'beneficiaries_count', width: 120, align: 'center' as const },
    { title: 'Status', dataIndex: 'is_active', key: 'is_active', width: 90 },
    { title: 'Actions', key: 'action', align: 'center' as const, width: 130 },
];

const drawerColumns = [
    { title: 'Code', dataIndex: 'beneficiary_code', key: 'code', width: 100 },
    { title: 'Name', key: 'name' },
    { title: 'Barangay', dataIndex: 'barangay', key: 'barangay' },
    { title: 'Enrolled', dataIndex: 'date_enrolled', key: 'date_enrolled', width: 110 },
    { title: 'Status', dataIndex: 'status', key: 'status', width: 100 },
    { title: '', key: 'remove', width: 60, align: 'center' as const },
];

let searchTimeout: ReturnType<typeof setTimeout>;
watch(search, (val) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(route('projects.index'), { search: val }, { preserveState: true, replace: true });
    }, 400);
});

const pagination = computed(() => ({
    current: props.projects.current_page,
    pageSize: props.projects.per_page,
    total: props.projects.total,
}));

const handleTableChange = (pag: any) => {
    router.get(route('projects.index'), {
        search: search.value,
        page: pag.current,
        per_page: pag.pageSize,
    }, { preserveState: true, replace: true });
};

const openCreate = () => {
    editing.value = null;
    form.reset();
    form.is_active = true;
    showModal.value = true;
};

const openEdit = (record: Project) => {
    editing.value = record;
    form.project_name = record.project_name;
    form.project_code = record.project_code;
    form.description = record.description ?? '';
    form.date_started = record.date_started ? record.date_started.substring(0, 10) : '';
    form.date_ended = record.date_ended ? record.date_ended.substring(0, 10) : '';
    form.fund_source = record.fund_source ?? '';
    form.is_active = record.is_active;
    showModal.value = true;
};

const handleSubmit = () => {
    if (editing.value) {
        form.put(route('projects.update', editing.value.id), {
            onSuccess: () => { showModal.value = false; message.success('Project updated!'); },
        });
    } else {
        form.post(route('projects.store'), {
            onSuccess: () => { showModal.value = false; form.reset(); message.success('Project created!'); },
        });
    }
};

const handleDelete = (record: Project) => {
    Modal.confirm({
        title: 'Delete Project',
        content: `Are you sure you want to delete "${record.project_name}"?`,
        okType: 'danger',
        onOk() {
            router.delete(route('projects.destroy', record.id), {
                onSuccess: () => message.success('Project deleted!'),
            });
        },
    });
};

// Beneficiaries Drawer
const openBeneficiariesDrawer = async (record: Project) => {
    drawerProject.value = record;
    drawerVisible.value = true;
    showAddForm.value = false;
    addForm.reset();
    await loadDrawerMembers();
};

const loadDrawerMembers = async () => {
    if (!drawerProject.value) return;
    drawerLoading.value = true;
    const res = await axios.get(route('projects.beneficiaries.index', drawerProject.value.id));
    drawerMembers.value = res.data;
    drawerLoading.value = false;
};

const loadAvailableBeneficiaries = async () => {
    if (!drawerProject.value) return;
    const res = await axios.get(route('projects.beneficiaries.available', drawerProject.value.id));
    availableBeneficiaries.value = res.data;
};

const openAddForm = async () => {
    showAddForm.value = true;
    await loadAvailableBeneficiaries();
};

const submitEnroll = () => {
    if (!drawerProject.value) return;
    addForm.post(route('projects.beneficiaries.store', drawerProject.value.id), {
        onSuccess: () => {
            showAddForm.value = false;
            addForm.reset();
            addForm.status = 'Active';
            loadDrawerMembers();
        },
    });
};

const removeBeneficiary = (beneficiaryId: string) => {
    if (!drawerProject.value) return;
    Modal.confirm({
        title: 'Remove Beneficiary',
        content: 'Remove this beneficiary from the project?',
        okType: 'danger',
        onOk() {
            router.delete(route('projects.beneficiaries.destroy', { project: drawerProject.value!.id, beneficiary: beneficiaryId }), {
                onSuccess: () => loadDrawerMembers(),
            });
        },
    });
};

const statusColors: Record<string, string> = { Active: 'green', Completed: 'blue', Dropped: 'red', Transferred: 'orange' };
</script>

<template>
    <AppLayout title="Projects">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Projects</h2>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-4">
                    <div class="flex justify-between items-center mb-4">
                        <a-input-search v-model:value="search" placeholder="Search projects..." style="width: 300px" allow-clear />
                        <a-space>
                            <ExportButtons
                                :pdf-route="route('reports.projects.pdf')"
                                :excel-route="route('reports.projects.excel')"
                                :params="{ search }"
                            />
                            <a-button type="primary" @click="openCreate">
                                <template #icon><PlusOutlined /></template>
                                Add Project
                            </a-button>
                        </a-space>
                    </div>

                    <Table
                        :columns="columns"
                        :pagination="pagination"
                        @change="handleTableChange"
                        :data-source="projects.data"
                        row-key="id"
                    >
                        <template #bodyCell="{ column, record }">
                            <template v-if="column.key === 'date_started'">
                                {{ formatDate(record.date_started) }}
                            </template>
                            <template v-else-if="column.key === 'date_ended'">
                                {{ formatDate(record.date_ended) || '—' }}
                            </template>
                            <template v-else-if="column.key === 'is_active'">
                                <a-tag :color="record.is_active ? 'green' : 'red'">
                                    {{ record.is_active ? 'Active' : 'Inactive' }}
                                </a-tag>
                            </template>
                            <template v-else-if="column.key === 'action'">
                                <a-space>
                                    <a-tooltip title="Manage Beneficiaries">
                                        <a-button size="small" @click="openBeneficiariesDrawer(record)"><TeamOutlined /></a-button>
                                    </a-tooltip>
                                    <a-button size="small" @click="openEdit(record)"><EditOutlined /></a-button>
                                    <a-button size="small" danger @click="handleDelete(record)"><DeleteOutlined /></a-button>
                                </a-space>
                            </template>
                        </template>
                    </Table>
                </div>
            </div>
        </div>

        <!-- Project Create/Edit Modal -->
        <a-modal
            v-model:open="showModal"
            :title="editing ? 'Edit Project' : 'Add Project'"
            :confirm-loading="form.processing"
            @ok="handleSubmit"
            width="650px"
            ok-text="Save"
        >
            <a-form layout="vertical" class="mt-2">
                <div class="grid grid-cols-2 gap-x-4">
                    <a-form-item label="Project Name" class="col-span-2" required>
                        <a-input v-model:value="form.project_name" />
                        <div class="text-red-500 text-xs" v-if="form.errors.project_name">{{ form.errors.project_name }}</div>
                    </a-form-item>
                    <a-form-item label="Project Code" required>
                        <a-input v-model:value="form.project_code" />
                        <div class="text-red-500 text-xs" v-if="form.errors.project_code">{{ form.errors.project_code }}</div>
                    </a-form-item>
                    <a-form-item label="Fund Source">
                        <a-input v-model:value="form.fund_source" />
                    </a-form-item>
                    <a-form-item label="Date Started" required>
                        <a-input type="date" v-model:value="form.date_started" class="w-full" />
                        <div class="text-red-500 text-xs" v-if="form.errors.date_started">{{ form.errors.date_started }}</div>
                    </a-form-item>
                    <a-form-item label="Date Ended">
                        <a-input type="date" v-model:value="form.date_ended" class="w-full" />
                    </a-form-item>
                    <a-form-item label="Description" class="col-span-2">
                        <a-textarea v-model:value="form.description" :rows="3" />
                    </a-form-item>
                    <a-form-item label="Status">
                        <a-switch v-model:checked="form.is_active" checked-children="Active" un-checked-children="Inactive" />
                    </a-form-item>
                </div>
            </a-form>
        </a-modal>

        <!-- Beneficiaries Drawer -->
        <a-drawer
            v-model:open="drawerVisible"
            :title="`Beneficiaries — ${drawerProject?.project_name ?? ''}`"
            placement="right"
            width="700"
            destroy-on-close
        >
            <div class="mb-3 flex justify-end">
                <a-button type="primary" size="small" @click="openAddForm" v-if="!showAddForm">
                    <template #icon><PlusOutlined /></template>
                    Enroll Beneficiary
                </a-button>
            </div>

            <!-- Add form -->
            <a-card v-if="showAddForm" class="mb-4" size="small" title="Enroll a Beneficiary">
                <a-form layout="vertical">
                    <div class="grid grid-cols-2 gap-x-4">
                        <a-form-item label="Beneficiary" class="col-span-2" required>
                            <a-select
                                v-model:value="addForm.beneficiary_id"
                                show-search
                                :filter-option="(input: string, option: any) => option.label?.toLowerCase().includes(input.toLowerCase())"
                                placeholder="Search beneficiary..."
                                style="width: 100%"
                            >
                                <a-select-option
                                    v-for="b in availableBeneficiaries"
                                    :key="b.id"
                                    :value="b.id"
                                    :label="`${b.last_name}, ${b.first_name} (${b.beneficiary_code})`"
                                >
                                    {{ b.last_name }}, {{ b.first_name }}
                                    <span class="text-gray-400 text-xs ml-1">{{ b.beneficiary_code }}</span>
                                </a-select-option>
                            </a-select>
                            <div class="text-red-500 text-xs" v-if="addForm.errors.beneficiary_id">{{ addForm.errors.beneficiary_id }}</div>
                        </a-form-item>
                        <a-form-item label="Date Enrolled" required>
                            <a-input type="date" v-model:value="addForm.date_enrolled" class="w-full" />
                        </a-form-item>
                        <a-form-item label="Status" required>
                            <a-select v-model:value="addForm.status" style="width: 100%">
                                <a-select-option value="Active">Active</a-select-option>
                                <a-select-option value="Completed">Completed</a-select-option>
                                <a-select-option value="Dropped">Dropped</a-select-option>
                                <a-select-option value="Transferred">Transferred</a-select-option>
                            </a-select>
                        </a-form-item>
                        <a-form-item label="Remarks" class="col-span-2">
                            <a-textarea v-model:value="addForm.remarks" :rows="2" />
                        </a-form-item>
                    </div>
                    <div class="flex gap-2 justify-end">
                        <a-button @click="showAddForm = false">Cancel</a-button>
                        <a-button type="primary" :loading="addForm.processing" @click="submitEnroll">Enroll</a-button>
                    </div>
                </a-form>
            </a-card>

            <a-spin :spinning="drawerLoading">
                <a-table
                    :data-source="drawerMembers"
                    :columns="drawerColumns"
                    :pagination="false"
                    row-key="id"
                    size="small"
                >
                    <template #bodyCell="{ column, record }">
                        <template v-if="column.key === 'name'">
                            <div>{{ record.last_name }}, {{ record.first_name }}</div>
                        </template>
                        <template v-else-if="column.key === 'date_enrolled'">
                            {{ formatDate(record.date_enrolled) }}
                        </template>
                        <template v-else-if="column.key === 'status'">
                            <a-tag :color="statusColors[record.status] ?? 'default'">{{ record.status }}</a-tag>
                        </template>
                        <template v-else-if="column.key === 'remove'">
                            <a-tooltip title="Remove">
                                <a-button size="small" danger @click="removeBeneficiary(record.id)">
                                    <template #icon><UserDeleteOutlined /></template>
                                </a-button>
                            </a-tooltip>
                        </template>
                    </template>
                </a-table>
                <a-empty v-if="!drawerLoading && drawerMembers.length === 0" description="No beneficiaries enrolled yet." class="mt-6" />
            </a-spin>
        </a-drawer>
    </AppLayout>
</template>

