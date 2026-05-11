<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import Table from '@/Components/Table.vue';
import ExportButtons from '@/Components/ExportButtons.vue';
import { router, useForm } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import { PlusOutlined, EditOutlined, DeleteOutlined, TeamOutlined, UserDeleteOutlined, ManOutlined, WomanOutlined } from '@ant-design/icons-vue';
import { message, Modal } from 'ant-design-vue';
import { formatDate } from '@/composables/useDateFormat';
import axios from 'axios';

interface BeneficiaryGroup {
    id: number;
    group_name: string;
    group_type: string;
    total_members: number;
    male_members: number;
    female_members: number;
    date_organized: string;
    members_count?: number;
}

interface MemberItem {
    id: string;
    first_name: string;
    last_name: string;
    beneficiary_code: string;
    barangay?: string;
    sex?: string;
    date_joined?: string;
}

const props = defineProps<{
    groups: {
        data: BeneficiaryGroup[];
        current_page: number;
        per_page: number;
        total: number;
    };
    filters: { search?: string };
}>();

const search = ref(props.filters?.search ?? '');
const showModal = ref(false);
const editing = ref<BeneficiaryGroup | null>(null);

// Members Drawer state
const drawerVisible = ref(false);
const drawerGroup = ref<BeneficiaryGroup | null>(null);
const drawerMembers = ref<MemberItem[]>([]);
const drawerLoading = ref(false);
const availableMembers = ref<MemberItem[]>([]);
const addForm = useForm({ beneficiary_id: null as string | null, date_joined: '' });
const showAddForm = ref(false);

const form = useForm({
    group_name: '',
    group_type: '',
    total_members: '' as any,
    male_members: '' as any,
    female_members: '' as any,
    date_organized: '',
});

const columns = [
    { title: 'Group Name', dataIndex: 'group_name', key: 'group_name' },
    { title: 'Type', dataIndex: 'group_type', key: 'group_type', width: 120 },
    { title: 'Linked Members', dataIndex: 'members_count', key: 'members_count', width: 130, align: 'center' as const },
    { title: 'Total', dataIndex: 'total_members', key: 'total_members', width: 80, align: 'center' as const },
    { title: 'Male', dataIndex: 'male_members', key: 'male_members', width: 80, align: 'center' as const },
    { title: 'Female', dataIndex: 'female_members', key: 'female_members', width: 90, align: 'center' as const },
    { title: 'Date Organized', dataIndex: 'date_organized', key: 'date_organized', width: 140 },
    { title: 'Actions', key: 'action', align: 'center' as const, width: 130 },
];

const drawerColumns = [
    { title: 'Code', dataIndex: 'beneficiary_code', key: 'code', width: 100 },
    { title: 'Name', key: 'name' },
    { title: 'Sex', dataIndex: 'sex', key: 'sex', width: 70, align: 'center' as const },
    { title: 'Barangay', dataIndex: 'barangay', key: 'barangay' },
    { title: 'Date Joined', dataIndex: 'date_joined', key: 'date_joined', width: 115 },
    { title: '', key: 'remove', width: 60, align: 'center' as const },
];

let searchTimeout: ReturnType<typeof setTimeout>;
watch(search, (val) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(route('beneficiary-groups.index'), { search: val }, { preserveState: true, replace: true });
    }, 400);
});

const pagination = computed(() => ({
    current: props.groups.current_page,
    pageSize: props.groups.per_page,
    total: props.groups.total,
}));

const handleTableChange = (pag: any) => {
    router.get(route('beneficiary-groups.index'), {
        search: search.value,
        page: pag.current,
        per_page: pag.pageSize,
    }, { preserveState: true, replace: true });
};

const openCreate = () => {
    editing.value = null;
    form.reset();
    showModal.value = true;
};

