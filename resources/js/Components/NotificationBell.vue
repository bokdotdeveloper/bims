<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { BellOutlined, WarningOutlined, InfoCircleOutlined, CloseOutlined, CheckOutlined } from '@ant-design/icons-vue';
import axios from 'axios';

interface AppNotification {
    id: number;
    type: 'info' | 'warning' | 'danger';
    title: string;
    message: string;
    icon: string | null;
    meta: Record<string, any> | null;
    is_read: boolean;
    created_at: string;
}

const open = ref(false);
const notifications = ref<AppNotification[]>([]);
const unread = ref(0);
const loading = ref(false);

const unreadCount = computed(() => unread.value);

const fetchNotifications = async () => {
    loading.value = true;
    try {
        const res = await axios.get(route('notifications.index'));
        notifications.value = res.data.notifications;
        unread.value = res.data.unread;
    } finally {
        loading.value = false;
    }
};

const markRead = async (n: AppNotification) => {
    if (n.is_read) return;
    await axios.patch(route('notifications.read', { id: n.id }));
    n.is_read = true;
    unread.value = Math.max(0, unread.value - 1);
};

const markAllRead = async () => {
    await axios.patch(route('notifications.read-all'));
    notifications.value.forEach(n => n.is_read = true);
    unread.value = 0;
};

const remove = async (n: AppNotification) => {
    await axios.delete(route('notifications.destroy', { id: n.id }));
    const idx = notifications.value.findIndex(x => x.id === n.id);
    if (idx !== -1) {
        if (!notifications.value[idx].is_read) unread.value = Math.max(0, unread.value - 1);
        notifications.value.splice(idx, 1);
    }
};

const typeColor: Record<string, string> = {
    warning: 'text-orange-500',
    danger:  'text-red-500',
    info:    'text-blue-500',
};

const typeBg: Record<string, string> = {
    warning: 'bg-orange-50 dark:bg-orange-900/20 border-orange-100 dark:border-orange-800',
    danger:  'bg-red-50 dark:bg-red-900/20 border-red-100 dark:border-red-800',
    info:    'bg-blue-50 dark:bg-blue-900/20 border-blue-100 dark:border-blue-800',
};

// Poll every 30s
let pollInterval: ReturnType<typeof setInterval>;
onMounted(() => {
    fetchNotifications();
    pollInterval = setInterval(fetchNotifications, 30_000);
});
onUnmounted(() => clearInterval(pollInterval));
</script>

<template>
    <div class="relative">
        <a-popover
            v-model:open="open"
            trigger="click"
            placement="bottomRight"
            :overlay-style="{ width: '380px' }"
            @open-change="(v: boolean) => { if (v) fetchNotifications(); }"
        >
            <template #title>
                <div class="flex items-center justify-between py-1">
                    <span class="font-semibold text-sm">Notifications</span>
                    <a-button
                        v-if="unreadCount > 0"
                        type="link"
                        size="small"
                        class="text-xs p-0"
                        @click="markAllRead"
                    >
                        <CheckOutlined /> Mark all read
                    </a-button>
                </div>
            </template>

            <template #content>
                <a-spin :spinning="loading">
                    <div class="max-h-96 overflow-y-auto -mx-4 px-1">
                        <template v-if="notifications.length === 0">
                            <a-empty description="No notifications" :image-style="{ height: '40px' }" class="py-6" />
                        </template>

                        <div
                            v-for="n in notifications"
                            :key="n.id"
                            class="flex gap-2 px-3 py-2.5 mb-1 rounded border cursor-pointer transition-colors"
                            :class="[
                                typeBg[n.type] ?? 'bg-gray-50 dark:bg-gray-800 border-gray-100 dark:border-gray-700',
                                n.is_read ? 'opacity-60' : ''
                            ]"
                            @click="markRead(n)"
                        >
                            <!-- Icon -->
                            <div class="shrink-0 mt-0.5" :class="typeColor[n.type] ?? 'text-gray-400'">
                                <WarningOutlined v-if="n.type === 'warning' || n.icon === 'crossmatch'" class="text-base" />
                                <InfoCircleOutlined v-else class="text-base" />
                            </div>

                            <!-- Body -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-start justify-between gap-1">
                                    <span class="text-xs font-semibold text-gray-800 dark:text-gray-200 leading-tight">
                                        {{ n.title }}
                                        <span
                                            v-if="!n.is_read"
                                            class="inline-block w-1.5 h-1.5 rounded-full bg-orange-500 mb-0.5 ml-1"
                                        />
                                    </span>
                                    <button
                                        class="shrink-0 text-gray-400 hover:text-red-500 transition-colors ml-1"
                                        @click.stop="remove(n)"
                                    >
                                        <CloseOutlined class="text-xs" />
                                    </button>
                                </div>
                                <p class="text-xs text-gray-600 dark:text-gray-400 mt-0.5 leading-snug">{{ n.message }}</p>
                                <div class="text-xs text-gray-400 mt-1">{{ n.created_at }}</div>
                            </div>
                        </div>
                    </div>
                </a-spin>
            </template>

            <!-- Bell trigger button -->
            <button
                class="relative flex items-center justify-center w-9 h-9 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors focus:outline-none"
                :title="unreadCount > 0 ? `${unreadCount} unread notifications` : 'Notifications'"
            >
                <BellOutlined class="text-lg text-gray-500 dark:text-gray-400" />
                <span
                    v-if="unreadCount > 0"
                    class="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] px-1 flex items-center justify-center rounded-full bg-red-500 text-white text-xs font-bold leading-none"
                >
                    {{ unreadCount > 99 ? '99+' : unreadCount }}
                </span>
            </button>
        </a-popover>
    </div>
</template>



