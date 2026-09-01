<script setup lang="ts">
import { Check, Circle, X } from 'lucide-vue-next';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import DemandDecisionExcerpt from '@/components/demands/DemandDecisionExcerpt.vue';
import { cn } from '@/lib/utils';
import type { DemandAttachment, DemandEvent, DemandStatus, DemandValidator } from '@/types/demands';

type ProcessState = 'done' | 'current' | 'upcoming' | 'failed';

type ProcessNode = {
    key: string;
    title: string;
    actor: string | null;
    detail: string | null;
    at: string | null;
    comment: string | null;
    files: DemandAttachment[];
    state: ProcessState;
};

const props = defineProps<{
    validators: DemandValidator[];
    currentStep: number | null;
    status: DemandStatus | string;
    events: DemandEvent[];
    demandId: number;
}>();

const { t } = useI18n();

function formatDate(value: string | null): string {
    if (!value) {
        return '';
    }

    return new Date(value).toLocaleString();
}

function isNegative(type: string): boolean {
    return type.includes('refuse') || type.includes('block');
}

const nodes = computed<ProcessNode[]>(() => {
    const sortedEvents = [...props.events].sort((a, b) => {
        const aTime = a.created_at ? new Date(a.created_at).getTime() : 0;
        const bTime = b.created_at ? new Date(b.created_at).getTime() : 0;

        return aTime - bTime || a.id - b.id;
    });

    const result: ProcessNode[] = sortedEvents.map((event) => ({
        key: `event-${event.id}`,
        title: t(`demands.events.${event.type}`),
        actor: event.actor?.name ?? null,
        detail: null,
        at: event.created_at,
        comment: event.comment,
        files: event.attachments ?? [],
        state: isNegative(event.type) ? 'failed' : 'done',
    }));

    const terminal = ['refused', 'closed', 'blocked'].includes(props.status);
    if (terminal) {
        return result;
    }

    const hasClosingGroup = props.validators.some(
        (validator) =>
            validator.is_group ||
            validator.role_name === 'reglementaires',
    );

    if (props.status === 'pending_manager') {
        result.push({
            key: 'current-manager',
            title: t('demands.timeline.awaiting_manager'),
            actor: null,
            detail: null,
            at: null,
            comment: null,
            files: [],
            state: 'current',
        });
    }

    if (
        props.status === 'pending_validation' ||
        props.status === 'pending_manager' ||
        props.status === 'draft'
    ) {
        for (const validator of props.validators) {
            if (validator.status === 'approved') {
                continue;
            }

            const isCurrent =
                props.status === 'pending_validation' &&
                props.currentStep !== null &&
                validator.position === props.currentStep;

            const isGroup = !!(validator.is_group || validator.role_name);

            result.push({
                key: `validator-${validator.id}`,
                title: isCurrent
                    ? isGroup
                        ? t('demands.timeline.awaiting_group')
                        : t('demands.timeline.awaiting_validator')
                    : isGroup
                      ? t('demands.timeline.upcoming_group')
                      : t('demands.timeline.upcoming_validator'),
                actor: isGroup
                    ? t('demands.form.reglementaires_group')
                    : (validator.user?.name ?? null),
                detail: isGroup
                    ? t('demands.form.reglementaires_group_help')
                    : (validator.user?.email ?? null),
                at: null,
                comment: null,
                files: [],
                state: isCurrent ? 'current' : 'upcoming',
            });
        }
    }

    if (!hasClosingGroup) {
        if (
            props.status === 'pending_validation' ||
            props.status === 'pending_manager' ||
            props.status === 'pending_business_dev' ||
            props.status === 'draft'
        ) {
            if (props.status !== 'pending_business_dev') {
                result.push({
                    key: 'pending-business',
                    title: t('demands.timeline.upcoming_business'),
                    actor: null,
                    detail: null,
                    at: null,
                    comment: null,
                    files: [],
                    state: 'upcoming',
                });
            } else {
                result.push({
                    key: 'current-business',
                    title: t('demands.timeline.awaiting_business'),
                    actor: null,
                    detail: null,
                    at: null,
                    comment: null,
                    files: [],
                    state: 'current',
                });
            }
        }

        if (
            props.status === 'pending_validation' ||
            props.status === 'pending_manager' ||
            props.status === 'pending_business_dev' ||
            props.status === 'pending_closure' ||
            props.status === 'draft'
        ) {
            if (props.status !== 'pending_closure') {
                result.push({
                    key: 'pending-closure',
                    title: t('demands.timeline.upcoming_closure'),
                    actor: null,
                    detail: null,
                    at: null,
                    comment: null,
                    files: [],
                    state: 'upcoming',
                });
            } else {
                result.push({
                    key: 'current-closure',
                    title: t('demands.timeline.awaiting_closure'),
                    actor: null,
                    detail: null,
                    at: null,
                    comment: null,
                    files: [],
                    state: 'current',
                });
            }
        }
    }

    return result;
});
</script>

