<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import Table from '@/Components/Table.vue';
import { router, useForm } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import { PlusOutlined, EditOutlined, DeleteOutlined } from '@ant-design/icons-vue';
import { Modal } from 'ant-design-vue';
import { useFlashMessages } from '@/composables/useFlashMessages';

interface PermissionRef {
    id: string;
    name: string;
}

interface RoleRow {
    id: string;
    name: string;
    users_count: number;
    permissions?: PermissionRef[];
}

const props = defineProps<{
    roles: {
        data: RoleRow[];
        current_page: number;
        per_page: number;
        total: number;
    };
    allPermissions: string[];
    filters: { search?: string };
}>();

useFlashMessages();

const search = ref(props.filters?.search ?? '');
let searchTimeout: ReturnType<typeof setTimeout>;
watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(route('roles.index'), { search: search.value }, { preserveState: true, replace: true });
    }, 400);
});

const pagination = computed(() => ({
    current: props.roles.current_page,
    pageSize: props.roles.per_page,
    total: props.roles.total,
    showSizeChanger: true,
    pageSizeOptions: ['10', '25', '50'],
}));

const handleTableChange = (pag: { current: number; pageSize: number }) => {
    router.get(route('roles.index'), {
        search: search.value,
        page: pag.current,
        per_page: pag.pageSize,
    }, { preserveState: true, replace: true });
};

const columns = [
    { title: 'Role', dataIndex: 'name', key: 'name', width: 180 },
    { title: 'Users', key: 'users_count', width: 90, align: 'center' as const },
    { title: 'Permissions', key: 'permissions', ellipsis: true },
    { title: '', key: 'action', align: 'center' as const, width: 100 },
];

const showModal = ref(false);
const editing = ref<RoleRow | null>(null);
const permissionSearch = ref('');

const form = useForm({
    name: '',
    permissions: [] as string[],
});

const openCreate = () => {
    editing.value = null;
    permissionSearch.value = '';
    form.reset();
    form.clearErrors();
    form.permissions = [];
    showModal.value = true;
};

const openEdit = (record: RoleRow) => {
    editing.value = record;
    permissionSearch.value = '';
    form.reset();
    form.clearErrors();
    form.name = record.name;
    form.permissions = (record.permissions ?? []).map((p) => p.name);
    showModal.value = true;
};

const handleSubmit = () => {
    if (editing.value) {
        form.put(route('roles.update', editing.value.id), {
            onSuccess: () => { showModal.value = false; },
        });
    } else {
        form.post(route('roles.store'), {
            onSuccess: () => { showModal.value = false; },
        });
    }
};

const handleDelete = (record: RoleRow) => {
    Modal.confirm({
        title: 'Delete role',
        content: `Delete role "${record.name}"? Users must be removed from this role first.`,
        okType: 'danger',
        onOk() {
            router.delete(route('roles.destroy', record.id));
        },
    });
};

interface PermissionGroup {
    key: string;
    title: string;
    permissions: string[];
}

/** Ordered sections; any permission from the API not listed here appears under "Other". */
const GROUP_DEFINITIONS: { title: string; permissions: string[] }[] = [
    { title: 'Dashboard', permissions: ['dashboard.access'] },
    { title: 'Beneficiaries', permissions: ['beneficiaries.view', 'beneficiaries.manage'] },
    { title: 'Projects', permissions: ['projects.view', 'projects.manage'] },
    { title: 'Trainings', permissions: ['trainings.view', 'trainings.manage'] },
    { title: 'Assistance records', permissions: ['assistance.view', 'assistance.manage'] },
    { title: 'Beneficiary groups', permissions: ['groups.view', 'groups.manage'] },
    { title: 'Reports', permissions: ['reports.export'] },
    { title: 'Audit logs', permissions: ['audit_logs.view'] },
    { title: 'Notifications', permissions: ['notifications.access'] },
    { title: 'Administration', permissions: ['users.manage', 'roles.manage'] },
];

