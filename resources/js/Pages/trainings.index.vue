<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import Table from '@/Components/Table.vue';
import ExportButtons from '@/Components/ExportButtons.vue';
import { router, useForm } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import { PlusOutlined, EditOutlined, DeleteOutlined, TeamOutlined, UserDeleteOutlined } from '@ant-design/icons-vue';
import { message, Modal } from 'ant-design-vue';
import { formatDate } from '@/composables/useDateFormat';
import { disabledFutureDate } from '@/composables/useDisabledFutureDate';
import { useAuthorization } from '@/composables/useAuthorization';
import axios from 'axios';

const { can } = useAuthorization();

interface Project {
    id: string;
    project_name: string;
}

interface Training {
    id: string;
    training_tile: string;
    training_type: string;
    facilitator: string;
    venue: string;
    date_conducted: string;
    duration_hours: number;
    project_id: string;
    project?: Project;
    beneficiaries_count?: number;
}

interface ParticipantItem {
    id: string;
    first_name: string;
    last_name: string;
    beneficiary_code: string;
    barangay?: string;
    date_attended?: string;
    completion_status?: string;
}

const props = defineProps<{
    trainings: {
        data: Training[];
        current_page: number;
        per_page: number;
        total: number;
    };
    projects: Project[];
    filters: { search?: string; project_id?: string };
}>();

const search = ref(props.filters?.search ?? '');
const filterProject = ref(props.filters?.project_id ?? undefined);
const showModal = ref(false);
const editing = ref<Training | null>(null);

// Participants Drawer state
const drawerVisible = ref(false);
const drawerTraining = ref<Training | null>(null);
const drawerParticipants = ref<ParticipantItem[]>([]);
const drawerLoading = ref(false);
const availableParticipants = ref<ParticipantItem[]>([]);
const addForm = useForm({ beneficiary_id: null as string | null, date_attended: '', completion_status: 'Incomplete' });
const showAddForm = ref(false);

const form = useForm({
    training_tile: '',
    training_type: '',
    facilitator: '',
    venue: '',
    date_conducted: '',
    duration_hours: '' as any,
    project_id: '' as string | null,
});

const columns = [
    { title: 'Title', dataIndex: 'training_tile', key: 'training_tile' },
    { title: 'Type', dataIndex: 'training_type', key: 'training_type', width: 120 },
    { title: 'Facilitator', dataIndex: 'facilitator', key: 'facilitator' },
    { title: 'Venue', dataIndex: 'venue', key: 'venue' },
    { title: 'Date', dataIndex: 'date_conducted', key: 'date_conducted', width: 120 },
    { title: 'Hours', dataIndex: 'duration_hours', key: 'duration_hours', width: 80, align: 'center' as const },
    { title: 'Project', key: 'project', width: 150 },
    { title: 'Participants', dataIndex: 'beneficiaries_count', key: 'beneficiaries_count', width: 100, align: 'center' as const },
    { title: 'Actions', key: 'action', align: 'center' as const, width: 130 },
];

const drawerColumns = [
    { title: 'Code', dataIndex: 'beneficiary_code', key: 'code', width: 100 },
    { title: 'Name', key: 'name' },
    { title: 'Barangay', dataIndex: 'barangay', key: 'barangay' },
    { title: 'Date Attended', dataIndex: 'date_attended', key: 'date_attended', width: 120 },
    { title: 'Status', dataIndex: 'completion_status', key: 'completion_status', width: 110 },
    { title: '', key: 'remove', width: 60, align: 'center' as const },
];

let searchTimeout: ReturnType<typeof setTimeout>;
watch(search, (val) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(route('trainings.index'), { search: val, project_id: filterProject.value }, { preserveState: true, replace: true });
    }, 400);
});

watch(filterProject, (val) => {
    router.get(route('trainings.index'), { search: search.value, project_id: val }, { preserveState: true, replace: true });
});

const pagination = computed(() => ({
    current: props.trainings.current_page,
    pageSize: props.trainings.per_page,
    total: props.trainings.total,
}));

const handleTableChange = (pag: any) => {
    router.get(route('trainings.index'), {
        search: search.value,
        project_id: filterProject.value,
        page: pag.current,
        per_page: pag.pageSize,
    }, { preserveState: true, replace: true });
};

const openCreate = () => {
    editing.value = null;
    form.reset();
    showModal.value = true;
};

const openEdit = (record: Training) => {
    editing.value = record;
    form.training_tile = record.training_tile;
    form.training_type = record.training_type ?? '';
    form.facilitator = record.facilitator ?? '';
    form.venue = record.venue ?? '';
    form.date_conducted = record.date_conducted ? record.date_conducted.substring(0, 10) : '';
    form.duration_hours = record.duration_hours ?? '';
    form.project_id = record.project_id ?? null;
    showModal.value = true;
};

