<script setup lang="ts">
import ApplicationMark from "@/Components/ApplicationMark.vue";
import { Head, Link, usePage } from "@inertiajs/vue3";
import { useAuthorization } from "@/composables/useAuthorization";
import {
    ArrowRightOutlined,
    AuditOutlined,
    DashboardOutlined,
    DollarOutlined,
    LoginOutlined,
    ProjectOutlined,
    ReadOutlined,
    TeamOutlined,
    UserOutlined,
} from "@ant-design/icons-vue";
import { computed, type Component } from "vue";

defineProps<{
    canLogin: boolean;
    laravelVersion: string;
    phpVersion: string;
}>();

const page = usePage();
const { can, canAny } = useAuthorization();

const appName = computed(() => String(page.props.appName ?? "Laravel"));
const appDesc = computed(() => String(page.props.appDesc ?? ""));
const canRegister = computed(() => Boolean(page.props.canRegister));

const user = computed(
    () => page.props.auth?.user as { name?: string } | undefined,
);

interface Feature {
    title: string;
    description: string;
    icon: Component;
    route?: string;
    /** Show “Open” when user has any of these permissions */
    permissions?: string[];
}

const features: Feature[] = [
    {
        title: "Beneficiaries",
        description:
            "Register and maintain individual beneficiary profiles, demographics, and household links.",
        icon: UserOutlined,
        route: "beneficiaries.index",
        permissions: ["beneficiaries.view", "beneficiaries.manage"],
    },
    {
        title: "Beneficiary groups",
        description:
            "Organize members into associations or communities for programs and shared assistance.",
        icon: TeamOutlined,
        route: "beneficiary-groups.index",
        permissions: ["groups.view", "groups.manage"],
    },
    {
        title: "Projects",
        description:
            "Track interventions and enroll individuals or groups with enrollment dates and status.",
        icon: ProjectOutlined,
        route: "projects.index",
        permissions: ["projects.view", "projects.manage"],
    },
    {
        title: "Trainings",
        description:
            "Schedule trainings and record participant attendance tied to projects where relevant.",
        icon: ReadOutlined,
        route: "trainings.index",
        permissions: ["trainings.view", "trainings.manage"],
    },
    {
        title: "Assistance",
        description:
            "Document releases to individuals or groups, amounts, types, and linkage to projects.",
        icon: DollarOutlined,
        route: "assistance-records.index",
        permissions: ["assistance.view", "assistance.manage"],
    },
    {
        title: "Audit trail",
        description:
            "Review accountable actions across beneficiaries and program entities when permitted.",
        icon: AuditOutlined,
        route: "audit-logs.index",
        permissions: ["audit_logs.view"],
    },
];

function canOpenFeature(f: Feature): boolean {
    if (!user.value || !f.route || !f.permissions?.length) {
        return false;
    }

    return canAny(f.permissions);
}
</script>