const permissionGroups = computed((): PermissionGroup[] => {
    const all = props.allPermissions ?? [];
    const inGroup = new Set<string>();
    const groups: PermissionGroup[] = [];

    GROUP_DEFINITIONS.forEach((def, i) => {
        const perms = def.permissions.filter((p) => all.includes(p));
        perms.forEach((p) => inGroup.add(p));
        if (perms.length > 0) {
            groups.push({ key: `group-${i}`, title: def.title, permissions: perms });
        }
    });

    const others = all.filter((p) => !inGroup.has(p)).sort((a, b) => a.localeCompare(b));
    if (others.length > 0) {
        groups.push({ key: 'group-other', title: 'Other', permissions: others });
    }

    return groups;
});

watch(showModal, (open) => {
    if (!open) {
        permissionSearch.value = '';
    }
});

const formatPermissionLabel = (perm: string) => {
    const [resource, action] = perm.split('.');
    const res = (resource ?? perm).replace(/_/g, ' ');
    const act = (action ?? '').replace(/_/g, ' ');
    return act ? `${res} — ${act}` : res;
};

const permissionSearchTrimmed = computed(() => permissionSearch.value.trim().toLowerCase());

const permissionMatchesSearch = (perm: string, groupTitle: string) => {
    const q = permissionSearchTrimmed.value;
    if (!q) {
        return true;
    }
    const label = formatPermissionLabel(perm).toLowerCase();
    const blob = [perm, label, groupTitle, perm.replace(/[._]/g, ' ')].join(' ').toLowerCase();
    return blob.includes(q);
};

/** Groups / permissions visible under the current permission search (full groups when query empty). */
const filteredPermissionGroups = computed((): PermissionGroup[] => {
    if (!permissionSearchTrimmed.value) {
        return permissionGroups.value;
    }
    return permissionGroups.value
        .map((g) => ({
            ...g,
            permissions: g.permissions.filter((p) => permissionMatchesSearch(p, g.title)),
        }))
        .filter((g) => g.permissions.length > 0);
});

const filteredPermissionCount = computed(() =>
    filteredPermissionGroups.value.reduce((n, g) => n + g.permissions.length, 0),
);

const allPermissionsSelected = computed(
    () =>
        props.allPermissions.length > 0
        && props.allPermissions.every((p) => form.permissions.includes(p)),
);

const togglePermission = (perm: string, checked: boolean) => {
    const set = new Set(form.permissions);
    if (checked) {
        set.add(perm);
    } else {
        set.delete(perm);
    }
    form.permissions = [...set];
};

const toggleSelectAllGlobal = () => {
    if (allPermissionsSelected.value) {
        form.permissions = [];
    } else {
        form.permissions = [...props.allPermissions];
    }
};

const groupFullySelected = (group: PermissionGroup) =>
    group.permissions.length > 0 && group.permissions.every((p) => form.permissions.includes(p));

const toggleSelectGroup = (group: PermissionGroup) => {
    const select = !groupFullySelected(group);
    const set = new Set(form.permissions);
    for (const p of group.permissions) {
        if (select) {
            set.add(p);
        } else {
            set.delete(p);
        }
    }
    form.permissions = [...set];
};

const isProtectedRole = (name: string) => name === 'Super Admin';
</script>

