<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    AlertCircle,
    CheckCircle2,
    Package,
    Pencil,
    ShieldAlert,
    UserRound,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import DemandDecisionExcerpt from '@/components/demands/DemandDecisionExcerpt.vue';
import DemandDocumentPreview from '@/components/demands/DemandDocumentPreview.vue';
import DemandFileDropzone from '@/components/demands/DemandFileDropzone.vue';
import DemandStatusBadge from '@/components/demands/DemandStatusBadge.vue';
import ValidationTimeline from '@/components/demands/ValidationTimeline.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { RichTextEditor } from '@/components/ui/rich-text';
import { Separator } from '@/components/ui/separator';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetFooter,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { Spinner } from '@/components/ui/spinner';
import DemandLayout from '@/layouts/demands/DemandLayout.vue';
import type { Demand } from '@/types/demands';
import type { BreadcrumbItem } from '@/types';

type ReasonAction = 'approve' | 'business_approve' | 'refuse';

const props = defineProps<{
    demand: Demand;
}>();

const { t } = useI18n();

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: t('demands.nav.title'), href: '/demands' },
    { title: props.demand.reference, href: `/demands/${props.demand.id}` },
]);

const reasonOpen = ref(false);
const reasonAction = ref<ReasonAction>('refuse');
const actionForm = useForm({
    comment: '',
    reason: '',
    files: [] as File[],
});
const dropzoneKey = ref(0);

const natureFiles = computed(() =>
    props.demand.attachments.filter((a) => a.collection === 'nature_materiel'),
);
const referentielFiles = computed(() =>
    props.demand.attachments.filter(
        (a) => a.collection === 'referentiel_produit',
    ),
);

const latestRefuseFiles = computed(() => {
    const event = [...props.demand.events]
        .reverse()
        .find((item) => item.type.includes('refused'));
    return event?.attachments ?? [];
});

const latestBlockFiles = computed(() => {
    const event = [...props.demand.events]
        .reverse()
        .find((item) => item.type.includes('blocked'));
    return event?.attachments ?? [];
});

const hasActions = computed(
    () =>
        !!props.demand.permissions &&
        (props.demand.permissions.update ||
            props.demand.permissions.validate ||
            props.demand.permissions.business_validate ||
            props.demand.permissions.refuse_or_block ||
            props.demand.permissions.close),
);

const reasonTitle = computed(() => {
    if (reasonAction.value === 'refuse') {
        return t('demands.actions.refuse');
    }
    return t('demands.actions.approve');
});

const reasonHelp = computed(() =>
    reasonAction.value === 'refuse'
        ? t('demands.actions.reason_help')
        : t('demands.actions.reason_optional'),
);

function openReason(action: ReasonAction): void {
    reasonAction.value = action;
    actionForm.comment = '';
    actionForm.reason = '';
    actionForm.files = [];
    dropzoneKey.value += 1;
    reasonOpen.value = true;
}

function reasonIsEmpty(): boolean {
    return actionForm.reason.replace(/<[^>]*>/g, '').trim() === '';
}

function submitReason(): void {
    const url =
        reasonAction.value === 'refuse'
            ? `/demands/${props.demand.id}/refuse`
            : reasonAction.value === 'business_approve'
              ? `/demands/${props.demand.id}/business-approve`
              : `/demands/${props.demand.id}/approve`;

    actionForm.clearErrors('reason');

    if (reasonAction.value === 'refuse' && reasonIsEmpty()) {
        actionForm.setError(
            'reason',
            t('demands.messages.reason_required'),
        );
        return;
    }

    if (reasonAction.value === 'refuse') {
        actionForm.comment = '';
    } else {
        actionForm.comment = actionForm.reason;
    }

    actionForm.post(url, {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            reasonOpen.value = false;
            actionForm.reset();
            actionForm.files = [];
        },
    });
}

function closeDemand(): void {
    actionForm.post(`/demands/${props.demand.id}/close`, {
        preserveScroll: true,
    });
}

