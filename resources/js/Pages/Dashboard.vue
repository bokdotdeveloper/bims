<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { computed, ref, onMounted, onUnmounted, nextTick } from 'vue';
import {
    UserOutlined, ProjectOutlined, ReadOutlined,
    DollarOutlined, TeamOutlined, RiseOutlined,
} from '@ant-design/icons-vue';
import { formatDate } from '@/composables/useDateFormat';

interface Stats {
    totalBeneficiaries: number;
    activeBeneficiaries: number;
    totalProjects: number;
    activeProjects: number;
    totalTrainings: number;
    totalAssistance: number;
    totalAmountReleased: number;
    totalGroups: number;
}

interface RecentRecord {
    recipient: string;
    type: string;
    assistance_type: string;
    amount: number | null;
    date_released: string | null;
    project: string | null;
}

const props = defineProps<{
    stats: Stats;
    charts: {
        assistanceByType: { assistance_type: string; count: number; total: number }[];
        monthlyAssistance: { month: string; total: number }[];
        beneficiariesBySex: { sex: string; count: number }[];
        beneficiariesByCivilStatus: { civil_status: string; count: number }[];
        topBarangays: { barangay: string; count: number }[];
    };
    recentAssistance: RecentRecord[];
}>();

const formatPeso = (val: number) =>
    '₱ ' + val.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

function readDarkFromDocument(): boolean {
    if (typeof document === 'undefined') {
        return false;
    }

    return document.documentElement.classList.contains('dark');
}

const isDark = ref(readDarkFromDocument());

let observer: MutationObserver | null = null;
onMounted(() => {
    // Re-sync after layout (theme is also initialized in app.js before mount).
    nextTick(() => {
        isDark.value = readDarkFromDocument();
    });

    observer = new MutationObserver(() => {
        isDark.value = readDarkFromDocument();
    });
    observer.observe(document.documentElement, { attributeFilter: ['class'] });
});
onUnmounted(() => observer?.disconnect());

const chartTheme = computed(() => ({
    mode: isDark.value ? 'dark' : 'light',
}));

/** Match `dark:bg-gray-800` cards so charts do not flash white on reload */
const chartBackground = computed(() => (isDark.value ? '#1f2937' : '#ffffff'));

const chartAxisColor = computed(() => (isDark.value ? '#9ca3af' : '#6b7280'));

const chartKey = computed(() => (isDark.value ? 'dark' : 'light'));

// Monthly line chart
const monthlyChartOptions = computed(() => ({
    chart: {
        type: 'area',
        toolbar: { show: false },
        sparkline: { enabled: false },
        background: chartBackground.value,
        foreColor: chartAxisColor.value,
    },
    theme: chartTheme.value,
    stroke: { curve: 'smooth', width: 2 },
    fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.05 } },
    xaxis: {
        categories: props.charts.monthlyAssistance.map(m => m.month),
        labels: { style: { colors: chartAxisColor.value, fontSize: '10px' } },
    },
    yaxis: {
        labels: {
            formatter: (v: number) => '₱' + (v / 1000).toFixed(0) + 'k',
            style: { colors: chartAxisColor.value, fontSize: '10px' },
        },
    },
    tooltip: {
        theme: isDark.value ? 'dark' : 'light',
        y: { formatter: (v: number) => formatPeso(v) },
    },
    colors: [isDark.value ? '#60a5fa' : '#1e40af'],
    dataLabels: { enabled: false },
    grid: { borderColor: isDark.value ? '#374151' : '#f3f4f6' },
}));

const monthlySeries = computed(() => [{
    name: 'Amount Released',
    data: props.charts.monthlyAssistance.map(m => parseFloat(m.total as any) || 0),
}]);

// Assistance by type bar chart
const byTypeOptions = computed(() => ({
    chart: { type: 'bar', toolbar: { show: false }, background: chartBackground.value, foreColor: chartAxisColor.value },
    theme: chartTheme.value,
    plotOptions: { bar: { horizontal: true, borderRadius: 4 } },
    xaxis: {
        categories: props.charts.assistanceByType.map(a => a.assistance_type),
        labels: { style: { colors: chartAxisColor.value, fontSize: '10px' } },
    },
    yaxis: { labels: { style: { colors: chartAxisColor.value, fontSize: '10px' } } },
    colors: ['#0284c7'],
    dataLabels: { enabled: false },
    tooltip: {
        theme: isDark.value ? 'dark' : 'light',
        y: { formatter: (v: number) => formatPeso(v) },
    },
    grid: { borderColor: isDark.value ? '#374151' : '#f3f4f6' },
}));