const handleSubmit = () => {
    if (editing.value) {
        form.put(route('trainings.update', editing.value.id), {
            onSuccess: () => { showModal.value = false; message.success('Training updated!'); },
        });
    } else {
        form.post(route('trainings.store'), {
            onSuccess: () => { showModal.value = false; form.reset(); message.success('Training created!'); },
        });
    }
};

const handleDelete = (record: Training) => {
    Modal.confirm({
        title: 'Delete Training',
        content: `Are you sure you want to delete "${record.training_tile}"?`,
        okType: 'danger',
        onOk() {
            router.delete(route('trainings.destroy', record.id), {
                onSuccess: () => message.success('Training deleted!'),
            });
        },
    });
};

// Participants Drawer
const openParticipantsDrawer = async (record: Training) => {
    drawerTraining.value = record;
    drawerVisible.value = true;
    showAddForm.value = false;
    addForm.reset();
    addForm.completion_status = 'Incomplete';
    await loadParticipants();
};

const loadParticipants = async () => {
    if (!drawerTraining.value) return;
    drawerLoading.value = true;
    const res = await axios.get(route('trainings.participants.index', drawerTraining.value.id));
    drawerParticipants.value = res.data;
    drawerLoading.value = false;
};

const openAddForm = async () => {
    showAddForm.value = true;
    const res = await axios.get(route('trainings.participants.available', drawerTraining.value!.id));
    availableParticipants.value = res.data;
};

const submitAdd = () => {
    if (!drawerTraining.value) return;
    addForm.post(route('trainings.participants.store', drawerTraining.value.id), {
        onSuccess: () => {
            showAddForm.value = false;
            addForm.reset();
            addForm.completion_status = 'Incomplete';
            loadParticipants();
        },
    });
};

const removeParticipant = (beneficiaryId: string) => {
    if (!drawerTraining.value) return;
    Modal.confirm({
        title: 'Remove Participant',
        content: 'Remove this participant from the training?',
        okType: 'danger',
        onOk() {
            router.delete(route('trainings.participants.destroy', { training: drawerTraining.value!.id, beneficiary: beneficiaryId }), {
                onSuccess: () => loadParticipants(),
            });
        },
    });
};

const completionColors: Record<string, string> = { Completed: 'green', Incomplete: 'orange', Dropped: 'red' };
</script>

