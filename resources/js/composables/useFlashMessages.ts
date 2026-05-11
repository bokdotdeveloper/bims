import { usePage } from '@inertiajs/vue3';
import { watch } from 'vue';
import { message } from 'ant-design-vue';

export function useFlashMessages() {
    const page = usePage();

    watch(
        () => page.props.flash as { success?: string | null; error?: string | null } | undefined,
        (f) => {
            if (f?.success) message.success(f.success);
            if (f?.error) message.error(f.error);
        },
        { deep: true, immediate: true },
    );
}
