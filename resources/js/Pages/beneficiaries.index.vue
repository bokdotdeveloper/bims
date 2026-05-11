<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import Table from '@/Components/Table.vue';
import ExportButtons from '@/Components/ExportButtons.vue';
import { router, useForm } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import { PlusOutlined, EditOutlined, DeleteOutlined, EyeOutlined, TeamOutlined, UsergroupAddOutlined, WarningOutlined } from '@ant-design/icons-vue';
import { message, Modal } from 'ant-design-vue';
import { formatDate } from '@/composables/useDateFormat';
import axios from 'axios';

interface Beneficiary {
    id: string;
    beneficiary_code: string;
    beneficiary_type: string;
    last_name: string;
    first_name: string;
    middle_name: string;
    date_of_birth: string;
    sex: string;
    civil_status: string;
    address: string;
    barangay: string;
    municipality: string;
    province: string;
    contact_number: string;
    is_active: boolean;
    assistance_records_count?: number;
    groups_count?: number;
}

interface AssistanceItem {
    id: string;
    assistance_type: string;
    amount: string | null;
    date_released: string | null;
    project: string | null;
    released_by: string | null;
    remarks: string | null;
}

interface GroupItem {
    id: number;
    group_name: string;
    group_type: string | null;
    date_joined: string | null;
}

interface FamilyMember {
    id: number;
    relationship: string;
    last_name: string;
    first_name: string;
    middle_name: string | null;
    date_of_birth: string | null;
    sex: string | null;
    civil_status: string | null;
    occupation: string | null;
    educational_attainment: string | null;
    is_pwd: boolean;
    is_senior: boolean;
    remarks: string | null;
    linked_beneficiary_id: string | null;
    linked_beneficiary: { id: string; beneficiary_code: string; name: string } | null;
}

const props = defineProps<{
    beneficiaries: {
        data: Beneficiary[];
        current_page: number;
        per_page: number;
        total: number;
    };
    filters: { search?: string; barangay?: string };
}>();

const search = ref(props.filters?.search ?? '');
const showModal = ref(false);
const editing = ref<Beneficiary | null>(null);

// Detail drawer
const detailVisible = ref(false);
const detailBeneficiary = ref<Beneficiary | null>(null);
const detailLoading = ref(false);
const detailAssistance = ref<AssistanceItem[]>([]);
const detailGroups = ref<GroupItem[]>([]);
const activeTab = ref('assistance');

// Family members
const detailFamily = ref<FamilyMember[]>([]);
const showFamilyForm = ref(false);
const editingFamily = ref<FamilyMember | null>(null);
const familyFormLoading = ref(false);
const familyFormErrors = ref<Record<string, string>>({});
const beneficiarySearchResults = ref<{
    id: string;
    beneficiary_code: string;
    name: string;
    barangay: string;
    last_name: string;
    first_name: string;
    middle_name: string | null;
    sex: string | null;
    civil_status: string | null;
    date_of_birth: string | null;
}[]>([]);
const beneficiarySearchLoading = ref(false);

const beneficiarySearchOptions = computed(() =>
    beneficiarySearchResults.value.map(b => ({
        value: b.id,
        label: `${b.name} (${b.beneficiary_code}${b.barangay ? ' · ' + b.barangay : ''})`,
    }))
);
const familyForm = ref({
    relationship: '',
    last_name: '',
    first_name: '',
    middle_name: '',
    date_of_birth: '',
    sex: '',
    civil_status: '',
    occupation: '',
    educational_attainment: '',
    is_pwd: false,
    is_senior: false,
    linked_beneficiary_id: null as string | null,
    remarks: '',
});

const form = useForm({
    beneficiary_code: '',
    beneficiary_type: '',
    last_name: '',
    first_name: '',
    middle_name: '',
    date_of_birth: '',
    sex: 'Male',
    civil_status: 'Single',
    address: '',
    barangay: '',
    municipality: '',
    province: '',
    contact_number: '',
    is_active: true,
});

const columns = [
    { title: 'Code', dataIndex: 'beneficiary_code', key: 'beneficiary_code', width: 120 },
    { title: 'Name', key: 'name', width: 220 },
    { title: 'Sex', dataIndex: 'sex', key: 'sex', width: 80 },
    { title: 'Civil Status', dataIndex: 'civil_status', key: 'civil_status', width: 120 },
    { title: 'Barangay', dataIndex: 'barangay', key: 'barangay' },
    { title: 'Contact', dataIndex: 'contact_number', key: 'contact_number' },
    { title: 'Groups', dataIndex: 'groups_count', key: 'groups_count', width: 80, align: 'center' as const },
    { title: 'Assistance', dataIndex: 'assistance_records_count', key: 'assistance_records_count', width: 90, align: 'center' as const },
    { title: 'Status', dataIndex: 'is_active', key: 'is_active', width: 90 },
    { title: 'Actions', key: 'action', align: 'center' as const, width: 110 },
];