<template>
    <AppLayout title="Trainings">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Trainings</h2>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-4">
                    <div class="flex justify-between items-center mb-4">
                        <a-space>
                            <a-input-search
                                v-model:value="search"
                                placeholder="Search training..."
                                style="width: 250px"
                                allow-clear
                            />
                            <a-select
                                v-model:value="filterProject"
                                placeholder="Filter by project"
                                style="width: 220px"
                                allow-clear
                            >
                                <a-select-option v-for="p in projects" :key="p.id" :value="p.id">{{ p.project_name }}</a-select-option>
                            </a-select>
                        </a-space>
                        <a-space>
                            <ExportButtons
                                v-if="can('reports.export')"
                                :pdf-route="route('reports.trainings.pdf')"
                                :excel-route="route('reports.trainings.excel')"
                                :params="{ search, project_id: filterProject }"
                            />
                            <a-button v-if="can('trainings.manage')" type="primary" @click="openCreate">
                                <template #icon><PlusOutlined /></template>
                                Add Training
                            </a-button>
                        </a-space>
                    </div>

                    <Table
                        :columns="columns"
                        :pagination="pagination"
                        @change="handleTableChange"
                        :data-source="trainings.data"
                        row-key="id"
                    >
                        <template #bodyCell="{ column, record }">
                            <template v-if="column.key === 'date_conducted'">
                                {{ formatDate(record.date_conducted) }}
                            </template>
                            <template v-else-if="column.key === 'project'">
                                {{ record.project?.project_name ?? '-' }}
                            </template>
                            <template v-else-if="column.key === 'action'">
                                <a-space>
                                    <a-tooltip title="Manage Participants">
                                        <a-button size="small" @click="openParticipantsDrawer(record)"><TeamOutlined /></a-button>
                                    </a-tooltip>
                                    <a-button v-if="can('trainings.manage')" size="small" @click="openEdit(record)"><EditOutlined /></a-button>
                                    <a-button v-if="can('trainings.manage')" size="small" danger @click="handleDelete(record)"><DeleteOutlined /></a-button>
                                </a-space>
                            </template>
                        </template>
                    </Table>
                </div>
            </div>
        </div>

        <!-- Training Create/Edit Modal -->
        <a-modal
            v-model:open="showModal"
            :title="editing ? 'Edit Training' : 'Add Training'"
            :confirm-loading="form.processing"
            @ok="handleSubmit"
            width="650px"
            ok-text="Save"
        >
            <a-form layout="vertical" class="mt-2">
                <div class="grid grid-cols-2 gap-x-4">
                    <a-form-item label="Training Title" class="col-span-2" required>
                        <a-input v-model:value="form.training_tile" />
                        <div class="text-red-500 text-xs" v-if="form.errors.training_tile">{{ form.errors.training_tile }}</div>
                    </a-form-item>
                    <a-form-item label="Training Type">
                        <a-input v-model:value="form.training_type" />
                    </a-form-item>
                    <a-form-item label="Facilitator">
                        <a-input v-model:value="form.facilitator" />
                    </a-form-item>
                    <a-form-item label="Venue">
                        <a-input v-model:value="form.venue" />
                    </a-form-item>
                    <a-form-item label="Date Conducted" required>
                        <a-date-picker
                            v-model:value="form.date_conducted"
                            value-format="YYYY-MM-DD"
                            format="MMM D, YYYY"
                            class="w-full"
                            placeholder="Select date conducted"
                            :disabled-date="disabledFutureDate"
                        />
                        <div class="text-red-500 text-xs" v-if="form.errors.date_conducted">{{ form.errors.date_conducted }}</div>
                    </a-form-item>
                    <a-form-item label="Duration (Hours)">
                        <a-input-number v-model:value="form.duration_hours" :min="0" class="w-full" />
                    </a-form-item>
                    <a-form-item label="Project">
                        <a-select v-model:value="form.project_id" placeholder="Select project" allow-clear>
                            <a-select-option v-for="p in projects" :key="p.id" :value="p.id">{{ p.project_name }}</a-select-option>
                        </a-select>
                    </a-form-item>
                </div>
            </a-form>
        </a-modal>

        <!-- Participants Drawer -->
        <a-drawer
            v-model:open="drawerVisible"
            :title="`Participants — ${drawerTraining?.training_tile ?? ''}`"
            placement="right"
            width="700"
            destroy-on-close
        >
            <div v-if="can('trainings.manage')" class="mb-3 flex justify-end">
                <a-button type="primary" size="small" @click="openAddForm" v-if="!showAddForm">
                    <template #icon><PlusOutlined /></template>
                    Add Participant
                </a-button>
            </div>

            <!-- Add form -->
            <a-card v-if="can('trainings.manage') && showAddForm" class="mb-4" size="small" title="Add a Participant">
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
                                    v-for="b in availableParticipants"
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
                        <a-form-item label="Date Attended">
                            <a-date-picker
                                v-model:value="addForm.date_attended"
                                value-format="YYYY-MM-DD"
                                format="MMM D, YYYY"
                                class="w-full"
                                allow-clear
                                placeholder="Select date attended"
                                :disabled-date="disabledFutureDate"
                            />
                        </a-form-item>
                        <a-form-item label="Completion Status" required>
                            <a-select v-model:value="addForm.completion_status" style="width: 100%">
                                <a-select-option value="Incomplete">Incomplete</a-select-option>
                                <a-select-option value="Completed">Completed</a-select-option>
                                <a-select-option value="Dropped">Dropped</a-select-option>
                            </a-select>
                        </a-form-item>
                    </div>
                    <div class="flex gap-2 justify-end">
                        <a-button @click="showAddForm = false">Cancel</a-button>
                        <a-button type="primary" :loading="addForm.processing" @click="submitAdd">Add</a-button>
                    </div>
                </a-form>
            </a-card>

            <a-spin :spinning="drawerLoading">
                <a-table
                    :data-source="drawerParticipants"
                    :columns="drawerColumns"
                    :pagination="false"
                    row-key="id"
                    size="small"
                >
                    <template #bodyCell="{ column, record }">
                        <template v-if="column.key === 'name'">
                            <div>{{ record.last_name }}, {{ record.first_name }}</div>
                        </template>
                        <template v-else-if="column.key === 'date_attended'">
                            {{ formatDate(record.date_attended) }}
                        </template>
                        <template v-else-if="column.key === 'completion_status'">
                            <a-tag :color="completionColors[record.completion_status] ?? 'default'">{{ record.completion_status }}</a-tag>
                        </template>
                        <template v-else-if="column.key === 'remove'">
                            <a-tooltip v-if="can('trainings.manage')" title="Remove">
                                <a-button size="small" danger @click="removeParticipant(record.id)">
                                    <template #icon><UserDeleteOutlined /></template>
                                </a-button>
                            </a-tooltip>
                        </template>
                    </template>
                </a-table>
                <a-empty v-if="!drawerLoading && drawerParticipants.length === 0" description="No participants added yet." class="mt-6" />
            </a-spin>
        </a-drawer>
    </AppLayout>
</template>