const openEdit = (record: BeneficiaryGroup) => {
    editing.value = record;
    form.group_name = record.group_name;
    form.group_type = record.group_type ?? '';
    form.total_members = record.total_members ?? '';
    form.male_members = record.male_members ?? '';
    form.female_members = record.female_members ?? '';
    form.date_organized = record.date_organized ? record.date_organized.substring(0, 10) : '';
    showModal.value = true;
};

const handleSubmit = () => {
    if (editing.value) {
        form.put(route('beneficiary-groups.update', editing.value.id), {
            onSuccess: () => { showModal.value = false; message.success('Group updated!'); },
        });
    } else {
        form.post(route('beneficiary-groups.store'), {
            onSuccess: () => { showModal.value = false; form.reset(); message.success('Group created!'); },
        });
    }
};

const handleDelete = (record: BeneficiaryGroup) => {
    Modal.confirm({
        title: 'Delete Group',
        content: `Are you sure you want to delete "${record.group_name}"?`,
        okType: 'danger',
        onOk() {
            router.delete(route('beneficiary-groups.destroy', record.id), {
                onSuccess: () => message.success('Group deleted!'),
            });
        },
    });
};

// Members Drawer
const openMembersDrawer = async (record: BeneficiaryGroup) => {
    drawerGroup.value = record;
    drawerVisible.value = true;
    showAddForm.value = false;
    addForm.reset();
    await loadMembers();
};

const loadMembers = async () => {
    if (!drawerGroup.value) return;
    drawerLoading.value = true;
    const res = await axios.get(route('beneficiary-groups.members.index', drawerGroup.value.id));
    drawerMembers.value = res.data;
    drawerLoading.value = false;
};

const openAddForm = async () => {
    showAddForm.value = true;
    const res = await axios.get(route('beneficiary-groups.members.available', drawerGroup.value!.id));
    availableMembers.value = res.data;
};

const submitAddMember = () => {
    if (!drawerGroup.value) return;
    addForm.post(route('beneficiary-groups.members.store', drawerGroup.value.id), {
        onSuccess: () => {
            showAddForm.value = false;
            addForm.reset();
            loadMembers();
        },
    });
};

const removeMember = (beneficiaryId: string) => {
    if (!drawerGroup.value) return;
    Modal.confirm({
        title: 'Remove Member',
        content: 'Remove this individual from the group?',
        okText: 'Remove',
        okType: 'danger',
        onOk() {
            router.delete(route('beneficiary-groups.members.destroy', { group: drawerGroup.value!.id, beneficiary: beneficiaryId }), {
                onSuccess: () => loadMembers(),
            });
        },
    });
};
</script>

