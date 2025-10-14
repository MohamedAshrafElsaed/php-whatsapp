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
import { login } from '@/routes';
import { Form, Head } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';
import { ref, watch } from 'vue';

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
</script>

<template>
    <AuthBase
        description="Enter your details below to create your account"
        title="Create an account"
    >
        <Head title="Register" />

        <Form
            v-slot="{ errors, processing }"
            :reset-on-success="['password', 'password_confirmation']"
            class="flex flex-col gap-6"
            v-bind="RegisteredUserController.store.form()"
        >
            <div class="grid gap-6">
                <div class="grid gap-2">
                    <Label for="name">Full Name</Label>
                    <Input
                        id="name"
                        :tabindex="1"
                        autocomplete="name"
                        autofocus
                        name="name"
                        placeholder="John Doe"
                        required
                        type="text"
                    />
                    <InputError :message="errors.name" />
                </div>

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
                            :tabindex="2"
                            autocomplete="tel"
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
                    <p class="text-xs text-muted-foreground">
                        ⚠️ Enter phone number without country code. You must
                        have WhatsApp on this number.
                    </p>
                    <InputError :message="errors.country_code" />
                    <InputError :message="errors.phone" />
                </div>

                <div class="grid gap-2">
                    <Label for="password">Password</Label>
                    <Input
                        id="password"
                        :tabindex="3"
                        autocomplete="new-password"
                        name="password"
                        placeholder="Password"
                        required
                        type="password"
                    />
                    <InputError :message="errors.password" />
                </div>

                <div class="grid gap-2">
                    <Label for="password_confirmation">Confirm Password</Label>
                    <Input
                        id="password_confirmation"
                        :tabindex="4"
                        autocomplete="new-password"
                        name="password_confirmation"
                        placeholder="Confirm password"
                        required
                        type="password"
                    />
                    <InputError :message="errors.password_confirmation" />
                </div>

                <Button
                    :disabled="processing || !!phoneError"
                    class="mt-2 w-full"
                    data-test="register-user-button"
                    tabindex="5"
                    type="submit"
                >
                    <LoaderCircle
                        v-if="processing"
                        class="h-4 w-4 animate-spin"
                    />
                    Create account
                </Button>
            </div>

            <div class="text-center text-sm text-muted-foreground">
                Already have an account?
                <TextLink
                    :href="login()"
                    :tabindex="6"
                    class="underline underline-offset-4"
                >
                    Log in
                </TextLink>
            </div>
        </Form>
    </AuthBase>
</template>