<template>
    <Head :title="`Welcome — ${appName}`" />

    <div
        class="relative min-h-screen overflow-x-hidden bg-slate-50 text-slate-900 selection:bg-indigo-500/25 selection:text-indigo-950 dark:bg-slate-950 dark:text-slate-100 dark:selection:bg-indigo-400/30 dark:selection:text-white"
    >
        <!-- ambient background -->
        <div
            class="pointer-events-none absolute inset-0 overflow-hidden"
            aria-hidden="true"
        >
            <div
                class="absolute -left-32 top-0 h-[420px] w-[420px] rounded-full bg-indigo-400/25 blur-3xl dark:bg-indigo-500/15"
            />
            <div
                class="absolute right-[-120px] top-[28%] h-[380px] w-[380px] rounded-full bg-sky-400/20 blur-3xl dark:bg-sky-500/10"
            />
            <div
                class="absolute bottom-[-80px] left-[35%] h-[320px] w-[320px] rounded-full bg-violet-400/15 blur-3xl dark:bg-violet-500/10"
            />
            <div
                class="absolute inset-0 bg-[linear-gradient(to_bottom,transparent,rgba(248,250,252,0.85)_65%,rgb(248,250,252))] dark:bg-[linear-gradient(to_bottom,transparent,rgba(2,6,23,0.92)_70%,rgb(2,6,23))]"
            />
        </div>

        <div class="relative flex min-h-screen flex-col">
            <!-- header -->
            <header
                class="border-b border-slate-200/80 bg-white/75 backdrop-blur-md dark:border-slate-800/80 dark:bg-slate-900/70"
            >
                <div
                    class="mx-auto flex h-16 max-w-6xl items-center justify-between px-4 sm:px-6 lg:px-8"
                >
                    <div class="flex items-center gap-3">
                        <ApplicationMark class="h-9 w-9 shrink-0" />
                        <div class="leading-tight">
                            <p
                                class="text-sm font-semibold tracking-tight text-slate-900 dark:text-white"
                            >
                                {{ appName }}
                            </p>
                            <p
                                class="text-[11px] text-slate-500 dark:text-slate-400"
                            >
                                {{ appDesc }}
                            </p>
                        </div>
                    </div>

                    <nav
                        v-if="canLogin"
                        class="flex items-center gap-2 sm:gap-3"
                    >
                        <template v-if="user">
                            <Link
                                v-if="can('dashboard.access')"
                                :href="route('dashboard')"
                                class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3 py-2 text-xs font-medium text-white shadow-sm transition hover:bg-indigo-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-slate-950 sm:text-sm"
                            >
                                <DashboardOutlined class="text-[15px]" />
                                Dashboard
                            </Link>
                            <Link
                                v-else
                                :href="route('profile.show')"
                                class="rounded-lg px-3 py-2 text-xs font-medium text-slate-700 transition hover:bg-slate-100 hover:text-slate-900 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white sm:text-sm"
                            >
                                Account
                            </Link>
                        </template>
                        <template v-else>
                            <Link
                                :href="route('login')"
                                class="inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-xs font-medium text-slate-700 transition hover:bg-slate-100 hover:text-slate-900 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white sm:text-sm"
                            >
                                <LoginOutlined class="text-[14px]" />
                                Sign in
                            </Link>
                            <Link
                                v-if="canRegister"
                                :href="route('register')"
                                class="inline-flex items-center gap-1 rounded-lg bg-indigo-600 px-3 py-2 text-xs font-medium text-white shadow-sm transition hover:bg-indigo-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-slate-950 sm:text-sm"
                            >
                                Register
                                <ArrowRightOutlined
                                    class="text-[12px] opacity-90"
                                />
                            </Link>
                        </template>
                    </nav>
                </div>
            </header>

            <main class="flex flex-1 flex-col">
                <!-- hero -->
                <section
                    class="mx-auto w-full max-w-6xl px-4 pb-12 pt-12 sm:px-6 sm:pb-16 sm:pt-16 lg:px-8"
                >
                    <p
                        class="mb-4 inline-flex items-center rounded-full border border-indigo-200/80 bg-indigo-50 px-3 py-1 text-[11px] font-medium uppercase tracking-wide text-indigo-800 dark:border-indigo-500/30 dark:bg-indigo-950/50 dark:text-indigo-200"
                    >
                        One workspace for social protection data
                    </p>
                    <h1
                        class="max-w-3xl text-3xl font-semibold tracking-tight text-slate-900 dark:text-white sm:text-4xl lg:text-[2.75rem] lg:leading-[1.12]"
                    >
                        Manage beneficiaries, groups, projects, and assistance
                        in one secure system.
                    </h1>
                    <p
                        class="mt-5 max-w-2xl text-base leading-relaxed text-slate-600 dark:text-slate-400"
                    >
                        {{ appName }} supports enrollment workflows, training
                        participation, financial assistance records, and an
                        audit trail—aligned with how field teams and
                        coordinators actually work.
                    </p>

                    <div class="mt-10 flex flex-wrap items-center gap-4">
                        <template v-if="canLogin && !user">
                            <Link
                                :href="route('login')"
                                class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-indigo-600/25 transition hover:bg-indigo-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 dark:shadow-indigo-900/40 dark:focus-visible:ring-offset-slate-950"
                            >
                                Sign in to continue
                                <ArrowRightOutlined />
                            </Link>
                            <Link
                                v-if="canRegister"
                                :href="route('register')"
                                class="rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-800 shadow-sm transition hover:border-slate-400 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100 dark:hover:bg-slate-800"
                            >
                                Request access
                            </Link>
                        </template>
                        <Link
                            v-else-if="user && can('dashboard.access')"
                            :href="route('dashboard')"
                            class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-indigo-600/25 transition hover:bg-indigo-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-slate-950"
                        >
                            Go to dashboard
                            <DashboardOutlined />
                        </Link>
                    </div>
                </section>

                <!-- capabilities -->
                <section
                    class="border-t border-slate-200/80 bg-white/40 py-14 dark:border-slate-800/80 dark:bg-slate-900/30"
                >
                    <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                        <div class="max-w-2xl">
                            <h2
                                class="text-lg font-semibold text-slate-900 dark:text-white"
                            >
                                What you can do inside {{ appName }}
                            </h2>
                            <p
                                class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-400"
                            >
                                Modules mirror the main navigation—permissions
                                control what each role sees after sign-in.
                            </p>
                        </div>

                        <div
                            class="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-3"
                        >
                            <article
                                v-for="f in features"
                                :key="f.title"
                                class="group flex flex-col rounded-2xl border border-slate-200/90 bg-white/90 p-6 shadow-sm transition hover:border-indigo-300/70 hover:shadow-md dark:border-slate-700/90 dark:bg-slate-900/80 dark:hover:border-indigo-500/40"
                            >
                                <div
                                    class="flex items-start justify-between gap-3"
                                >
                                    <div
                                        class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-indigo-600/10 text-indigo-700 dark:bg-indigo-500/15 dark:text-indigo-300"
                                    >
                                        <component
                                            :is="f.icon"
                                            class="text-xl"
                                        />
                                    </div>
                                    <Link
                                        v-if="canOpenFeature(f)"
                                        :href="route(f.route!)"
                                        class="inline-flex items-center gap-0.5 text-xs font-semibold text-indigo-600 opacity-0 transition group-hover:opacity-100 dark:text-indigo-400 sm:opacity-100"
                                    >
                                        Open
                                        <ArrowRightOutlined
                                            class="text-[10px]"
                                        />
                                    </Link>
                                </div>
                                <h3
                                    class="mt-4 text-base font-semibold text-slate-900 dark:text-white"
                                >
                                    {{ f.title }}
                                </h3>
                                <p
                                    class="mt-2 flex-1 text-sm leading-relaxed text-slate-600 dark:text-slate-400"
                                >
                                    {{ f.description }}
                                </p>
                            </article>
                        </div>
                    </div>
                </section>

                <!-- closing strip -->
                <section
                    class="mx-auto w-full max-w-6xl flex-1 px-4 py-14 sm:px-6 lg:px-8"
                >
                    <div
                        class="rounded-2xl border border-indigo-200/60 bg-gradient-to-br from-indigo-600 via-indigo-700 to-violet-800 px-6 py-10 text-center shadow-xl dark:border-indigo-500/20 sm:px-10"
                    >
                        <h2
                            class="text-xl font-semibold text-white sm:text-2xl"
                        >
                            Ready when your team is
                        </h2>
                        <p
                            class="mx-auto mt-3 max-w-xl text-sm leading-relaxed text-indigo-100"
                        >
                            Use roles and permissions to separate viewers,
                            encoders, and administrators—without mixing datasets
                            you rely on for reporting.
                        </p>
                        <div v-if="canLogin && !user" class="mt-8">
                            <Link
                                :href="route('login')"
                                class="inline-flex items-center gap-2 rounded-xl bg-white px-5 py-3 text-sm font-semibold text-indigo-700 shadow-md transition hover:bg-indigo-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-indigo-700"
                            >
                                Log in to {{ appName }}
                            </Link>
                        </div>
                    </div>
                </section>
            </main>

            <footer
                class="border-t border-slate-200/80 bg-white/60 py-8 text-center text-xs text-slate-500 backdrop-blur-sm dark:border-slate-800 dark:bg-slate-950/60 dark:text-slate-500"
            >
                <p>
                    &copy; {{ new Date().getFullYear() }} {{ appName }}. All
                    rights reserved.
                </p>
                <p
                    class="mt-2 font-mono text-[10px] text-slate-400 dark:text-slate-600"
                >
                    Laravel {{ laravelVersion }} · PHP {{ phpVersion }}
                </p>
            </footer>
        </div>
    </div>
</template>