const assistanceColumns = [
    { title: 'Type', dataIndex: 'assistance_type', key: 'assistance_type' },
    { title: 'Amount', dataIndex: 'amount', key: 'amount', width: 110, align: 'right' as const },
    { title: 'Date Released', dataIndex: 'date_released', key: 'date_released', width: 130 },
    { title: 'Project', dataIndex: 'project', key: 'project' },
    { title: 'Released By', dataIndex: 'released_by', key: 'released_by' },
    { title: 'Remarks', dataIndex: 'remarks', key: 'remarks' },
];

const groupColumns = [
    { title: 'Group Name', dataIndex: 'group_name', key: 'group_name' },
    { title: 'Type', dataIndex: 'group_type', key: 'group_type', width: 140 },
    { title: 'Date Joined', dataIndex: 'date_joined', key: 'date_joined', width: 130 },
];

const familyColumns = [
    { title: 'Relationship', dataIndex: 'relationship', key: 'relationship', width: 120 },
    { title: 'Name', key: 'name' },
    { title: 'Sex', dataIndex: 'sex', key: 'sex', width: 70 },
    { title: 'Age/DOB', key: 'dob', width: 110 },
    { title: 'Occupation', dataIndex: 'occupation', key: 'occupation' },
    { title: 'Cross-match', key: 'crossmatch', width: 140 },
    { title: '', key: 'actions', width: 80, align: 'center' as const },
];

let searchTimeout: ReturnType<typeof setTimeout>;
watch(search, (val) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(route('beneficiaries.index'), { search: val }, { preserveState: true, replace: true });
    }, 400);
});

const pagination = computed(() => ({
    current: props.beneficiaries.current_page,
    pageSize: props.beneficiaries.per_page,
    total: props.beneficiaries.total,
}));

const handleTableChange = (pag: any) => {
    router.get(route('beneficiaries.index'), {
        search: search.value,
        page: pag.current,
        per_page: pag.pageSize,
    }, { preserveState: true, replace: true });
};

const openCreate = () => {
    editing.value = null;
    form.reset();
    form.is_active = true;
    form.sex = 'Male';
    form.civil_status = 'Single';
    showModal.value = true;
};

const openEdit = (record: Beneficiary) => {
    editing.value = record;
    form.beneficiary_code = record.beneficiary_code;
    form.beneficiary_type = record.beneficiary_type ?? '';
    form.last_name = record.last_name;
    form.first_name = record.first_name;
    form.middle_name = record.middle_name ?? '';
    form.date_of_birth = record.date_of_birth ? record.date_of_birth.substring(0, 10) : '';
    form.sex = record.sex;
    form.civil_status = record.civil_status;
    form.address = record.address ?? '';
    form.barangay = record.barangay ?? '';
    form.municipality = record.municipality ?? '';
    form.province = record.province ?? '';
    form.contact_number = record.contact_number ?? '';
    form.is_active = record.is_active;
    showModal.value = true;
};

const handleSubmit = () => {
    if (editing.value) {
        form.put(route('beneficiaries.update', editing.value.id), {
            onSuccess: () => { showModal.value = false; message.success('Beneficiary updated!'); },
        });
    } else {
        form.post(route('beneficiaries.store'), {
            onSuccess: () => { showModal.value = false; form.reset(); message.success('Beneficiary created!'); },
        });
    }
};

const handleDelete = (record: Beneficiary) => {
    Modal.confirm({
        title: 'Delete Beneficiary',
        content: `Are you sure you want to delete ${record.first_name} ${record.last_name}?`,
        okType: 'danger',
        onOk() {
            router.delete(route('beneficiaries.destroy', record.id), {
                onSuccess: () => message.success('Beneficiary deleted!'),
            });
        },
    });
};

// Detail drawer
const openDetail = async (record: Beneficiary) => {
    detailBeneficiary.value = record;
    detailVisible.value = true;
    detailLoading.value = true;
    activeTab.value = 'assistance';
    const res = await axios.get(route('beneficiaries.details', record.id));
    detailAssistance.value = res.data.assistance;
    detailGroups.value = res.data.groups;
    detailLoading.value = false;
    // Load family in background
    loadFamily(record.id);
};

const loadFamily = async (beneficiaryId: string) => {
    const res = await axios.get(route('beneficiaries.family.index', beneficiaryId));
    detailFamily.value = res.data;
};

const resetFamilyForm = () => {
    familyForm.value = {
        relationship: '', last_name: '', first_name: '', middle_name: '',
        date_of_birth: '', sex: '', civil_status: '', occupation: '',
        educational_attainment: '', is_pwd: false, is_senior: false,
        linked_beneficiary_id: null, remarks: '',
    };
    editingFamily.value = null;
    familyFormErrors.value = {};
    beneficiarySearchResults.value = [];
};

const openFamilyCreate = () => {
    resetFamilyForm();
    showFamilyForm.value = true;
};

