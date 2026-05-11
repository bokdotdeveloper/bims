<script setup lang="ts">
import ApplicationMark from '@/Components/ApplicationMark.vue';
import Checkbox from '@/Components/Checkbox.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { SafetyCertificateOutlined, ArrowLeftOutlined, LoginOutlined } from '@ant-design/icons-vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps<{
    canResetPassword: boolean;
    status?: string;
}>();

const page = usePage();
const appName = computed(() => String(page.props.appName ?? 'Laravel'));
const appDesc = computed(() => String(page.props.appDesc ?? ''));
const canRegister = computed(() => Boolean(page.props.canRegister));

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.transform((data) => ({
        ...data,
        remember: form.remember ? 'on' : '',
    })).post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};

const inputClass =
    'mt-1 block w-full rounded-xl border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30 dark:border-slate-600 dark:bg-slate-950 dark:text-slate-100 dark:focus:border-indigo-400 dark:focus:ring-indigo-400/25';
</script>

<template>
    <Head :title="`Log in — ${appName}`" />

    <div class="relative min-h-screen overflow-x-hidden bg-slate-50 text-slate-900 selection:bg-indigo-500/25 dark:bg-slate-950 dark:text-slate-100 dark:selection:bg-indigo-400/30">
        <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
            <div class="absolute -left-24 top-[-80px] h-[400px] w-[400px] rounded-full bg-indigo-400/25 blur-3xl dark:bg-indigo-500/12" />
            <div class="absolute bottom-[-100px] right-[-80px] h-[360px] w-[360px] rounded-full bg-sky-400/20 blur-3xl dark:bg-sky-500/10" />
            <div class="absolute inset-0 bg-[linear-gradient(to_bottom,transparent,rgba(248,250,252,0.9)_60%,rgb(248,250,252))] dark:bg-[linear-gradient(to_bottom,transparent,rgba(2,6,23,0.94)_65%,rgb(2,6,23))]" />
        </div>

        <div class="relative grid min-h-screen lg:grid-cols-[minmax(0,1fr)_minmax(0,1.05fr)]">
            <!-- Brand panel -->
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
                            Secure access
                        </p>
                        <h1 class="mt-3 text-3xl font-semibold leading-tight tracking-tight xl:text-4xl">
                            Sign in to continue your work.
                        </h1>
                        <p class="mt-4 text-sm leading-relaxed text-indigo-100/90">
                            Use the account issued by your administrator. Sessions may be protected with two-factor authentication when enabled.
                        </p>
                    </div>
                </div>

                <p class="text-xs text-indigo-200/70">
                    Protected area · Authorized personnel only
                </p>
            </aside>

            <!-- Form column -->
            <div class="flex flex-col justify-center px-4 py-10 sm:px-8 lg:px-12 xl:px-16">
                <div class="mx-auto w-full max-w-md">
                    <!-- Mobile brand -->
                    <div class="mb-8 flex items-center justify-between gap-4 lg:hidden">
                        <Link :href="'/'" class="inline-flex items-center gap-2.5">
                            <ApplicationMark class="h-9 w-9 shrink-0" />
                            <span class="leading-tight">
                                <span class="block text-sm font-semibold text-slate-900 dark:text-white">{{ appName }}</span>
                                <span class="text-[11px] text-slate-500 dark:text-slate-400">Sign in</span>
                            </span>
                        </Link>
                    </div>

                    <div
                        class="rounded-2xl border border-slate-200/90 bg-white/90 p-8 shadow-xl shadow-slate-900/5 backdrop-blur-md dark:border-slate-700/90 dark:bg-slate-900/85 dark:shadow-black/40 sm:p-10"
                    >
                        <div class="mb-8 flex items-start gap-3">
                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-indigo-600/10 text-indigo-700 dark:bg-indigo-500/15 dark:text-indigo-300">
                                <LoginOutlined class="text-xl" />
                            </div>
                            <div>
                                <h2 class="text-xl font-semibold tracking-tight text-slate-900 dark:text-white">
                                    Log in
                                </h2>
                                <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
                                    Enter your email and password for {{ appName }}.
                                </p>
                            </div>
                        </div>

                        <div
                            v-if="status"
                            class="mb-6 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800 dark:border-green-900/50 dark:bg-green-950/40 dark:text-green-300"
                        >
                            {{ status }}
                        </div>

                        <form class="space-y-5" @submit.prevent="submit">
                            <div>
                                <InputLabel class="text-slate-700 dark:text-slate-300" for="email" value="Email" />
                                <TextInput
                                    id="email"
                                    v-model="form.email"
                                    type="email"
                                    :class="inputClass"
                                    required
                                    autofocus
                                    autocomplete="username"
                                />
                                <InputError class="mt-2" :message="form.errors.email" />
                            </div>

                            <div>
                                <InputLabel class="text-slate-700 dark:text-slate-300" for="password" value="Password" />
                                <TextInput
                                    id="password"
                                    v-model="form.password"
                                    type="password"
                                    :class="inputClass"
                                    required
                                    autocomplete="current-password"
                                />
                                <InputError class="mt-2" :message="form.errors.password" />
                            </div>

                            <div class="flex items-center">
                                <label class="flex cursor-pointer items-center">
                                    <Checkbox v-model:checked="form.remember" name="remember" />
                                    <span class="ms-2 text-sm text-slate-600 dark:text-slate-400">Remember me</span>
                                </label>
                            </div>

                            <button
                                type="submit"
                                class="flex w-full items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 py-3 text-sm font-semibold text-white shadow-lg shadow-indigo-600/25 transition hover:bg-indigo-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 dark:shadow-indigo-950/50 dark:focus-visible:ring-offset-slate-950"
                                :disabled="form.processing"
                            >
                                <LoginOutlined v-if="!form.processing" />
                                {{ form.processing ? 'Signing in…' : 'Log in' }}
                            </button>

                            <div
                                v-if="canResetPassword || canRegister"
                                class="flex flex-col items-center gap-3 border-t border-slate-200 pt-6 text-center text-sm dark:border-slate-700 sm:flex-row sm:justify-between sm:text-left"
                            >
                                <Link
                                    v-if="canResetPassword"
                                    :href="route('password.request')"
                                    class="font-medium text-indigo-600 transition hover:text-indigo-500 dark:text-indigo-400 dark:hover:text-indigo-300"
                                >
                                    Forgot password?
                                </Link>
                                <Link
                                    v-if="canRegister"
                                    :href="route('register')"
                                    class="font-medium text-slate-600 transition hover:text-slate-900 dark:text-slate-400 dark:hover:text-white"
                                >
                                    Create an account
                                </Link>
                            </div>
                        </form>
                    </div>

                    <Link
                        :href="'/'"
                        class="mt-8 inline-flex items-center gap-2 text-sm font-medium text-slate-500 transition hover:text-slate-800 dark:text-slate-500 dark:hover:text-slate-300"
                    >
                        <ArrowLeftOutlined class="text-xs" />
                        Back to home
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>
