<script setup lang="ts">
import { onMounted, onUnmounted, ref, computed } from 'vue';
import { useMediaQuery } from '@vueuse/core';

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

/** Horizontal scroll + sticky behavior on phones; vertical scroll on md+ */
const isMdUp = useMediaQuery('(min-width: 768px)');
const isSmUp = useMediaQuery('(min-width: 640px)');

const tableScrollHeight = ref(typeof window !== 'undefined' ? window.innerHeight - 280 : 480);
const updateScrollHeight = () => {
    tableScrollHeight.value = window.innerHeight - 280;
};

const tableScroll = computed(() => {
    const scroll: { x?: number | string; y?: number } = { x: 'max-content' };
    if (isMdUp.value) {
        scroll.y = tableScrollHeight.value;
    }
    return scroll;
});

const resolvedPagination = computed(() => ({
    ...props.pagination,
    showSizeChanger: isSmUp.value,
    showQuickJumper: isSmUp.value,
    hideOnSinglePage: true,
    showTotal: isSmUp.value ? (total: number) => `Total ${total} items` : undefined,
    pageSizeOptions: ['10', '20', '50', '100'],
    simple: !isSmUp.value,
}));

onMounted(() => {
    updateScrollHeight();
    window.addEventListener('resize', updateScrollHeight);
});

onUnmounted(() => {
    window.removeEventListener('resize', updateScrollHeight);
});
</script>

<template>
    <div class="w-full min-w-0 overflow-x-auto rounded-lg ant-table-responsive-scroll">
        <a-table
            :columns="columns"
            :loading="loading"
            :data-source="dataSource"
            :row-key="rowKey"
            :scroll="tableScroll"
            size="middle"
            :pagination="resolvedPagination"
            @change="(pag: any) => emit('change', pag)"
            class="font-light min-w-0"
        >
            <template #bodyCell="{ column, record }">
                <slot name="bodyCell" :column="column" :record="record" />
            </template>
        </a-table>
    </div>
</template>
