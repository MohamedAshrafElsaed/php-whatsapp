<script lang="ts" setup>
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import { edit } from '@/routes/profile';
import { send } from '@/routes/verification';
import { Form, Head, Link, usePage } from '@inertiajs/vue3';

import DeleteUser from '@/components/DeleteUser.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
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
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem } from '@/types';
import { ref, computed } from 'vue';

interface Props {
    mustVerifyEmail: boolean;
    status?: string;
}

defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Profile settings',
        href: edit().url,
    },
];

const page = usePage();
const user = page.props.auth.user;

const industries = [
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

const industrySearch = ref('');
const selectedIndustry = ref(user.industry || '');

// Filter industries based on search
const filteredIndustries = computed(() => {
    if (!industrySearch.value) return industries;
    return industries.filter(industry =>
        industry.toLowerCase().includes(industrySearch.value.toLowerCase())
    );
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Profile settings" />

        <SettingsLayout>
            <div class="flex flex-col space-y-6">
                <HeadingSmall
                    description="Update your profile information"
                    title="Profile information"
                />

                <Form
                    v-slot="{ errors, processing, recentlySuccessful }"
                    class="space-y-6"
                    v-bind="ProfileController.update.form()"
                >
                    <div class="grid gap-2">
                        <Label for="first_name">First Name</Label>
                        <Input
                            id="first_name"
                            :default-value="user.first_name"
                            autocomplete="given-name"
                            class="mt-1 block w-full"
                            name="first_name"
                            placeholder="First name"
                            required
                        />
                        <InputError :message="errors.first_name" class="mt-2" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="last_name">Last Name</Label>
                        <Input
                            id="last_name"
                            :default-value="user.last_name"
                            autocomplete="family-name"
                            class="mt-1 block w-full"
                            name="last_name"
                            placeholder="Last name"
                            required
                        />
                        <InputError :message="errors.last_name" class="mt-2" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="email">Email address (Optional)</Label>
                        <Input
                            id="email"
                            :default-value="user.email"
                            autocomplete="username"
                            class="mt-1 block w-full"
                            name="email"
                            placeholder="Email address"
                            type="email"
                        />
                        <InputError :message="errors.email" class="mt-2" />
                    </div>

                    <div v-if="mustVerifyEmail && user.email && !user.email_verified_at">
                        <p class="-mt-4 text-sm text-muted-foreground">
                            Your email address is unverified.
                            <Link
                                :href="send()"
                                as="button"
                                class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                            >
                                Click here to resend the verification email.
                            </Link>
                        </p>

                        <div
                            v-if="status === 'verification-link-sent'"
                            class="mt-2 text-sm font-medium text-green-600"
                        >
                            A new verification link has been sent to your email
                            address.
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <Label for="industry">Industry (Optional)</Label>
                        <Select v-model="selectedIndustry" name="industry">
                            <SelectTrigger>
                                <SelectValue placeholder="Select your industry" />
                            </SelectTrigger>
                            <SelectContent>
                                <div class="p-2">
                                    <Input
                                        v-model="industrySearch"
                                        placeholder="Search industries..."
                                        class="mb-2"
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
                        <InputError :message="errors.industry" class="mt-2" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="phone">Phone Number</Label>
                        <Input
                            id="phone"
                            :default-value="`${user.country_code} ${user.phone}`"
                            class="mt-1 block w-full bg-muted"
                            disabled
                            readonly
                            type="text"
                        />
                        <p class="text-xs text-muted-foreground">
                            Phone number cannot be changed. Contact support if needed.
                        </p>
                    </div>

                    <div class="flex items-center gap-4">
                        <Button
                            :disabled="processing"
                            data-test="update-profile-button"
                        >Save
                        </Button>

                        <Transition
                            enter-active-class="transition ease-in-out"
                            enter-from-class="opacity-0"
                            leave-active-class="transition ease-in-out"
                            leave-to-class="opacity-0"
                        >
                            <p
                                v-show="recentlySuccessful"
                                class="text-sm text-neutral-600"
                            >
                                Saved.
                            </p>
                        </Transition>
                    </div>
                </Form>
            </div>

            <DeleteUser />
        </SettingsLayout>
    </AppLayout>
</template>