const byTypeSeries = computed(() => [{
    name: 'Total Released',
    data: props.charts.assistanceByType.map(a => parseFloat(a.total as any) || 0),
}]);

// Sex donut
const sexChartOptions = computed(() => ({
    chart: { type: 'donut', background: chartBackground.value, foreColor: chartAxisColor.value },
    theme: chartTheme.value,
    labels: props.charts.beneficiariesBySex.map(b => b.sex),
    colors: ['#3b82f6', '#ec4899'],
    legend: {
        position: 'bottom',
        fontSize: '11px',
        labels: { colors: chartAxisColor.value },
    },
    dataLabels: { style: { fontSize: '10px' } },
    plotOptions: { pie: { donut: { size: '60%' } } },
    tooltip: { theme: isDark.value ? 'dark' : 'light' },
}));

const sexSeries = computed(() => props.charts.beneficiariesBySex.map(b => b.count));

// Civil status donut
const civilChartOptions = computed(() => ({
    chart: { type: 'donut', background: chartBackground.value, foreColor: chartAxisColor.value },
    theme: chartTheme.value,
    labels: props.charts.beneficiariesByCivilStatus.map(b => b.civil_status),
    colors: ['#10b981', '#f59e0b', '#6366f1', '#ef4444'],
    legend: {
        position: 'bottom',
        fontSize: '11px',
        labels: { colors: chartAxisColor.value },
    },
    dataLabels: { style: { fontSize: '10px' } },
    plotOptions: { pie: { donut: { size: '60%' } } },
    tooltip: { theme: isDark.value ? 'dark' : 'light' },
}));

const civilSeries = computed(() => props.charts.beneficiariesByCivilStatus.map(b => b.count));

// Top barangays
const barangayOptions = computed(() => ({
    chart: { type: 'bar', toolbar: { show: false }, background: chartBackground.value, foreColor: chartAxisColor.value },
    theme: chartTheme.value,
    plotOptions: { bar: { horizontal: false, borderRadius: 4, columnWidth: '55%' } },
    xaxis: {
        categories: props.charts.topBarangays.map(b => b.barangay),
        labels: { style: { colors: chartAxisColor.value, fontSize: '9px' } },
    },
    yaxis: { labels: { style: { colors: chartAxisColor.value, fontSize: '10px' } } },
    colors: ['#7c3aed'],
    dataLabels: { enabled: false },
    tooltip: { theme: isDark.value ? 'dark' : 'light' },
    grid: { borderColor: isDark.value ? '#374151' : '#f3f4f6' },
}));

const barangaySeries = computed(() => [{
    name: 'Beneficiaries',
    data: props.charts.topBarangays.map(b => b.count),
}]);

const recentColumns = [
    { title: 'Recipient', key: 'recipient' },
    { title: 'Assistance Type', dataIndex: 'assistance_type', key: 'assistance_type' },
    { title: 'Amount', key: 'amount', width: 120, align: 'right' as const },
    { title: 'Date', key: 'date_released', width: 120 },
    { title: 'Project', key: 'project' },
];
</script>