const openFamilyEdit = (member: FamilyMember) => {
    editingFamily.value = member;
    familyForm.value = {
        relationship: member.relationship,
        last_name: member.last_name,
        first_name: member.first_name,
        middle_name: member.middle_name ?? '',
        date_of_birth: member.date_of_birth ?? '',
        sex: member.sex ?? '',
        civil_status: member.civil_status ?? '',
        occupation: member.occupation ?? '',
        educational_attainment: member.educational_attainment ?? '',
        is_pwd: member.is_pwd,
        is_senior: member.is_senior,
        linked_beneficiary_id: member.linked_beneficiary_id,
        remarks: member.remarks ?? '',
    };
    showFamilyForm.value = true;
};

const submitFamilyForm = async () => {
    if (!detailBeneficiary.value) return;
    familyFormLoading.value = true;
    familyFormErrors.value = {};
    try {
        if (editingFamily.value) {
            await axios.put(route('beneficiaries.family.update', {
                beneficiary: detailBeneficiary.value.id,
                member: editingFamily.value.id,
            }), familyForm.value);
            message.success('Family member updated!');
        } else {
            await axios.post(route('beneficiaries.family.store', detailBeneficiary.value.id), familyForm.value);
            if (familyForm.value.linked_beneficiary_id) {
                // Prominent cross-match notification
                const linked = beneficiarySearchOptions.value.find(
                    o => o.value === familyForm.value.linked_beneficiary_id
                );
                message.warning({
                    content: `⚠️ Cross-match recorded: this family member is linked to an existing beneficiary${linked ? ' — ' + linked.label : ''}. Monitor to prevent duplicate assistance.`,
                    duration: 8,
                });
            } else {
                message.success('Family member added!');
            }
        }
        showFamilyForm.value = false;
        similarityMatches.value = [];
        resetFamilyForm();
        loadFamily(detailBeneficiary.value.id);
    } catch (e: any) {
        if (e.response?.status === 422 && e.response?.data?.errors) {
            familyFormErrors.value = Object.fromEntries(
                Object.entries(e.response.data.errors).map(([k, v]) => [k, (v as string[])[0]])
            );
            message.error(e.response.data.message ?? 'Please fix the errors below.');
        } else {
            message.error('An unexpected error occurred. Please try again.');
        }
    } finally {
        familyFormLoading.value = false;
    }
};

const deleteFamilyMember = (member: FamilyMember) => {
    if (!detailBeneficiary.value) return;
    Modal.confirm({
        title: 'Remove Family Member',
        content: `Remove ${member.first_name} ${member.last_name} from family records?`,
        okType: 'danger',
        async onOk() {
            await axios.delete(route('beneficiaries.family.destroy', {
                beneficiary: detailBeneficiary.value!.id,
                member: member.id,
            }));
            message.success('Family member removed.');
            loadFamily(detailBeneficiary.value!.id);
        },
    });
};

const searchBeneficiaries = async (val: string) => {
    if (!val || val.length < 1) { beneficiarySearchResults.value = []; return; }
    beneficiarySearchLoading.value = true;
    try {
        const res = await axios.get(route('beneficiaries.search'), { params: { q: val } });
        beneficiarySearchResults.value = res.data.filter((b: any) => b.id !== detailBeneficiary.value?.id);
    } catch {
        beneficiarySearchResults.value = [];
    } finally {
        beneficiarySearchLoading.value = false;
    }
};

const formatAmount = (val: string | null) =>
    val ? '₱ ' + parseFloat(val).toLocaleString('en-PH', { minimumFractionDigits: 2 }) : '—';

const onSelectLinkedBeneficiary = (selectedId: string) => {
    const found = beneficiarySearchResults.value.find(b => b.id === selectedId);
    if (!found) return;
    familyForm.value.last_name     = found.last_name;
    familyForm.value.first_name    = found.first_name;
    familyForm.value.middle_name   = found.middle_name ?? '';
    familyForm.value.sex           = found.sex ?? '';
    familyForm.value.civil_status  = found.civil_status ?? '';
    familyForm.value.date_of_birth = found.date_of_birth ?? '';
    similarityMatches.value = []; // clear similarity since we used dropdown
    message.info({
        content: `Auto-filled from beneficiary record: ${found.name} (${found.beneficiary_code}). This family member is now cross-matched.`,
        duration: 5,
    });
};

// ── Similarity detection ────────────────────────────────────────────────────
interface SimilarMatch {
    id: string;
    beneficiary_code: string;
    name: string;
    date_of_birth: string | null;
    sex: string | null;
    civil_status: string | null;
    barangay: string | null;
    is_active: boolean;
    score: number;
    matched_fields: string[];
}

const similarityMatches = ref<SimilarMatch[]>([]);
const similarityChecking = ref(false);
let similarityTimeout: ReturnType<typeof setTimeout>;

