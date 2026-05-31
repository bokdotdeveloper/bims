<script setup lang="ts">
import { onMounted, onUnmounted, ref, computed, watch, nextTick } from 'vue';
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
    /** Pin first / action columns when the table scrolls horizontally */
    stickyColumns?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    stickyColumns: true,
});

const emit = defineEmits<{
    (e: 'change', pagination: any): void;
}>();

const isMdUp = useMediaQuery('(min-width: 768px)');
const isSmUp = useMediaQuery('(min-width: 640px)');

const DEFAULT_COL_WIDTH = 150;
const ACTION_COLUMN_KEYS = new Set(['actions', 'remove', 'details']);

const isActionColumn = (col: any) => {
    const key = String(col.key ?? col.dataIndex ?? '');
    const title = String(col.title ?? '');

    if (ACTION_COLUMN_KEYS.has(key)) return true;
    if (key === 'action' && (title === '' || title.toLowerCase() === 'actions')) return true;

    return false;
};

const scrollX = computed(() =>
    props.columns.reduce((sum, col) => sum + Number(col.width ?? DEFAULT_COL_WIDTH), 0),
);

const resolvedColumns = computed(() => {
    if (!props.stickyColumns) return props.columns;

    return props.columns.map((col, index) => {
        const width = col.width ?? (index === 0 || isActionColumn(col) ? 120 : DEFAULT_COL_WIDTH);
        const patched = { ...col, width };

        if (index === 0) {
            return { ...patched, fixed: 'left' as const };
        }

        if (isActionColumn(col)) {
            return { ...patched, fixed: 'right' as const };
        }

        return patched;
    });
});

const tableScrollHeight = ref(typeof window !== 'undefined' ? window.innerHeight - 280 : 480);
const updateScrollHeight = () => {
    tableScrollHeight.value = window.innerHeight - 280;
};

/** Vertical scroll in viewport on md+; horizontal scroll handled by Ant table */
const tableScroll = computed(() => {
    const scroll: { x: number; y?: number } = { x: scrollX.value };
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

const scrollWrapper = ref<HTMLElement | null>(null);
const canScrollRight = ref(false);
let scrollContent: HTMLElement | null = null;

const updateScrollHint = () => {
    if (!scrollContent) return;
    canScrollRight.value =
        scrollContent.scrollWidth - scrollContent.clientWidth - scrollContent.scrollLeft > 4;
};

const bindScrollContent = async () => {
    await nextTick();
    scrollContent = scrollWrapper.value?.querySelector('.ant-table-content') ?? null;
    scrollContent?.removeEventListener('scroll', updateScrollHint);
    scrollContent?.addEventListener('scroll', updateScrollHint, { passive: true });
    updateScrollHint();
};

onMounted(() => {
    updateScrollHeight();
    window.addEventListener('resize', updateScrollHeight);
    window.addEventListener('resize', updateScrollHint);
    void bindScrollContent();
});

onUnmounted(() => {
    window.removeEventListener('resize', updateScrollHeight);
    window.removeEventListener('resize', updateScrollHint);
    scrollContent?.removeEventListener('scroll', updateScrollHint);
});

watch([() => props.dataSource, () => props.columns, isMdUp], () => {
    void bindScrollContent();
});
</script>

<template>
    <div
        ref="scrollWrapper"
        class="ant-table-responsive-scroll relative w-full min-w-0 rounded-lg"
        :class="{ 'ant-table-responsive-scroll--hint': canScrollRight && !isMdUp }"
    >
        <a-table
            :columns="resolvedColumns"
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
        <p v-if="canScrollRight && !isMdUp" class="mt-1.5 text-center text-xs text-gray-400 dark:text-gray-500 sm:hidden">
            Swipe sideways to see more columns
        </p>
    </div>
</template>
