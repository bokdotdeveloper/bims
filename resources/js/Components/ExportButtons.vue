<script setup lang="ts">
import { FilePdfOutlined, FileExcelOutlined } from '@ant-design/icons-vue';

const props = defineProps<{
    pdfRoute: string;
    excelRoute: string;
    params?: Record<string, any>;
}>();

const buildUrl = (base: string) => {
    const url = new URL(base, window.location.origin);
    if (props.params) {
        Object.entries(props.params).forEach(([k, v]) => {
            if (v !== undefined && v !== null && v !== '') url.searchParams.set(k, String(v));
        });
    }
    return url.toString();
};
</script>

<template>
    <a-space>
        <a-tooltip title="Download PDF">
            <a :href="buildUrl(pdfRoute)" target="_blank" style="text-decoration:none">
                <a-button danger style="display:inline-flex;align-items:center;gap:4px;">
                    <FilePdfOutlined />
                    <span>PDF</span>
                </a-button>
            </a>
        </a-tooltip>
        <a-tooltip title="Download Excel">
            <a :href="buildUrl(excelRoute)" target="_blank" style="text-decoration:none">
                <a-button style="display:inline-flex;align-items:center;gap:4px;color:#166534;border-color:#166534">
                    <FileExcelOutlined />
                    <span>Excel</span>
                </a-button>
            </a>
        </a-tooltip>
    </a-space>
</template>
