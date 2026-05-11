<script setup lang="ts">
import ApplicationMark from '@/Components/ApplicationMark.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import {
    ArrowLeftOutlined,
    SafetyCertificateOutlined,
    SafetyOutlined,
} from '@ant-design/icons-vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, nextTick, ref } from 'vue';

const page = usePage();
const appName = computed(() => String(page.props.appName ?? 'Laravel'));
const appDesc = computed(() => String(page.props.appDesc ?? ''));

const recovery = ref(false);

const form = useForm({
    code: '',
    recovery_code: '',
});

const recoveryCodeInput = ref<{ focus: () => void } | null>(null);
const codeInput = ref<{ focus: () => void } | null>(null);

const toggleRecovery = async () => {
    recovery.value = !recovery.value;

    await nextTick();

    if (recovery.value) {
        recoveryCodeInput.value?.focus();
        form.code = '';
    } else {
        codeInput.value?.focus();
        form.recovery_code = '';
    }
};

const submit = () => {
    form.post(route('two-factor.login'));
};

const inputClass =
    'mt-1 block w-full rounded-xl border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30 dark:border-slate-600 dark:bg-slate-950 dark:text-slate-100 dark:focus:border-indigo-400 dark:focus:ring-indigo-400/25';
</script>

<template>
    <Head :title="`Two-factor authentication — ${appName}`" />

    <div class="relative min-h-screen overflow-x-hidden bg-slate-50 text-slate-900 selection:bg-indigo-500/25 dark:bg-slate-950 dark:text-slate-100 dark:selection:bg-indigo-400/30">
        <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
            <div class="absolute -left-24 top-[-80px] h-[400px] w-[400px] rounded-full bg-indigo-400/25 blur-3xl dark:bg-indigo-500/12" />
            <div class="absolute bottom-[-100px] right-[-80px] h-[360px] w-[360px] rounded-full bg-violet-400/20 blur-3xl dark:bg-violet-500/10" />
            <div class="absolute inset-0 bg-[linear-gradient(to_bottom,transparent,rgba(248,250,252,0.9)_60%,rgb(248,250,252))] dark:bg-[linear-gradient(to_bottom,transparent,rgba(2,6,23,0.94)_65%,rgb(2,6,23))]" />
        </div>

        <div class="relative grid min-h-screen lg:grid-cols-[minmax(0,1fr)_minmax(0,1.05fr)]">
            <aside
                class="relative hidden flex-col justify-between border-r border-white/10 bg-gradient-to-br from-indigo-700 via-indigo-800 to-violet-950 px-10 py-12 text-white lg:flex xl:px-14"
            >
                <div>
                    <Link :href="'/'" class="inline-flex items-center gap-3 rounded-xl text-white/90 transition hover:text-white">
                        <ApplicationMark class="h-10 w-10 shrink-0 drop-shadow-sm" />
                        <span class="leading-tight">
                            <span class="block text-lg font-semibold tracking-tight">{{ appName }}</span>
                            <span class="text-xs font-medium text-indigo-200/90">{{ appDesc }}</span>
                        </span>
                    </Link>

                    <div class="mt-14 max-w-md">
                        <p class="flex items-center gap-2 text-[11px] font-semibold uppercase tracking-wider text-indigo-200/90">
                            <SafetyCertificateOutlined />
                            Two-factor verification
                        </p>
                        <h1 class="mt-3 text-3xl font-semibold leading-tight tracking-tight xl:text-4xl">
                            Confirm it's you before we continue.
                        </h1>
                        <p class="mt-4 text-sm leading-relaxed text-indigo-100/90">
                            Enter the one-time code from your authenticator app, or use a recovery code if you've lost access to the device.
                        </p>
                    </div>
                </div>

                <p class="text-xs text-indigo-200/70">
                    Extra security for {{ appName }}
                </p>
            </aside>

            <div class="flex flex-col justify-center px-4 py-10 sm:px-8 lg:px-12 xl:px-16">
                <div class="mx-auto w-full max-w-md">
                    <div class="mb-8 flex items-center justify-between gap-4 lg:hidden">
                        <Link :href="'/'" class="inline-flex items-center gap-2.5">
                            <ApplicationMark class="h-9 w-9 shrink-0" />
                            <span class="leading-tight">
                                <span class="block text-sm font-semibold text-slate-900 dark:text-white">{{ appName }}</span>
                                <span class="text-[11px] text-slate-500 dark:text-slate-400">Two-factor step</span>
                            </span>
                        </Link>
                    </div>

                    <div
                        class="rounded-2xl border border-slate-200/90 bg-white/90 p-8 shadow-xl shadow-slate-900/5 backdrop-blur-md dark:border-slate-700/90 dark:bg-slate-900/85 dark:shadow-black/40 sm:p-10"
                    >
                        <div class="mb-8 flex items-start gap-3">
                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-indigo-600/10 text-indigo-700 dark:bg-indigo-500/15 dark:text-indigo-300">
                                <SafetyOutlined class="text-xl" />
                            </div>
                            <div>
                                <h2 class="text-xl font-semibold tracking-tight text-slate-900 dark:text-white">
                                    Two-factor authentication
                                </h2>
                                <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
                                    <template v-if="!recovery">
                                        Enter the 6-digit code from your authenticator app.
                                    </template>
                                    <template v-else>
                                        Enter one of your emergency recovery codes.
                                    </template>
                                </p>
                            </div>
                        </div>

                        <form class="space-y-5" @submit.prevent="submit">
                            <div v-if="!recovery">
                                <InputLabel class="text-slate-700 dark:text-slate-300" for="code" value="Authentication code" />
                                <TextInput
                                    id="code"
                                    ref="codeInput"
                                    v-model="form.code"
                                    type="text"
                                    inputmode="numeric"
                                    :class="inputClass"
                                    autofocus
                                    autocomplete="one-time-code"
                                />
                                <InputError class="mt-2" :message="form.errors.code" />
                            </div>

                            <div v-else>
                                <InputLabel class="text-slate-700 dark:text-slate-300" for="recovery_code" value="Recovery code" />
                                <TextInput
                                    id="recovery_code"
                                    ref="recoveryCodeInput"
                                    v-model="form.recovery_code"
                                    type="text"
                                    :class="inputClass"
                                    autocomplete="one-time-code"
                                />
                                <InputError class="mt-2" :message="form.errors.recovery_code" />
                            </div>

                            <button
                                type="button"
                                class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-100 dark:border-slate-600 dark:bg-slate-800/80 dark:text-slate-200 dark:hover:bg-slate-800"
                                @click.prevent="toggleRecovery"
                            >
                                <template v-if="!recovery">
                                    Use a recovery code instead
                                </template>
                                <template v-else>
                                    Use authenticator code instead
                                </template>
                            </button>

                            <button
                                type="submit"
                                class="flex w-full items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 py-3 text-sm font-semibold text-white shadow-lg shadow-indigo-600/25 transition hover:bg-indigo-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 dark:shadow-indigo-950/50 dark:focus-visible:ring-offset-slate-950"
                                :disabled="form.processing"
                            >
                                <SafetyOutlined v-if="!form.processing" class="text-base" />
                                {{ form.processing ? 'Verifying…' : 'Continue' }}
                            </button>
                        </form>
                    </div>

                    <Link
                        :href="route('login')"
                        class="mt-8 inline-flex items-center gap-2 text-sm font-medium text-slate-500 transition hover:text-slate-800 dark:text-slate-500 dark:hover:text-slate-300"
                    >
                        <ArrowLeftOutlined class="text-xs" />
                        Back to sign in
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>
