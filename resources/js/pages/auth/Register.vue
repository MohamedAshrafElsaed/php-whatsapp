<script lang="ts" setup>
import RegisteredUserController from '@/actions/App/Http/Controllers/Auth/RegisteredUserController';
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
import { login } from '@/routes';
import { Form, Head } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

const { t, locale } = useTranslation();

const countryCodes = computed(() => [
    { code: '+1', country: t('auth.countries.us_canada'), digits: '10' },
    { code: '+20', country: t('auth.countries.egypt'), digits: '10' },
    { code: '+44', country: t('auth.countries.uk'), digits: '10' },
    { code: '+91', country: t('auth.countries.india'), digits: '10' },
    { code: '+971', country: t('auth.countries.uae'), digits: '9' },
    { code: '+966', country: t('auth.countries.saudi_arabia'), digits: '9' },
    { code: '+962', country: t('auth.countries.jordan'), digits: '9' },
]);

const industries = computed(() => {
    const baseIndustries = [
        'Accounting',
        'Advertising',
        'Agriculture',
        'Automotive',
        'Banking',
        'Construction',
        'Consulting',
        'E-commerce',
        'Education',
        'Energy',
        'Entertainment',
        'Fashion',
        'Financial Services',
        'Food & Beverage',
        'Healthcare',
        'Hospitality',
        'Information Technology',
        'Insurance',
        'Legal Services',
        'Manufacturing',
        'Marketing',
        'Media',
        'Non-Profit',
        'Pharmaceutical',
        'Real Estate',
        'Retail',
        'Software',
        'Technology',
        'Telecommunications',
        'Transportation',
        'Travel & Tourism',
        'Other',
    ];

    // For Arabic, you can translate industry names if needed
    // For now, keeping English as it's standard in business
    return baseIndustries;
});

const selectedCountryCode = ref('+20');
const phoneInput = ref('');
const phoneError = ref('');
const selectedIndustry = ref('');
const industrySearch = ref('');

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

const filteredIndustries = computed(() => {
    if (!industrySearch.value) return industries.value;
    return industries.value.filter((industry) =>
        industry.toLowerCase().includes(industrySearch.value.toLowerCase()),
    );
});
</script>

<template>
    <AuthBase
        :description="t('auth.register_description')"
        :title="t('auth.register_title')"
    >
        <Head :title="t('auth.create_account')" />

        <Form
            v-slot="{ errors, processing }"
            :reset-on-success="['password', 'password_confirmation']"
            class="flex flex-col gap-6"
            v-bind="RegisteredUserController.store.form()"
        >
            <div class="grid gap-6">
                <div class="grid gap-2">
                    <Label for="first_name">{{ t('auth.first_name') }}</Label>
                    <Input
                        id="first_name"
                        :tabindex="1"
                        autocomplete="given-name"
                        autofocus
                        name="first_name"
                        :placeholder="t('auth.placeholder.first_name')"
                        required
                        type="text"
                    />
                    <InputError :message="errors.first_name" />
                </div>

                <div class="grid gap-2">
                    <Label for="last_name">{{ t('auth.last_name') }}</Label>
                    <Input
                        id="last_name"
                        :tabindex="2"
                        autocomplete="family-name"
                        name="last_name"
                        :placeholder="t('auth.placeholder.last_name')"
                        required
                        type="text"
                    />
                    <InputError :message="errors.last_name" />
                </div>

                <div class="grid gap-2">
                    <Label for="email">{{ t('auth.email') }}</Label>
                    <Input
                        id="email"
                        :tabindex="3"
                        autocomplete="email"
                        name="email"
                        :placeholder="t('auth.placeholder.email')"
                        type="email"
                    />
                    <InputError :message="errors.email" />
                </div>

                <div class="grid gap-2">
                    <Label for="industry">{{ t('auth.industry') }}</Label>
                    <Select v-model="selectedIndustry" name="industry">
                        <SelectTrigger :tabindex="4">
                            <SelectValue :placeholder="t('auth.placeholder.industry')" />
                        </SelectTrigger>
                        <SelectContent>
                            <div class="p-2">
                                <Input
                                    v-model="industrySearch"
                                    class="mb-2"
                                    :placeholder="t('auth.placeholder.search_industry')"
                                    @click.stop
                                />
                            </div>
                            <SelectItem
                                v-for="industry in filteredIndustries"
                                :key="industry"
                                :value="industry"
                            >
                                {{ industry }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="errors.industry" />
                </div>

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
                            :tabindex="5"
                            autocomplete="tel"
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
                    <p class="text-xs text-muted-foreground">
                        {{ t('auth.phone_note') }}
                    </p>
                    <InputError :message="errors.country_code" />
                    <InputError :message="errors.phone" />
                </div>

                <div class="grid gap-2">
                    <Label for="password">{{ t('auth.password') }}</Label>
                    <Input
                        id="password"
                        :tabindex="6"
                        autocomplete="new-password"
                        name="password"
                        :placeholder="t('auth.placeholder.password')"
                        required
                        type="password"
                    />
                    <InputError :message="errors.password" />
                </div>

                <div class="grid gap-2">
                    <Label for="password_confirmation">{{ t('auth.confirm_password') }}</Label>
                    <Input
                        id="password_confirmation"
                        :tabindex="7"
                        autocomplete="new-password"
                        name="password_confirmation"
                        :placeholder="t('auth.placeholder.confirm_password')"
                        required
                        type="password"
                    />
                    <InputError :message="errors.password_confirmation" />
                </div>

                <Button
                    :disabled="processing || !!phoneError"
                    class="mt-2 w-full"
                    data-test="register-user-button"
                    tabindex="8"
                    type="submit"
                    variant="default"
                >
                    <LoaderCircle
                        v-if="processing"
                        class="h-4 w-4 animate-spin"
                    />
                    {{ processing ? t('auth.creating') : t('auth.create_account') }}
                </Button>
            </div>

            <div class="text-center text-sm text-muted-foreground">
                {{ t('auth.already_have_account') }}
                <TextLink
                    :href="login()"
                    :tabindex="9"
                    class="underline underline-offset-4"
                >
                    {{ t('auth.log_in') }}
                </TextLink>
            </div>
        </Form>
    </AuthBase>
</template>
