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
import { register } from '@/routes';
import { Form, Head, usePage } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

const props = defineProps<{
    status?: string;
}>();

const page = usePage();
const otpFailed = computed(() => page.props.otp_failed || false);

const countryCodes = [
    { code: '+1', country: 'US/Canada', digits: '10' },
    { code: '+20', country: 'Egypt', digits: '10' },
    { code: '+44', country: 'UK', digits: '10' },
    { code: '+91', country: 'India', digits: '10' },
    { code: '+971', country: 'UAE', digits: '9' },
    { code: '+966', country: 'Saudi Arabia', digits: '9' },
    { code: '+962', country: 'Jordan', digits: '9' },
];

const selectedCountryCode = ref('+20');
const phoneInput = ref('');
const phoneError = ref('');
const showPasswordField = ref(otpFailed.value);

// Prevent user from typing country code
const handlePhoneInput = (event: Event) => {
    const target = event.target as HTMLInputElement;
    let value = target.value;

    value = value.replace(/\D/g, '');

    const cleanCountryCode = selectedCountryCode.value.replace('+', '');

    if (value.startsWith(cleanCountryCode)) {
        phoneError.value = `Don't include country code ${selectedCountryCode.value} in the phone number`;
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

watch(otpFailed, (newValue) => {
    showPasswordField.value = newValue;
});

const togglePasswordField = () => {
    showPasswordField.value = !showPasswordField.value;
};
</script>

<template>
    <AuthBase
        description="Enter your phone number below to log in"
        title="Log in to your account"
    >
        <Head title="Log in" />

        <div
            v-if="status"
            class="mb-4 text-center text-sm font-medium text-green-600"
        >
            {{ status }}
        </div>

        <div
            v-if="otpFailed"
            class="mb-4 rounded-lg bg-yellow-50 p-3 text-center text-sm text-yellow-800"
        >
            Could not send verification code. Please login with your password
            instead.
        </div>

        <Form
            v-slot="{ errors, processing }"
            class="flex flex-col gap-6"
            v-bind="AuthenticatedSessionController.store.form()"
        >
            <div class="grid gap-6">
                <div class="grid gap-2">
                    <Label for="phone">Phone Number</Label>
                    <div class="flex gap-2">
                        <Select v-model="selectedCountryCode" name="country_code">
                            <SelectTrigger class="w-[140px]">
                                <SelectValue placeholder="Code" />
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
                            placeholder="1097154916"
                            required
                            type="tel"
                            @input="handlePhoneInput"
                        />
                    </div>
                    <p v-if="phoneError" class="text-xs text-destructive">
                        {{ phoneError }}
                    </p>
                    <p v-if="!showPasswordField" class="text-xs text-muted-foreground">
                        ⚠️ We'll send a verification code to your WhatsApp
                    </p>
                    <InputError :message="errors.country_code" />
                    <InputError :message="errors.phone" />
                </div>

                <div v-if="showPasswordField" class="grid gap-2">
                    <Label for="password">Password</Label>
                    <Input
                        id="password"
                        :tabindex="2"
                        autocomplete="current-password"
                        name="password"
                        placeholder="Password"
                        type="password"
                    />
                    <InputError :message="errors.password" />
                </div>

                <Button
                    :disabled="processing || !!phoneError"
                    :tabindex="showPasswordField ? 3 : 2"
                    class="mt-4 w-full"
                    data-test="login-button"
                    type="submit"
                >
                    <LoaderCircle
                        v-if="processing"
                        class="h-4 w-4 animate-spin"
                    />
                    {{ showPasswordField ? 'Login with password' : 'Send verification code' }}
                </Button>

                <div class="text-center">
                    <button
                        class="text-sm text-muted-foreground hover:text-foreground underline"
                        type="button"
                        @click="togglePasswordField"
                    >
                        {{ showPasswordField ? 'Use verification code instead' : 'Use password instead' }}
                    </button>
                </div>
            </div>

            <div class="text-center text-sm text-muted-foreground">
                Don't have an account?
                <TextLink :href="register()" :tabindex="showPasswordField ? 4 : 3">
                    Sign up
                </TextLink>
            </div>
        </Form>
    </AuthBase>
</template>
