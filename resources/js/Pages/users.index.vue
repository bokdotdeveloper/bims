<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import Table from '@/Components/Table.vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import { PlusOutlined, EditOutlined, DeleteOutlined } from '@ant-design/icons-vue';
import { Modal } from 'ant-design-vue';
import { useFlashMessages } from '@/composables/useFlashMessages';

interface RoleRef {
    id: string;
    name: string;
}

interface UserRow {
    id: string;
    name: string;
    email: string;
    roles?: RoleRef[];
}

const props = defineProps<{
    users: {
        data: UserRow[];
        current_page: number;
        per_page: number;
        total: number;
    };
    availableRoles: RoleRef[];
    filters: { search?: string };
}>();

useFlashMessages();

const page = usePage();
const authUserId = computed(() => (page.props.auth as { user?: { id: string } })?.user?.id ?? '');

const search = ref(props.filters?.search ?? '');
let searchTimeout: ReturnType<typeof setTimeout>;
watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(route('users.index'), { search: search.value }, { preserveState: true, replace: true });
    }, 400);
});

const pagination = computed(() => ({
    current: props.users.current_page,
    pageSize: props.users.per_page,
    total: props.users.total,
    showSizeChanger: true,
    pageSizeOptions: ['10', '25', '50'],
}));

const handleTableChange = (pag: { current: number; pageSize: number }) => {
    router.get(route('users.index'), {
        search: search.value,
        page: pag.current,
        per_page: pag.pageSize,
    }, { preserveState: true, replace: true });
};

const columns = [
    { title: 'Name', dataIndex: 'name', key: 'name', width: 200 },
    { title: 'Email', dataIndex: 'email', key: 'email', width: 260 },
    { title: 'Roles', key: 'roles', width: 280 },
    { title: '', key: 'action', align: 'center' as const, width: 110 },
];

const showModal = ref(false);
const editing = ref<UserRow | null>(null);

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    roles: [] as string[],
});

const openCreate = () => {
    editing.value = null;
    form.reset();
    form.clearErrors();
    form.roles = [];
    showModal.value = true;
};

const openEdit = (record: UserRow) => {
    editing.value = record;
    form.reset();
    form.clearErrors();
    form.name = record.name;
    form.email = record.email;
    form.password = '';
    form.password_confirmation = '';
    form.roles = (record.roles ?? []).map((r) => r.id);
    showModal.value = true;
};

const handleSubmit = () => {
    if (editing.value) {
        form.put(route('users.update', editing.value.id), {
            onSuccess: () => { showModal.value = false; },
        });
    } else {
        form.post(route('users.store'), {
            onSuccess: () => { showModal.value = false; },
        });
    }
};

const handleDelete = (record: UserRow) => {
    Modal.confirm({
        title: 'Delete user',
        content: `Remove ${record.name} from the system?`,
        okType: 'danger',
        onOk() {
            router.delete(route('users.destroy', record.id));
        },
    });
};
</script>

<template>
    <AppLayout title="Users">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Users</h2>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-4">
                    <div class="flex justify-between items-center mb-4 flex-wrap gap-2">
                        <a-input-search
                            v-model:value="search"
                            placeholder="Search name or email..."
                            style="width: 280px"
                            allow-clear
                        />
                        <a-button type="primary" @click="openCreate">
                            <template #icon><PlusOutlined /></template>
                            Add User
                        </a-button>
                    </div>

                    <Table
                        :columns="columns"
                        :pagination="pagination"
                        @change="handleTableChange"
                        :data-source="users.data"
                        row-key="id"
                    >
                        <template #bodyCell="{ column, record }">
                            <template v-if="column.dataIndex === 'name' || column.dataIndex === 'email'">
                                {{ record[column.dataIndex as keyof UserRow] }}
                            </template>
                            <template v-else-if="column.key === 'roles'">
                                <a-space wrap>
                                    <a-tag v-for="r in (record.roles ?? [])" :key="r.id" color="blue">{{ r.name }}</a-tag>
                                    <span v-if="!(record.roles?.length)" class="text-gray-400 text-xs">No roles</span>
                                </a-space>
                            </template>
                            <template v-else-if="column.key === 'action'">
                                <a-space>
                                    <a-button size="small" @click="openEdit(record)"><EditOutlined /></a-button>
                                    <a-button
                                        v-if="record.id !== authUserId"
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
            :title="editing ? 'Edit User' : 'Add User'"
            :confirm-loading="form.processing"
            ok-text="Save"
            width="520px"
            @ok="handleSubmit"
        >
            <a-form layout="vertical" class="mt-2">
                <a-form-item label="Name" required>
                    <a-input v-model:value="form.name" />
                    <div class="text-red-500 text-xs" v-if="form.errors.name">{{ form.errors.name }}</div>
                </a-form-item>
                <a-form-item label="Email" required>
                    <a-input v-model:value="form.email" type="email" autocomplete="off" />
                    <div class="text-red-500 text-xs" v-if="form.errors.email">{{ form.errors.email }}</div>
                </a-form-item>
                <a-form-item :label="editing ? 'New password (optional)' : 'Password'" :required="!editing">
                    <a-input-password v-model:value="form.password" autocomplete="new-password" />
                    <div class="text-red-500 text-xs" v-if="form.errors.password">{{ form.errors.password }}</div>
                </a-form-item>
                <a-form-item label="Confirm password" :required="!editing">
                    <a-input-password v-model:value="form.password_confirmation" autocomplete="new-password" />
                </a-form-item>
                <a-form-item label="Roles">
                    <a-select
                        v-model:value="form.roles"
                        mode="multiple"
                        placeholder="Select roles"
                        style="width: 100%"
                        :options="availableRoles.map(r => ({ value: r.id, label: r.name }))"
                        allow-clear
                    />
                    <div class="text-red-500 text-xs" v-if="form.errors.roles">{{ form.errors.roles }}</div>
                </a-form-item>
            </a-form>
        </a-modal>
    </AppLayout>
</template>