<template>
    <AppLayout title="Beneficiary Groups">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Beneficiary Groups</h2>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-4">
                    <div class="mb-3 text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded px-3 py-2">
                        ℹ️ Individuals linked to a group will have their assistance participation tracked at the <strong>group level</strong> and will be excluded from individual assistance records.
                    </div>
                    <div class="flex justify-between items-center mb-4">
                        <a-input-search
                            v-model:value="search"
                            placeholder="Search groups..."
                            style="width: 300px"
                            allow-clear
                        />
                        <a-space>
                            <a-button type="primary" @click="openCreate">
                                <template #icon><PlusOutlined /></template>
                                Add Group
                            </a-button>
                        </a-space>
                    </div>

                    <Table
                        :columns="columns"
                        :pagination="pagination"
                        @change="handleTableChange"
                        :data-source="groups.data"
                        row-key="id"
                    >
                        <template #bodyCell="{ column, record }">
                            <template v-if="column.key === 'date_organized'">
                                {{ formatDate(record.date_organized) }}
                            </template>
                            <template v-else-if="column.key === 'action'">
                                <a-space>
                                    <a-tooltip title="Manage Members">
                                        <a-button size="small" @click="openMembersDrawer(record)"><TeamOutlined /></a-button>
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

        <!-- Group Create/Edit Modal -->
        <a-modal
            v-model:open="showModal"
            :title="editing ? 'Edit Group' : 'Add Beneficiary Group'"
            :confirm-loading="form.processing"
            @ok="handleSubmit"
            width="550px"
            ok-text="Save"
        >
            <a-form layout="vertical" class="mt-2">
                <div class="grid grid-cols-2 gap-x-4">
                    <a-form-item label="Group Name" class="col-span-2" required>
                        <a-input v-model:value="form.group_name" />
                        <div class="text-red-500 text-xs" v-if="form.errors.group_name">{{ form.errors.group_name }}</div>
                    </a-form-item>
                    <a-form-item label="Group Type">
                        <a-input v-model:value="form.group_type" />
                    </a-form-item>
                    <a-form-item label="Date Organized">
                        <a-input type="date" v-model:value="form.date_organized" class="w-full" />
                    </a-form-item>
                    <a-form-item label="Total Members">
                        <a-input-number v-model:value="form.total_members" :min="0" class="w-full" />
                    </a-form-item>
                    <a-form-item label="Male Members">
                        <a-input-number v-model:value="form.male_members" :min="0" class="w-full" />
                    </a-form-item>
                    <a-form-item label="Female Members">
                        <a-input-number v-model:value="form.female_members" :min="0" class="w-full" />
                    </a-form-item>
                </div>
            </a-form>
        </a-modal>

        <!-- Members Drawer -->
        <a-drawer
            v-model:open="drawerVisible"
            :title="`Members — ${drawerGroup?.group_name ?? ''}`"
            placement="right"
            width="700"
            destroy-on-close
        >
            <div class="mb-3">
                <a-alert
                    type="info"
                    message="Members linked here are excluded from individual assistance records."
                    show-icon
                    class="mb-3"
                />
                <div class="flex justify-end">
                    <a-button type="primary" size="small" @click="openAddForm" v-if="!showAddForm">
                        <template #icon><PlusOutlined /></template>
                        Add Member
                    </a-button>
                </div>
            </div>

            <!-- Add form -->
            <a-card v-if="showAddForm" class="mb-4" size="small" title="Add a Member">
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
                                    v-for="b in availableMembers"
                                    :key="b.id"
                                    :value="b.id"
                                    :label="`${b.last_name}, ${b.first_name} (${b.beneficiary_code})`"
                                >
                                    {{ b.last_name }}, {{ b.first_name }}
                                    <span class="text-gray-400 text-xs ml-1">{{ b.beneficiary_code }}</span>
                                    <a-tag size="small" :color="b.sex === 'Male' ? 'blue' : 'pink'" class="ml-1 text-xs">{{ b.sex }}</a-tag>
                                </a-select-option>
                            </a-select>
                            <div class="text-red-500 text-xs" v-if="addForm.errors.beneficiary_id">{{ addForm.errors.beneficiary_id }}</div>
                        </a-form-item>
                        <a-form-item label="Date Joined">
                            <a-input type="date" v-model:value="addForm.date_joined" class="w-full" />
                        </a-form-item>
                    </div>
                    <div class="flex gap-2 justify-end">
                        <a-button @click="showAddForm = false">Cancel</a-button>
                        <a-button type="primary" :loading="addForm.processing" @click="submitAddMember">Add</a-button>
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
                        <template v-else-if="column.key === 'sex'">
                            <ManOutlined v-if="record.sex === 'Male'" class="text-blue-500" />
                            <WomanOutlined v-else class="text-pink-500" />
                        </template>
                        <template v-else-if="column.key === 'date_joined'">
                            {{ formatDate(record.date_joined) }}
                        </template>
                        <template v-else-if="column.key === 'remove'">
                            <a-tooltip title="Remove">
                                <a-button size="small" danger @click="removeMember(record.id)">
                                    <template #icon><UserDeleteOutlined /></template>
                                </a-button>
                            </a-tooltip>
                        </template>
                    </template>
                </a-table>
                <a-empty v-if="!drawerLoading && drawerMembers.length === 0" description="No members linked yet." class="mt-6" />
            </a-spin>
        </a-drawer>
    </AppLayout>
</template>

