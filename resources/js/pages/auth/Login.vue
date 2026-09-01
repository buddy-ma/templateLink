<script setup lang="ts">
import { Form, Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { Spinner } from '@/components/ui/spinner';
import { useAuthSettings } from '@/composables/useAppSettings';
import AuthBase from '@/layouts/AuthLayout.vue';
import { formPropsFromRoute } from '@/lib/formPropsFromRoute';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();

const authSettings = useAuthSettings();
const { t } = useI18n();
const page = usePage();
const flashError = computed(() => page.props.flash?.error);
</script>

<template>
    <AuthBase
        :title="t('auth.login_title')"
        :description="t('auth.login_description')"
    >
        <Head :title="t('auth.login')" />

        <div
            v-if="status"
            class="mb-4 text-center text-sm font-medium text-green-600"
        >
            {{ status }}
        </div>

        <div
            v-if="flashError"
            class="mb-4 text-center text-sm font-medium text-destructive"
        >
            {{ flashError }}
        </div>

        <div v-if="authSettings.zohoEnabled" class="mb-6 flex flex-col gap-3">
            <Button variant="outline" class="w-full" as-child>
                <a href="/auth/zoho">
                    <svg
                        class="mr-2 size-5"
                        viewBox="0 0 24 24"
                        fill="none"
                        xmlns="http://www.w3.org/2000/svg"
                        aria-hidden="true"
                    >
                        <path
                            d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm0 2.4c5.302 0 9.6 4.298 9.6 9.6S17.302 21.6 12 21.6 2.4 17.302 2.4 12 6.698 2.4 12 2.4z"
                            fill="#FF6D01"
                        />
                        <path
                            d="M12 6c-3.314 0-6 2.686-6 6s2.686 6 6 6 6-2.686 6-6-2.686-6-6-6zm0 2.4c1.988 0 3.6 1.612 3.6 3.6S13.988 16.2 12 16.2 8.4 14.588 8.4 12.6 10.012 8.4 12 8.4z"
                            fill="#FF6D01"
                        />
                    </svg>
                    {{ t('auth.login_with_zoho') }}
                </a>
            </Button>

            <div
                v-if="authSettings.passwordLoginEnabled"
                class="relative flex items-center gap-3 py-1"
            >
                <Separator class="flex-1" />
                <span class="text-xs text-muted-foreground">{{
                    t('auth.or_continue_with')
                }}</span>
                <Separator class="flex-1" />
            </div>
        </div>

        <Form
            v-if="authSettings.passwordLoginEnabled"
            v-bind="formPropsFromRoute(store())"
            :reset-on-success="['password']"
            v-slot="{ errors, processing }"
            class="flex flex-col gap-6"
        >
            <div class="grid gap-5">
                <div class="grid gap-2">
                    <Label for="email">{{ t('auth.email') }}</Label>
                    <Input
                        id="email"
                        type="email"
                        name="email"
                        required
                        autofocus
                        :tabindex="1"
                        autocomplete="email"
                        placeholder="email@example.com"
                    />
                    <InputError :message="errors.email" />
                </div>

                <div class="grid gap-2">
                    <div class="flex items-center justify-between">
                        <Label for="password">{{ t('auth.password') }}</Label>
                        <TextLink
                            v-if="canResetPassword"
                            :href="request()"
                            class="text-sm"
                            :tabindex="5"
                        >
                            {{ t('auth.forgot_password') }}
                        </TextLink>
                    </div>
                    <PasswordInput
                        id="password"
                        name="password"
                        required
                        :tabindex="2"
                        autocomplete="current-password"
                        placeholder="Password"
                    />
                    <InputError :message="errors.password" />
                </div>

                <div class="flex items-center justify-between">
                    <Label for="remember" class="flex items-center space-x-3">
                        <Checkbox id="remember" name="remember" :tabindex="3" />
                        <span>{{ t('auth.remember_me') }}</span>
                    </Label>
                </div>

                <Button
                    type="submit"
                    class="mt-2 w-full"
                    :tabindex="4"
                    :disabled="processing"
                    data-test="login-button"
                >
                    <Spinner v-if="processing" />
                    {{ t('auth.login') }}
                </Button>
            </div>
        </Form>
    </AuthBase>
</template>