function formatDate(value: string | null): string {
    if (!value) return '';
    return new Date(value).toLocaleString();
}
</script>

<template>
    <DemandLayout :breadcrumbs="breadcrumbs">
        <Head :title="demand.reference" />

        <div class="mx-auto w-full max-w-7xl flex-1 p-4 md:p-6">
            <div
                class="grid gap-6 xl:grid-cols-[minmax(0,1.55fr)_minmax(22rem,0.85fr)]"
            >
                <div class="space-y-6">
                    <section
                        class="relative overflow-hidden rounded-2xl border bg-linear-to-br from-card via-card to-primary/4 shadow-sm"
                    >
                        <div
                            class="pointer-events-none absolute -top-16 -right-10 size-48 rounded-full bg-primary/10 blur-3xl"
                        />
                        <div class="relative space-y-5 p-5 md:p-7">
                            <div
                                class="flex flex-wrap items-start justify-between gap-3"
                            >
                                <div class="space-y-2">
                                    <div
                                        class="flex flex-wrap items-center gap-2"
                                    >
                                        <span
                                            class="rounded-md bg-muted px-2 py-1 font-mono text-[11px] tracking-wide text-muted-foreground"
                                        >
                                            {{ demand.reference }}
                                        </span>
                                        <DemandStatusBadge
                                            :status="demand.status"
                                        />
                                    </div>
                                    <h1
                                        class="text-2xl font-semibold tracking-tight md:text-3xl"
                                    >
                                        {{ demand.brand?.name }}
                                    </h1>
                                </div>
                            </div>

                            <div class="grid gap-3 sm:grid-cols-3">
                                <div
                                    class="rounded-xl border bg-background/70 px-3 py-2.5"
                                >
                                    <p
                                        class="text-[11px] font-medium tracking-wide text-muted-foreground uppercase"
                                    >
                                        {{ t('demands.form.material_nature') }}
                                    </p>
                                    <p
                                        class="mt-1 flex items-center gap-1.5 text-sm font-medium"
                                    >
                                        <Package
                                            class="size-3.5 shrink-0 text-primary"
                                        />
                                        <span class="truncate">
                                            {{
                                                demand.material_nature?.name ||
                                                '—'
                                            }}
                                        </span>
                                    </p>
                                </div>
                                <div
                                    class="rounded-xl border bg-background/70 px-3 py-2.5"
                                >
                                    <p
                                        class="text-[11px] font-medium tracking-wide text-muted-foreground uppercase"
                                    >
                                        {{ t('demands.show.created_by') }}
                                    </p>
                                    <p
                                        class="mt-1 flex items-center gap-1.5 text-sm font-medium"
                                    >
                                        <UserRound
                                            class="size-3.5 shrink-0 text-primary"
                                        />
                                        <span class="truncate">
                                            {{ demand.creator?.name || '—' }}
                                        </span>
                                    </p>
                                </div>
                                <div
                                    class="rounded-xl border bg-background/70 px-3 py-2.5"
                                >
                                    <p
                                        class="text-[11px] font-medium tracking-wide text-muted-foreground uppercase"
                                    >
                                        {{ t('demands.show.created_at') }}
                                    </p>
                                    <p class="mt-1 text-sm font-medium">
                                        {{
                                            formatDate(demand.created_at) || '—'
                                        }}
                                    </p>
                                </div>
                            </div>

                            <div
                                class="rounded-xl border bg-background/60 px-4 py-3"
                            >
                                <p
                                    class="mb-1 text-[11px] font-medium tracking-wide text-muted-foreground uppercase"
                                >
                                    {{ t('demands.form.description') }}
                                </p>
                                <p
                                    class="text-sm leading-relaxed whitespace-pre-wrap"
                                >
                                    {{ demand.description }}
                                </p>
                            </div>

                            <Alert
                                v-if="demand.refused_reason"
                                class="border-orange-300/50 bg-orange-50/70 dark:bg-orange-950/20"
                            >
                                <ShieldAlert
                                    class="text-orange-700 dark:text-orange-300"
                                />
                                <AlertTitle>{{
                                    t('demands.show.refused_reason')
                                }}</AlertTitle>
                                <AlertDescription>
                                    <DemandDecisionExcerpt
                                        :demand-id="demand.id"
                                        :html="demand.refused_reason"
                                        :files="latestRefuseFiles"
                                        :title="
                                            t('demands.show.refused_reason')
                                        "
                                    />
                                </AlertDescription>
                            </Alert>
                            <Alert
                                v-if="demand.blocked_reason"
                                variant="destructive"
                            >
                                <AlertCircle />
                                <AlertTitle>{{
                                    t('demands.show.blocked_reason')
                                }}</AlertTitle>
                                <AlertDescription>
                                    <DemandDecisionExcerpt
                                        :demand-id="demand.id"
                                        :html="demand.blocked_reason"
                                        :files="latestBlockFiles"
                                        :title="
                                            t('demands.show.blocked_reason')
                                        "
                                    />
                                </AlertDescription>
                            </Alert>

                            <div
                                v-if="hasActions"
                                class="flex flex-wrap gap-2 pt-1"
                            >
                                <Button
                                    v-if="demand.permissions?.update"
                                    variant="outline"
                                    as-child
                                >
                                    <Link :href="`/demands/${demand.id}/edit`">
                                        <Pencil class="size-4" />
                                        {{ t('common.edit') }}
                                    </Link>
                                </Button>
                                <Button
                                    v-if="demand.permissions?.validate"
                                    :disabled="actionForm.processing"
                                    @click="openReason('approve')"
                                >
                                    <CheckCircle2 class="size-4" />
                                    {{ t('demands.actions.approve') }}
                                </Button>
                                <Button
                                    v-if="demand.permissions?.business_validate"
                                    :disabled="actionForm.processing"
                                    @click="openReason('business_approve')"
                                >
                                    <CheckCircle2 class="size-4" />
                                    {{ t('demands.actions.approve') }}
                                </Button>
                                <Button
                                    v-if="demand.permissions?.refuse_or_block"
                                    variant="outline"
                                    :disabled="actionForm.processing"
                                    @click="openReason('refuse')"
                                >
                                    {{ t('demands.actions.refuse') }}
                                </Button>
                                <Button
                                    v-if="demand.permissions?.close"
                                    :disabled="actionForm.processing"
                                    @click="closeDemand"
                                >
                                    {{ t('demands.actions.close') }}
                                </Button>
                            </div>

                            <Separator />

                            <div class="space-y-4">
                                <div>
                                    <h2
                                        class="text-base font-semibold tracking-tight"
                                    >
                                        {{ t('demands.show.documents') }}
                                    </h2>
                                    <p class="text-sm text-muted-foreground">
                                        {{ t('demands.show.documents_hint') }}
                                    </p>
                                </div>

                                <div class="space-y-3">
                                    <div
                                        class="flex items-center justify-between gap-2"
                                    >
                                        <h3 class="text-sm font-semibold">
                                            {{ t('demands.form.nature_files') }}
                                        </h3>
                                        <span
                                            class="text-xs text-muted-foreground"
                                        >
                                            {{ natureFiles.length }}
                                        </span>
                                    </div>
                                    <div
                                        v-if="natureFiles.length"
                                        class="space-y-2"
                                    >
                                        <DemandDocumentPreview
                                            v-for="file in natureFiles"
                                            :key="file.id"
                                            :demand-id="demand.id"
                                            :file="file"
                                        />
                                    </div>
                                    <div
                                        v-else
                                        class="flex min-h-16 items-center justify-center rounded-xl border border-dashed text-sm text-muted-foreground"
                                    >
                                        {{ t('demands.show.no_files') }}
                                    </div>
                                </div>

                                <Separator />

                                <div class="space-y-3">
                                    <div
                                        class="flex items-center justify-between gap-2"
                                    >
                                        <h3 class="text-sm font-semibold">
                                            {{
                                                t(
                                                    'demands.form.referentiel_files',
                                                )
                                            }}
                                        </h3>
                                        <span
                                            class="text-xs text-muted-foreground"
                                        >
                                            {{ referentielFiles.length }}
                                        </span>
                                    </div>
                                    <div
                                        v-if="referentielFiles.length"
                                        class="space-y-2"
                                    >
                                        <DemandDocumentPreview
                                            v-for="file in referentielFiles"
                                            :key="file.id"
                                            :demand-id="demand.id"
                                            :file="file"
                                        />
                                    </div>
                                    <div
                                        v-else
                                        class="flex min-h-16 items-center justify-center rounded-xl border border-dashed text-sm text-muted-foreground"
                                    >
                                        {{ t('demands.show.no_files') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>

                <aside class="xl:sticky xl:top-4 xl:self-start">
                    <Card class="gap-0 shadow-sm">
                        <CardHeader>
                            <CardTitle class="text-base">
                                {{ t('demands.timeline.process') }}
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="pt-5">
                            <ValidationTimeline
                                :validators="demand.validators"
                                :current-step="demand.current_step"
                                :status="demand.status"
                                :events="demand.events"
                                :demand-id="demand.id"
                            />
                        </CardContent>
                    </Card>
                </aside>
            </div>
        </div>

        <Sheet v-model:open="reasonOpen">
            <SheetContent
                side="right"
                class="flex w-full flex-col gap-0 px-4 sm:max-w-lg"
            >
                <SheetHeader class="border-b px-1 pb-4 text-left">
                    <SheetTitle>{{ reasonTitle }}</SheetTitle>
                    <SheetDescription>
                        {{ reasonHelp }}
                    </SheetDescription>
                </SheetHeader>
                <div class="flex-1 space-y-4 overflow-y-auto px-1 py-4">
                    <div class="space-y-2">
                        <Label>
                            {{ t('demands.actions.reason') }}
                            <span
                                v-if="reasonAction !== 'refuse'"
                                class="font-normal text-muted-foreground"
                            >
                                ({{ t('common.optional') }})
                            </span>
                        </Label>
                        <RichTextEditor
                            v-model="actionForm.reason"
                            :placeholder="reasonHelp"
                            :disabled="actionForm.processing"
                        />
                        <p
                            v-if="
                                actionForm.errors.reason ||
                                actionForm.errors.comment
                            "
                            class="text-sm text-destructive"
                        >
                            {{
                                actionForm.errors.reason ||
                                actionForm.errors.comment
                            }}
                        </p>
                    </div>
                    <div class="space-y-2">
                        <Label>{{ t('demands.actions.files') }}</Label>
                        <DemandFileDropzone
                            :key="dropzoneKey"
                            accept=".pdf,.doc,.docx,.ppt,.pptx,.jpg,.jpeg,.png,.webp,application/pdf,image/jpeg,image/png,image/webp"
                            :hint="t('demands.actions.files_hint')"
                            :error="
                                actionForm.errors.files ||
                                actionForm.errors['files.0']
                            "
                            @select="(files) => (actionForm.files = files)"
                        />
                    </div>
                </div>
                <SheetFooter
                    class="mt-auto gap-2 border-t px-1 pt-4 sm:flex-row"
                >
                    <Button variant="outline" @click="reasonOpen = false">
                        {{ t('common.cancel') }}
                    </Button>
                    <Button
                        :variant="
                            reasonAction === 'refuse'
                                ? 'destructive'
                                : 'default'
                        "
                        :disabled="actionForm.processing"
                        @click="submitReason"
                    >
                        <Spinner v-if="actionForm.processing" />
                        {{
                            reasonAction === 'refuse'
                                ? t('demands.actions.refuse')
                                : t('demands.actions.approve')
                        }}
                    </Button>
                </SheetFooter>
            </SheetContent>
        </Sheet>
    </DemandLayout>
</template>