const runSimilarityCheck = () => {
    const ln = familyForm.value.last_name.trim();
    const fn = familyForm.value.first_name.trim();
    // Skip if linked beneficiary already selected (auto-filled)
    if (familyForm.value.linked_beneficiary_id) { similarityMatches.value = []; return; }
    if (ln.length < 2 && fn.length < 2) { similarityMatches.value = []; return; }

    clearTimeout(similarityTimeout);
    similarityTimeout = setTimeout(async () => {
        similarityChecking.value = true;
        try {
            const res = await axios.get(route('beneficiaries.similarity'), {
                params: {
                    last_name: ln,
                    first_name: fn,
                    date_of_birth: familyForm.value.date_of_birth || undefined,
                    exclude_beneficiary_id: detailBeneficiary.value?.id,
                },
            });
            similarityMatches.value = res.data;
        } catch {
            similarityMatches.value = [];
        } finally {
            similarityChecking.value = false;
        }
    }, 600);
};

watch(
    () => [familyForm.value.last_name, familyForm.value.first_name, familyForm.value.date_of_birth],
    () => runSimilarityCheck()
);

// Also clear similarity matches when linked beneficiary is cleared
watch(() => familyForm.value.linked_beneficiary_id, (val) => {
    if (!val) similarityMatches.value = [];
});

const linkSimilarMatch = (match: SimilarMatch) => {
    familyForm.value.linked_beneficiary_id = match.id;
    // Keep already-filled name fields; just set the link
    message.warning({
        content: `Linked to beneficiary ${match.beneficiary_code} — ${match.name}. Please verify the relationship.`,
        duration: 6,
    });
    similarityMatches.value = [];
};

// ── Family-member match detection for the Add/Edit Beneficiary modal ─────────
interface FamilyMemberMatch {
    family_member_id: number;
    name: string;
    relationship: string;
    date_of_birth: string | null;
    sex: string | null;
    owner_beneficiary: {
        id: string;
        beneficiary_code: string;
        name: string;
        barangay: string | null;
    } | null;
    score: number;
    matched_fields: string[];
}
const familyMatchResults = ref<FamilyMemberMatch[]>([]);
const familyMatchChecking = ref(false);
let familyMatchTimeout: ReturnType<typeof setTimeout>;

const runFamilyMatchCheck = () => {
    if (!showModal.value || editing.value) { familyMatchResults.value = []; return; }
    const ln = form.last_name.trim();
    const fn = form.first_name.trim();
    if (ln.length < 2 && fn.length < 2) { familyMatchResults.value = []; return; }
    clearTimeout(familyMatchTimeout);
    familyMatchTimeout = setTimeout(async () => {
        familyMatchChecking.value = true;
        try {
            const res = await axios.get(route('beneficiaries.family-match'), {
                params: {
                    last_name: ln,
                    first_name: fn,
                    date_of_birth: form.date_of_birth || undefined,
                },
            });
            familyMatchResults.value = res.data;
        } catch {
            familyMatchResults.value = [];
        } finally {
            familyMatchChecking.value = false;
        }
    }, 600);
};

watch(
    () => [form.last_name, form.first_name, form.date_of_birth],
    () => runFamilyMatchCheck()
);

// clear when modal closes
watch(showModal, (open) => {
    if (!open) familyMatchResults.value = [];
});
</script>

