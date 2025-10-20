<script lang="ts" setup>
import AuthenticatedSessionController from '@/actions/App/Http/Controllers/Auth/AuthenticatedSessionController';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AuthBase from '@/layouts/AuthLayout.vue';
import { useTranslation } from '@/composables/useTranslation';
import { register } from '@/routes';
import { Form, Head } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';
import { ref, watch, computed } from 'vue';

const props = defineProps<{
    status?: string;
}>();

const { t } = useTranslation();

const countryCodes = computed(() => [
    { code: '+1', country: t('auth.countries.us_canada'), digits: '10' },
    { code: '+20', country: t('auth.countries.egypt'), digits: '10' },
    { code: '+44', country: t('auth.countries.uk'), digits: '10' },
    { code: '+91', country: t('auth.countries.india'), digits: '10' },
    { code: '+971', country: t('auth.countries.uae'), digits: '9' },
    { code: '+966', country: t('auth.countries.saudi_arabia'), digits: '9' },
    { code: '+962', country: t('auth.countries.jordan'), digits: '9' },
]);

const selectedCountryCode = ref('+20');
const phoneInput = ref('');
const phoneError = ref('');

const handlePhoneInput = (event: Event) => {
    const target = event.target as HTMLInputElement;
    let value = target.value;

    value = value.replace(/\D/g, '');

    const cleanCountryCode = selectedCountryCode.value.replace('+', '');

    if (value.startsWith(cleanCountryCode)) {
        phoneError.value = t('auth.phone_error', { code: selectedCountryCode.value });
        value = value.substring(cleanCountryCode.length);
    } else if (value.startsWith('0')) {
        value = value.substring(1);
    } else {
        phoneError.value = '';
    }

    phoneInput.value = value;
    target.value = value;
};

watch(selectedCountryCode, () => {
    phoneError.value = '';
});
</script>

<template>
    <AuthBase
        :description="t('auth.login_description')"
        :title="t('auth.login_title')"
    >
        <Head :title="t('auth.log_in')" />

        <div
            v-if="status"
            class="mb-4 text-center text-sm font-medium text-green-600"
        >
            {{ status }}
        </div>

        <Form
            v-slot="{ errors, processing }"
            class="flex flex-col gap-6"
            v-bind="AuthenticatedSessionController.store.form()"
        >
            <div class="grid gap-6">
                <div class="grid gap-2">
                    <Label for="phone">{{ t('auth.phone') }}</Label>
                    <div class="flex flex-col gap-2 sm:flex-row">
                        <Select
                            v-model="selectedCountryCode"
                            name="country_code"
                        >
                            <SelectTrigger class="w-full sm:w-[140px]">
                                <SelectValue :placeholder="t('auth.placeholder.country_code')" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="item in countryCodes"
                                    :key="item.code"
                                    :value="item.code"
                                >
                                    {{ item.code }} ({{ item.country }})
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <Input
                            id="phone"
                            v-model="phoneInput"
                            :tabindex="1"
                            autocomplete="tel"
                            autofocus
                            class="flex-1"
                            inputmode="numeric"
                            name="phone"
                            :placeholder="t('auth.placeholder.phone')"
                            required
                            type="tel"
                            @input="handlePhoneInput"
                        />
                    </div>
                    <p v-if="phoneError" class="text-xs text-destructive">
                        {{ phoneError }}
                    </p>
                    <InputError :message="errors.country_code" />
                    <InputError :message="errors.phone" />
                </div>

                <div class="grid gap-2">
                    <Label for="password">{{ t('auth.password') }}</Label>
                    <Input
                        id="password"
                        :tabindex="2"
                        autocomplete="current-password"
                        name="password"
                        :placeholder="t('auth.placeholder.password')"
                        required
                        type="password"
                    />
                    <InputError :message="errors.password" />
                </div>

                <Button
                    :disabled="processing || !!phoneError"
                    :tabindex="3"
                    class="mt-4 w-full"
                    data-test="login-button"
                    type="submit"
                    variant="default"
                >
                    <LoaderCircle
                        v-if="processing"
                        class="h-4 w-4 animate-spin"
                    />
                    {{ processing ? t('auth.logging_in') : t('auth.log_in') }}
                </Button>
            </div>

            <div class="text-center text-sm text-muted-foreground">
                {{ t('auth.dont_have_account') }}
                <TextLink :href="register()" :tabindex="4">
                    {{ t('auth.sign_up') }}
                </TextLink>
            </div>
        </Form>
    </AuthBase>
</template>