<template>
    <AppLayout title="Roles">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Roles & permissions</h2>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-4">
                    <div class="flex justify-between items-center mb-4 flex-wrap gap-2">
                        <a-input-search
                            v-model:value="search"
                            placeholder="Search roles..."
                            style="width: 280px"
                            allow-clear
                        />
                        <a-button type="primary" @click="openCreate">
                            <template #icon><PlusOutlined /></template>
                            Add Role
                        </a-button>
                    </div>

                    <Table
                        :columns="columns"
                        :pagination="pagination"
                        @change="handleTableChange"
                        :data-source="roles.data"
                        row-key="id"
                    >
                        <template #bodyCell="{ column, record }">
                            <template v-if="column.key === 'permissions'">
                                <span class="text-xs text-gray-600 dark:text-gray-300">
                                    {{ (record.permissions ?? []).map(p => p.name).join(', ') || '—' }}
                                </span>
                            </template>
                            <template v-else-if="column.key === 'users_count'">
                                {{ record.users_count }}
                            </template>
                            <template v-else-if="column.key === 'action'">
                                <a-space>
                                    <a-button size="small" @click="openEdit(record)"><EditOutlined /></a-button>
                                    <a-button
                                        v-if="!isProtectedRole(record.name) && record.users_count === 0"
                                        size="small"
                                        danger
                                        @click="handleDelete(record)"
                                    >
                                        <DeleteOutlined />
                                    </a-button>
                                </a-space>
                            </template>
                        </template>
                    </Table>
                </div>
            </div>
        </div>

        <a-modal
            v-model:open="showModal"
            :title="editing ? 'Edit Role' : 'Add Role'"
            :confirm-loading="form.processing"
            ok-text="Save"
            width="720px"
            @ok="handleSubmit"
        >
            <a-form layout="vertical" class="mt-2">
                <a-form-item label="Role name" required>
                    <a-input
                        v-model:value="form.name"
                        :disabled="!!editing && isProtectedRole(editing.name)"
                    />
                    <div class="text-red-500 text-xs" v-if="form.errors.name">{{ form.errors.name }}</div>
                </a-form-item>
                <a-form-item label="Permissions">
                    <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                        <span class="text-xs text-gray-500 dark:text-gray-400">
                            {{ form.permissions.length }} of {{ allPermissions.length }} selected
                            <template v-if="permissionSearchTrimmed">
                                · showing {{ filteredPermissionCount }} match{{ filteredPermissionCount === 1 ? '' : 'es' }}
                            </template>
                        </span>
                        <a-button type="link" size="small" class="!px-0 h-auto" @click="toggleSelectAllGlobal">
                            {{ allPermissionsSelected ? 'Deselect all' : 'Select all' }}
                        </a-button>
                    </div>
                    <a-input-search
                        v-model:value="permissionSearch"
                        placeholder="Search permissions or groups..."
                        allow-clear
                        class="mb-2"
                    />
                    <div
                        class="max-h-[min(52vh,420px)] overflow-y-auto rounded-md border border-gray-200 dark:border-gray-600 px-3 py-2 space-y-4"
                    >
                        <p
                            v-if="permissionSearchTrimmed && filteredPermissionCount === 0"
                            class="text-sm text-gray-500 dark:text-gray-400 py-6 text-center"
                        >
                            No permissions match “{{ permissionSearch.trim() }}”.
                        </p>
                        <div v-for="group in filteredPermissionGroups" :key="group.key">
                            <div class="flex flex-wrap items-center justify-between gap-2 mb-2 pb-1 border-b border-gray-100 dark:border-gray-700">
                                <span class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ group.title }}</span>
                                <a-button type="link" size="small" class="!px-0 h-auto" @click="toggleSelectGroup(group)">
                                    {{ groupFullySelected(group) ? 'Deselect group' : 'Select group' }}
                                </a-button>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-1">
                                <a-checkbox
                                    v-for="perm in group.permissions"
                                    :key="perm"
                                    :checked="form.permissions.includes(perm)"
                                    class="!items-start"
                                    @update:checked="(checked: boolean) => togglePermission(perm, checked)"
                                >
                                    <span class="whitespace-normal leading-snug">{{ formatPermissionLabel(perm) }}</span>
                                    <span class="block text-[11px] text-gray-400 dark:text-gray-500 font-mono">{{ perm }}</span>
                                </a-checkbox>
                            </div>
                        </div>
                    </div>
                    <div class="text-red-500 text-xs mt-1" v-if="form.errors.permissions">{{ form.errors.permissions }}</div>
                </a-form-item>
            </a-form>
        </a-modal>
    </AppLayout>
</template>
