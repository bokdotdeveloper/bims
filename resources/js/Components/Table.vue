<script setup lang="ts">
import { onMounted, onUnmounted, ref } from 'vue';

interface Props {
    columns: any[];
    loading?: boolean;
    dataSource?: any[];
    rowKey?: string;
    pagination: {
        pageSize: number;
        total: number;
        current: number;
    };
}

const props = defineProps<Props>();
const emit = defineEmits<{
    (e: 'change', pagination: any): void;
}>();

const tableScrollHeight = ref(window.innerHeight - 280);
const updateScrollHeight = () => {
    tableScrollHeight.value = window.innerHeight - 280;
};

onMounted(() => {
    updateScrollHeight();
    window.addEventListener('resize', updateScrollHeight);
});

onUnmounted(() => {
    window.removeEventListener('resize', updateScrollHeight);
});
</script>

<template>
    <a-table
        :columns="columns"
        :loading="loading"
        :data-source="dataSource"
        :row-key="rowKey"
        :scroll="{ y: tableScrollHeight }"
        size="middle"
        :pagination="{
            ...pagination,
            showSizeChanger: true,
            showQuickJumper: true,
            hideOnSinglePage: true,
            showTotal: (total : number) => `Total ${total} items`,
            pageSizeOptions: ['10', '20', '50', '100'],
        }"
        @change="(pag: any) => emit('change', pag)"
        class="font-light"
    >
        <template #bodyCell="{ column, record }">
            <slot name="bodyCell" :column="column" :record="record" />
        </template>
    </a-table>
</template>