<template>
    <ol class="relative space-y-0">
        <li
            v-for="(node, index) in nodes"
            :key="node.key"
            class="relative flex gap-3 pb-5 last:pb-0"
        >
            <div class="relative flex flex-col items-center">
                <div
                    :class="
                        cn(
                            'z-10 flex size-9 shrink-0 items-center justify-center rounded-full border-2 transition-colors',
                            node.state === 'done' &&
                                'border-emerald-500 bg-emerald-500 text-white',
                            node.state === 'failed' &&
                                'border-destructive bg-destructive text-white',
                            node.state === 'current' &&
                                'border-primary bg-primary text-primary-foreground ring-4 ring-primary/20',
                            node.state === 'upcoming' &&
                                'border-border bg-background text-muted-foreground',
                        )
                    "
                >
                    <Check v-if="node.state === 'done'" class="size-4" />
                    <X v-else-if="node.state === 'failed'" class="size-4" />
                    <Circle v-else class="size-2.5 fill-current" />
                </div>
                <div
                    v-if="index < nodes.length - 1"
                    class="bg-border absolute top-9 bottom-0 w-px"
                />
            </div>

            <div
                :class="
                    cn(
                        'min-w-0 flex-1 rounded-xl border px-3 py-2.5 transition-colors',
                        node.state === 'current' && 'border-primary/30 bg-primary/5',
                        node.state === 'failed' &&
                            'border-destructive/30 bg-destructive/5',
                        (node.state === 'done' || node.state === 'upcoming') && 'bg-card',
                    )
                "
            >
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <p class="text-sm font-semibold">
                        {{ node.title }}
                        <span v-if="node.actor" class="text-muted-foreground font-normal">
                            — {{ node.actor }}
                        </span>
                    </p>
                    <span
                        :class="
                            cn(
                                'rounded-full px-2 py-0.5 text-[11px] font-medium',
                                node.state === 'done' &&
                                    'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200',
                                node.state === 'failed' &&
                                    'bg-destructive/10 text-destructive',
                                node.state === 'current' && 'bg-primary/15 text-primary',
                                node.state === 'upcoming' &&
                                    'bg-muted text-muted-foreground',
                            )
                        "
                    >
                        {{ t(`demands.timeline.state.${node.state}`) }}
                    </span>
                </div>
                <p v-if="node.detail" class="text-muted-foreground truncate text-xs">
                    {{ node.detail }}
                </p>
                <p v-if="node.at" class="text-muted-foreground mt-1 text-[11px]">
                    {{ formatDate(node.at) }}
                </p>
                <DemandDecisionExcerpt
                    v-if="node.comment || node.files.length"
                    class="mt-2"
                    :demand-id="demandId"
                    :html="node.comment"
                    :files="node.files"
                    :title="node.title"
                />
            </div>
        </li>

        <li
            v-if="nodes.length === 0"
            class="text-muted-foreground rounded-xl border border-dashed px-3 py-6 text-center text-sm"
        >
            {{ t('demands.timeline.empty') }}
        </li>
    </ol>
</template>