<template>
    <AppLayout title="Beneficiaries">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Beneficiaries</h2>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-4">
                    <div class="flex justify-between items-center mb-4">
                        <a-input-search
                            v-model:value="search"
                            placeholder="Search by name or code..."
                            style="width: 300px"
                            allow-clear
                        />
                        <a-space>
                            <ExportButtons
                                :pdf-route="route('reports.beneficiaries.pdf')"
                                :excel-route="route('reports.beneficiaries.excel')"
                                :params="{ search }"
                            />
                            <a-button type="primary" @click="openCreate">
                                <template #icon><PlusOutlined /></template>
                                Add Beneficiary
                            </a-button>
                        </a-space>
                    </div>

                    <Table
                        :columns="columns"
                        :pagination="pagination"
                        @change="handleTableChange"
                        :data-source="beneficiaries.data"
                        row-key="id"
                    >
                        <template #bodyCell="{ column, record }">
                            <template v-if="column.key === 'name'">
                                <div class="font-medium">{{ record.last_name }}, {{ record.first_name }} {{ record.middle_name }}</div>
                                <div class="text-xs text-gray-400">{{ record.beneficiary_type }}</div>
                            </template>
                            <template v-else-if="column.key === 'groups_count'">
                                <a-badge
                                    :count="record.groups_count"
                                    :number-style="{ backgroundColor: record.groups_count ? '#7c3aed' : '#d9d9d9' }"
                                    :overflow-count="99"
                                    show-zero
                                />
                            </template>
                            <template v-else-if="column.key === 'assistance_records_count'">
                                <a-badge
                                    :count="record.assistance_records_count"
                                    :number-style="{ backgroundColor: record.assistance_records_count ? '#0284c7' : '#d9d9d9' }"
                                    :overflow-count="99"
                                    show-zero
                                />
                            </template>
                            <template v-else-if="column.key === 'is_active'">
                                <a-tag :color="record.is_active ? 'green' : 'red'">
                                    {{ record.is_active ? 'Active' : 'Inactive' }}
                                </a-tag>
                            </template>
                            <template v-else-if="column.key === 'action'">
                                <a-space>
                                    <a-tooltip title="View Details">
                                        <a-button size="small" @click="openDetail(record)"><EyeOutlined /></a-button>
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

        <!-- Create/Edit Modal -->
        <a-modal
            v-model:open="showModal"
            :title="editing ? 'Edit Beneficiary' : 'Add Beneficiary'"
            :confirm-loading="form.processing"
            @ok="handleSubmit"
            width="750px"
            ok-text="Save"
        >
            <a-form layout="vertical" class="mt-2">
                <div class="grid grid-cols-2 gap-x-4">
                    <a-form-item label="Beneficiary Code" required>
                        <a-input v-model:value="form.beneficiary_code" />
                        <div class="text-red-500 text-xs" v-if="form.errors.beneficiary_code">{{ form.errors.beneficiary_code }}</div>
                    </a-form-item>
                    <a-form-item label="Beneficiary Type">
                        <a-input v-model:value="form.beneficiary_type" />
                    </a-form-item>
                    <a-form-item label="Last Name" required>
                        <a-input v-model:value="form.last_name" />
                        <div class="text-red-500 text-xs" v-if="form.errors.last_name">{{ form.errors.last_name }}</div>
                    </a-form-item>
                    <a-form-item label="First Name" required>
                        <a-input v-model:value="form.first_name" />
                        <div class="text-red-500 text-xs" v-if="form.errors.first_name">{{ form.errors.first_name }}</div>
                    </a-form-item>
                    <a-form-item label="Middle Name">
                        <a-input v-model:value="form.middle_name" />
                    </a-form-item>
                    <a-form-item label="Date of Birth" required>
                        <a-input type="date" v-model:value="form.date_of_birth" class="w-full" />
                        <div class="text-red-500 text-xs" v-if="form.errors.date_of_birth">{{ form.errors.date_of_birth }}</div>
                    </a-form-item>
                    <a-form-item label="Sex" required>
                        <a-select v-model:value="form.sex">
                            <a-select-option value="Male">Male</a-select-option>
                            <a-select-option value="Female">Female</a-select-option>
                        </a-select>
                    </a-form-item>
                    <a-form-item label="Civil Status" required>
                        <a-select v-model:value="form.civil_status">
                            <a-select-option value="Single">Single</a-select-option>
                            <a-select-option value="Married">Married</a-select-option>
                            <a-select-option value="Widowed">Widowed</a-select-option>
                            <a-select-option value="Separated">Separated</a-select-option>
                        </a-select>
                        <div class="text-red-500 text-xs" v-if="form.errors.civil_status">{{ form.errors.civil_status }}</div>
                    </a-form-item>
                    <a-form-item label="Address" class="col-span-2">
                        <a-input v-model:value="form.address" />
                    </a-form-item>
                    <a-form-item label="Barangay">
                        <a-input v-model:value="form.barangay" />
                    </a-form-item>
                    <a-form-item label="Municipality">
                        <a-input v-model:value="form.municipality" />
                    </a-form-item>
                    <a-form-item label="Province">
                        <a-input v-model:value="form.province" />
                    </a-form-item>
                    <a-form-item label="Contact Number">
                        <a-input v-model:value="form.contact_number" />
                    </a-form-item>
                    <a-form-item label="Status">
                        <a-switch v-model:checked="form.is_active" checked-children="Active" un-checked-children="Inactive" />
                    </a-form-item>
                </div>

                <!-- ── Family member match warning ── -->
                <template v-if="!editing">
                    <div v-if="familyMatchChecking" class="text-xs text-gray-400 mt-1 mb-2">
                        <a-spin size="small" /> Checking family member records…
                    </div>
                    <template v-if="familyMatchResults.length > 0">
                        <a-alert type="warning" show-icon class="mb-2">
                            <template #message>
                                <span class="font-semibold">Possible match in family member records!</span>
                                This person may already be listed as a family member of an existing beneficiary.
                            </template>
                            <template #description>
                                <div v-for="m in familyMatchResults" :key="m.family_member_id" class="mt-1 text-xs border-t pt-1">
                                    <span class="font-medium">{{ m.name }}</span>
                                    <span class="ml-1 text-gray-500">({{ m.relationship }})</span>
                                    <span v-if="m.date_of_birth" class="ml-1 text-gray-400">· DOB: {{ m.date_of_birth }}</span>
                                    <template v-if="m.owner_beneficiary">
                                        <span class="ml-1">— family member of </span>
                                        <span class="font-medium text-orange-700">{{ m.owner_beneficiary.name }}</span>
                                        <span class="text-gray-500 ml-1">({{ m.owner_beneficiary.beneficiary_code }}
                                            <template v-if="m.owner_beneficiary.barangay">· {{ m.owner_beneficiary.barangay }}</template>)
                                        </span>
                                    </template>
                                    <a-tag color="orange" class="ml-2 text-xs">{{ m.matched_fields.join(', ') }}</a-tag>
                                </div>
                            </template>
                        </a-alert>
                    </template>
                </template>
            </a-form>
        </a-modal>

        <!-- Detail Drawer -->
        <a-drawer
            v-model:open="detailVisible"
            placement="right"
            width="750"
            destroy-on-close
        >
            <template #title>
                <div v-if="detailBeneficiary">
                    <div class="font-semibold text-base">
                        {{ detailBeneficiary.last_name }}, {{ detailBeneficiary.first_name }} {{ detailBeneficiary.middle_name }}
                    </div>
                    <div class="text-xs text-gray-400 font-normal">
                        {{ detailBeneficiary.beneficiary_code }} · {{ detailBeneficiary.sex }} · {{ detailBeneficiary.barangay }}, {{ detailBeneficiary.municipality }}
                    </div>
                </div>
            </template>

            <a-spin :spinning="detailLoading">
                <a-tabs v-model:active-key="activeTab">
                    <!-- Assistance Records Tab -->
                    <a-tab-pane key="assistance">
                        <template #tab>
                            <span>
                                Assistance Records
                                <a-badge :count="detailAssistance.length" :number-style="{ backgroundColor: '#0284c7', fontSize: '10px' }" class="ml-1" />
                            </span>
                        </template>

                        <a-table
                            :data-source="detailAssistance"
                            :columns="assistanceColumns"
                            :pagination="{ pageSize: 10, size: 'small' }"
                            row-key="id"
                            size="small"
                        >
                            <template #bodyCell="{ column, record }">
                                <template v-if="column.key === 'amount'">
                                    <span class="font-mono">{{ formatAmount(record.amount) }}</span>
                                </template>
                                <template v-else-if="column.key === 'date_released'">
                                    {{ formatDate(record.date_released) }}
                                </template>
                                <template v-else-if="column.key === 'project'">
                                    {{ record.project ?? '—' }}
                                </template>
                                <template v-else-if="column.key === 'remarks'">
                                    <span class="text-gray-500 text-xs">{{ record.remarks ?? '—' }}</span>
                                </template>
                            </template>
                        </a-table>
                        <a-empty v-if="!detailLoading && detailAssistance.length === 0" description="No assistance records found." class="mt-6" />
                    </a-tab-pane>

                    <!-- Group Memberships Tab -->
                    <a-tab-pane key="groups">
                        <template #tab>
                            <span>
                                <TeamOutlined />
                                Group Memberships
                                <a-badge :count="detailGroups.length" :number-style="{ backgroundColor: '#7c3aed', fontSize: '10px' }" class="ml-1" />
                            </span>
                        </template>

                        <a-alert
                            v-if="detailGroups.length > 0"
                            type="warning"
                            show-icon
                            message="This beneficiary is linked to one or more groups. Their assistance participation is tracked at the group level."
                            class="mb-3"
                        />
                        <a-table
                            :data-source="detailGroups"
                            :columns="groupColumns"
                            :pagination="false"
                            row-key="id"
                            size="small"
                        >
                            <template #bodyCell="{ column, record }">
                                <template v-if="column.key === 'group_type'">
                                    <a-tag color="purple">{{ record.group_type ?? '—' }}</a-tag>
                                </template>
                                <template v-else-if="column.key === 'date_joined'">
                                    {{ formatDate(record.date_joined) }}
                                </template>
                            </template>
                        </a-table>
                        <a-empty v-if="!detailLoading && detailGroups.length === 0" description="Not linked to any group." class="mt-6" />
                    </a-tab-pane>

                    <!-- Family Members Tab -->
                    <a-tab-pane key="family">
                        <template #tab>
                            <span>
                                <UsergroupAddOutlined />
                                Family Members
                                <a-badge :count="detailFamily.length" :number-style="{ backgroundColor: '#059669', fontSize: '10px' }" class="ml-1" />
                            </span>
                        </template>

                        <!-- Cross-match warning -->
                        <a-alert
                            v-if="detailFamily.some(m => m.linked_beneficiary_id)"
                            type="warning"
                            show-icon
                            class="mb-3"
                        >
                            <template #message>
                                <span class="font-semibold">Cross-match detected!</span>
                                One or more family members are registered as beneficiaries. Review to prevent duplicate assistance records.
                            </template>
                        </a-alert>

                        <div class="flex justify-end mb-3">
                            <a-button type="primary" size="small" @click="openFamilyCreate" v-if="!showFamilyForm">
                                <template #icon><PlusOutlined /></template>
                                Add Family Member
                            </a-button>
                        </div>

                        <!-- Family Add/Edit Form -->
                        <a-card v-if="showFamilyForm" class="mb-4" size="small"
                            :title="editingFamily ? 'Edit Family Member' : 'Add Family Member'">
                            <div class="grid grid-cols-2 gap-x-4">
                                <a-form-item label="Relationship" required class="mb-2">
                                    <a-select v-model:value="familyForm.relationship" style="width:100%"
                                        :status="familyFormErrors.relationship ? 'error' : ''">
                                        <a-select-option value="Head">Head</a-select-option>
                                        <a-select-option value="Spouse">Spouse</a-select-option>
                                        <a-select-option value="Child">Child</a-select-option>
                                        <a-select-option value="Parent">Parent</a-select-option>
                                        <a-select-option value="Sibling">Sibling</a-select-option>
                                        <a-select-option value="Other">Other</a-select-option>
                                    </a-select>
                                    <div class="text-red-500 text-xs mt-0.5" v-if="familyFormErrors.relationship">{{ familyFormErrors.relationship }}</div>
                                </a-form-item>
                                <a-form-item label="Sex" class="mb-2">
                                    <a-select v-model:value="familyForm.sex" style="width:100%" allow-clear>
                                        <a-select-option value="Male">Male</a-select-option>
                                        <a-select-option value="Female">Female</a-select-option>
                                    </a-select>
                                </a-form-item>
                                <a-form-item label="Last Name" required class="mb-2">
                                    <a-input v-model:value="familyForm.last_name"
                                        :status="familyFormErrors.last_name ? 'error' : ''" />
                                    <div class="text-red-500 text-xs mt-0.5" v-if="familyFormErrors.last_name">{{ familyFormErrors.last_name }}</div>
                                </a-form-item>
                                <a-form-item label="First Name" required class="mb-2">
                                    <a-input v-model:value="familyForm.first_name"
                                        :status="familyFormErrors.first_name ? 'error' : ''" />
                                    <div class="text-red-500 text-xs mt-0.5" v-if="familyFormErrors.first_name">{{ familyFormErrors.first_name }}</div>
                                </a-form-item>
                                <a-form-item label="Middle Name" class="mb-2">
                                    <a-input v-model:value="familyForm.middle_name" />
                                </a-form-item>
                                <a-form-item label="Date of Birth" class="mb-2">
                                    <a-input type="date" v-model:value="familyForm.date_of_birth" class="w-full" />
                                </a-form-item>
                                <a-form-item label="Civil Status" class="mb-2">
                                    <a-select v-model:value="familyForm.civil_status" style="width:100%" allow-clear>
                                        <a-select-option value="Single">Single</a-select-option>
                                        <a-select-option value="Married">Married</a-select-option>
                                        <a-select-option value="Widowed">Widowed</a-select-option>
                                        <a-select-option value="Separated">Separated</a-select-option>
                                    </a-select>
                                </a-form-item>
                                <a-form-item label="Occupation" class="mb-2">
                                    <a-input v-model:value="familyForm.occupation" />
                                </a-form-item>
                                <a-form-item label="Educational Attainment" class="mb-2">
                                    <a-select v-model:value="familyForm.educational_attainment" style="width:100%" allow-clear>
                                        <a-select-option value="No formal education">No formal education</a-select-option>
                                        <a-select-option value="Elementary">Elementary</a-select-option>
                                        <a-select-option value="High School">High School</a-select-option>
                                        <a-select-option value="Senior High School">Senior High School</a-select-option>
                                        <a-select-option value="Vocational">Vocational</a-select-option>
                                        <a-select-option value="College">College</a-select-option>
                                        <a-select-option value="Post-graduate">Post-graduate</a-select-option>
                                    </a-select>
                                </a-form-item>
                                <a-form-item label="Flags" class="mb-2 col-span-2">
                                    <a-space>
                                        <a-checkbox v-model:checked="familyForm.is_pwd">PWD</a-checkbox>
                                        <a-checkbox v-model:checked="familyForm.is_senior">Senior Citizen</a-checkbox>
                                    </a-space>
                                </a-form-item>
                                <!-- Cross-match: link to existing beneficiary -->
                                <a-form-item class="col-span-2 mb-2">
                                    <template #label>
                                        <span>Linked Beneficiary <span class="text-xs text-orange-500 font-normal">(cross-match)</span></span>
                                    </template>
                                    <a-select
                                        v-model:value="familyForm.linked_beneficiary_id"
                                        show-search
                                        allow-clear
                                        placeholder="Type a name or code to search..."
                                        style="width:100%"
                                        :filter-option="false"
                                        :options="beneficiarySearchOptions"
                                        :loading="beneficiarySearchLoading"
                                        :not-found-content="beneficiarySearchLoading ? 'Searching...' : (beneficiarySearchResults.length === 0 ? 'Type to search beneficiaries' : 'No results found')"
                                        @search="searchBeneficiaries"
                                        @change="onSelectLinkedBeneficiary"
                                    />
                                    <div class="text-xs text-orange-600 mt-1" v-if="familyForm.linked_beneficiary_id">
                                        <WarningOutlined /> This family member is linked to an existing beneficiary record.
                                    </div>
                                </a-form-item>
                                <a-form-item label="Remarks" class="col-span-2 mb-2">
                                    <a-textarea v-model:value="familyForm.remarks" :rows="2" />
                                </a-form-item>

                                <!-- ── Similarity detection alert ── -->
                                <template v-if="similarityMatches.length > 0">
                                    <div class="col-span-2 mb-2">
                                        <a-alert type="warning" show-icon>
                                            <template #message>
                                                <span class="font-semibold">Possible duplicate detected!</span>
                                                The name you entered is similar to
                                                {{ similarityMatches.length }} registered
                                                {{ similarityMatches.length === 1 ? 'beneficiary' : 'beneficiaries' }}.
                                                Please verify before saving.
                                            </template>
                                            <template #description>
                                                <div
                                                    v-for="m in similarityMatches"
                                                    :key="m.id"
                                                    class="flex items-start justify-between gap-2 mt-2 border border-orange-200 bg-orange-50 rounded p-2"
                                                >
                                                    <div class="text-xs">
                                                        <div class="font-semibold text-orange-800">{{ m.name }}</div>
                                                        <div class="text-gray-600">
                                                            {{ m.beneficiary_code }}
                                                            <span v-if="m.barangay"> · {{ m.barangay }}</span>
                                                            <span v-if="m.date_of_birth"> · {{ formatDate(m.date_of_birth) }}</span>
                                                        </div>
                                                        <div class="mt-0.5 flex gap-1 flex-wrap">
                                                            <a-tag
                                                                v-for="f in m.matched_fields"
                                                                :key="f"
                                                                color="orange"
                                                                class="text-xs"
                                                            >{{ f }}</a-tag>
                                                            <a-tag :color="m.is_active ? 'green' : 'red'" class="text-xs">
                                                                {{ m.is_active ? 'Active' : 'Inactive' }}
                                                            </a-tag>
                                                        </div>
                                                    </div>
                                                    <a-button size="small" type="link" class="text-orange-600 shrink-0" @click="linkSimilarMatch(m)">
                                                        Link this
                                                    </a-button>
                                                </div>
                                            </template>
                                        </a-alert>
                                    </div>
                                </template>
                                <div v-if="similarityChecking" class="col-span-2 text-xs text-gray-400 mb-1 flex items-center gap-1">
                                    <a-spin size="small" /> Checking for similar beneficiaries...
                                </div>
                            </div>
                            <div class="flex gap-2 justify-end mt-1">
                                <a-button @click="showFamilyForm = false; resetFamilyForm()">Cancel</a-button>
                                <a-button type="primary" :loading="familyFormLoading" @click="submitFamilyForm">
                                    {{ editingFamily ? 'Update' : 'Save' }}
                                </a-button>
                            </div>
                        </a-card>

                        <a-table
                            :data-source="detailFamily"
                            :columns="familyColumns"
                            :pagination="false"
                            row-key="id"
                            size="small"
                        >
                            <template #bodyCell="{ column, record }">
                                <template v-if="column.key === 'name'">
                                    <div class="font-medium text-xs">{{ record.last_name }}, {{ record.first_name }} {{ record.middle_name }}</div>
                                    <div class="flex gap-1 mt-0.5 flex-wrap">
                                        <a-tag v-if="record.is_pwd" color="blue" class="text-xs">PWD</a-tag>
                                        <a-tag v-if="record.is_senior" color="purple" class="text-xs">Senior</a-tag>
                                        <a-tag v-if="record.civil_status" color="default" class="text-xs">{{ record.civil_status }}</a-tag>
                                    </div>
                                </template>
                                <template v-else-if="column.key === 'dob'">
                                    <span class="text-xs">{{ formatDate(record.date_of_birth) || '—' }}</span>
                                </template>
                                <template v-else-if="column.key === 'crossmatch'">
                                    <a-tooltip v-if="record.linked_beneficiary" :title="`Beneficiary: ${record.linked_beneficiary.beneficiary_code}`">
                                        <a-tag color="orange" class="text-xs cursor-pointer">
                                            <WarningOutlined /> {{ record.linked_beneficiary.name }}
                                        </a-tag>
                                    </a-tooltip>
                                    <span v-else class="text-xs text-gray-400">—</span>
                                </template>
                                <template v-else-if="column.key === 'actions'">
                                    <a-space>
                                        <a-button size="small" @click="openFamilyEdit(record)"><EditOutlined /></a-button>
                                        <a-button size="small" danger @click="deleteFamilyMember(record)"><DeleteOutlined /></a-button>
                                    </a-space>
                                </template>
                            </template>
                        </a-table>
                        <a-empty v-if="detailFamily.length === 0 && !showFamilyForm" description="No family members recorded yet." class="mt-6" />
                    </a-tab-pane>
                </a-tabs>
            </a-spin>
        </a-drawer>
    </AppLayout>
</template>