<template>
    <AppLayout title="Dashboard">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Dashboard</h2>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto space-y-6 px-3 sm:px-6 lg:px-8">

                <!-- Summary Cards -->
                <div class="grid grid-cols-1 min-[420px]:grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4">
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center text-blue-600 dark:text-blue-400 text-lg leading-none"><UserOutlined /></div>
                        <div>
                            <div class="text-2xl font-bold text-blue-700 dark:text-blue-400">{{ stats.totalBeneficiaries }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">Beneficiaries <span class="text-green-600 dark:text-green-400">({{ stats.activeBeneficiaries }} active)</span></div>
                        </div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-indigo-100 dark:bg-indigo-900 flex items-center justify-center text-indigo-600 dark:text-indigo-400 text-lg leading-none"><ProjectOutlined /></div>
                        <div>
                            <div class="text-2xl font-bold text-indigo-700 dark:text-indigo-400">{{ stats.totalProjects }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">Projects <span class="text-green-600 dark:text-green-400">({{ stats.activeProjects }} active)</span></div>
                        </div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-cyan-100 dark:bg-cyan-900 flex items-center justify-center text-cyan-600 dark:text-cyan-400 text-lg leading-none"><ReadOutlined /></div>
                        <div>
                            <div class="text-2xl font-bold text-cyan-700 dark:text-cyan-400">{{ stats.totalTrainings }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">Trainings Conducted</div>
                        </div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-purple-100 dark:bg-purple-900 flex items-center justify-center text-purple-600 dark:text-purple-400 text-lg leading-none"><TeamOutlined /></div>
                        <div>
                            <div class="text-2xl font-bold text-purple-700 dark:text-purple-400">{{ stats.totalGroups }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">Beneficiary Groups</div>
                        </div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 flex items-center gap-3 md:col-span-2">
                        <div class="w-10 h-10 rounded-full bg-green-100 dark:bg-green-900 flex items-center justify-center text-green-600 dark:text-green-400 text-lg leading-none"><DollarOutlined /></div>
                        <div>
                            <div class="text-2xl font-bold text-green-700 dark:text-green-400">{{ (stats.totalAmountReleased).toLocaleString('en-PH', { style: 'currency', currency: 'PHP' }) }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">Total Amount Released ({{ stats.totalAssistance }} records)</div>
                        </div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 flex items-center gap-3 md:col-span-2">
                        <div class="w-10 h-10 rounded-full bg-orange-100 dark:bg-orange-900 flex items-center justify-center text-orange-600 dark:text-orange-400 text-lg leading-none"><RiseOutlined /></div>
                        <div>
                            <div class="text-2xl font-bold text-orange-700 dark:text-orange-400">
                                {{ stats.totalBeneficiaries > 0 ? ((stats.activeBeneficiaries / stats.totalBeneficiaries) * 100).toFixed(1) : 0 }}%
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">Beneficiary Activation Rate</div>
                        </div>
                    </div>
                </div>

                <!-- Monthly Assistance Chart -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4">
                    <div class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Monthly Assistance Released (Last 12 Months)</div>
                    <apexchart :key="`monthly-${chartKey}`" type="area" height="200" :options="monthlyChartOptions" :series="monthlySeries" />
                </div>

                <!-- Middle Row: Assistance by Type + Beneficiaries by Sex + Civil Status -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 md:col-span-1">
                        <div class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">By Sex</div>
                        <apexchart :key="`sex-${chartKey}`" type="donut" height="220" :options="sexChartOptions" :series="sexSeries" />
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 md:col-span-1">
                        <div class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">By Civil Status</div>
                        <apexchart :key="`civil-${chartKey}`" type="donut" height="220" :options="civilChartOptions" :series="civilSeries" />
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 md:col-span-1">
                        <div class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Top 5 Barangays</div>
                        <apexchart :key="`barangay-${chartKey}`" type="bar" height="220" :options="barangayOptions" :series="barangaySeries" />
                    </div>
                </div>

                <!-- Assistance by Type + Recent Records -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4">
                        <div class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Assistance by Type (Total Amount)</div>
                        <apexchart :key="`bytype-${chartKey}`" type="bar" height="250" :options="byTypeOptions" :series="byTypeSeries" />
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 min-w-0">
                        <div class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Recent Assistance Records</div>
                        <div class="w-full overflow-x-auto">
                            <a-table
                                :data-source="recentAssistance"
                                :columns="recentColumns"
                                :pagination="false"
                                size="small"
                                row-key="recipient"
                                :scroll="{ x: 'max-content' }"
                                :class="isDark ? 'ant-table-dark' : ''"
                            >
                            <template #bodyCell="{ column, record }">
                                <template v-if="column.key === 'recipient'">
                                    <div class="font-medium text-xs dark:text-gray-200">{{ record.recipient }}</div>
                                    <a-tag :color="record.type === 'individual' ? 'blue' : 'purple'" class="text-xs">{{ record.type }}</a-tag>
                                </template>
                                <template v-else-if="column.key === 'amount'">
                                    <span class="font-mono text-xs dark:text-gray-300">{{ record.amount ? '₱ ' + parseFloat(record.amount).toLocaleString('en-PH', { minimumFractionDigits: 2 }) : '—' }}</span>
                                </template>
                                <template v-else-if="column.key === 'date_released'">
                                    <span class="text-xs dark:text-gray-300">{{ formatDate(record.date_released) }}</span>
                                </template>
                                <template v-else-if="column.key === 'project'">
                                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ record.project ?? '—' }}</span>
                                </template>
                            </template>
                            </a-table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AppLayout>
</template>
