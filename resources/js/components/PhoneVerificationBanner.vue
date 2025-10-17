<script lang="ts" setup>
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    PinInput,
    PinInputGroup,
    PinInputSlot,
} from '@/components/ui/pin-input';
import { router, useForm, usePage } from '@inertiajs/vue3';
import { AlertCircle, CheckCircle2, LoaderCircle, X } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

const props = defineProps<{
    phoneVerified: boolean;
    userPhone: {
        country_code: string;
        phone: string;
        full_phone: string;
    };
}>();

const page = usePage();
const showVerification = ref(!props.phoneVerified);
const verificationStep = ref<'initial' | 'verify'>('initial');

const code = ref<string[]>([]);
const codeValue = computed<string>(() => code.value.join(''));

const sendForm = useForm({});
const verifyForm = useForm({
    otp_code: '',
});

const dismissBanner = () => {
    showVerification.value = false;
};

const sendOtp = () => {
    console.log('Sending OTP to:', props.userPhone.full_phone);

    sendForm.post('/verify-phone/send', {
        preserveScroll: true,
        preserveState: true,
        onSuccess: (response) => {
            console.log('OTP sent successfully');
            verificationStep.value = 'verify';
        },
        onError: (errors) => {
            console.error('Failed to send OTP:', errors);
        },
    });
};

const submitOtp = () => {
    verifyForm.otp_code = codeValue.value;

    console.log('Verifying OTP:', verifyForm.otp_code);

    verifyForm.post('/verify-phone', {
        preserveScroll: true,
        onSuccess: () => {
            console.log('Phone verified successfully');
            showVerification.value = false;
            router.reload();
        },
        onError: (errors) => {
            console.error('Verification failed:', errors);
            code.value = [];
        },
    });
};

// Watch for flash messages
watch(
    () => page.props.flash,
    (flash: any) => {
        if (flash?.status) {
            console.log('Flash message:', flash.status);
        }
    },
    { deep: true },
);
</script>

<template>
    <div v-if="!phoneVerified && showVerification" class="mb-6">
        <Card class="border-yellow-200 bg-yellow-50">
            <CardHeader>
                <div class="flex items-start justify-between">
                    <div class="flex items-start gap-3">
                        <AlertCircle class="mt-0.5 h-5 w-5 text-yellow-600" />
                        <div>
                            <CardTitle class="text-yellow-900">
                                Verify Your Phone Number
                            </CardTitle>
                            <CardDescription class="text-yellow-700">
                                <span class="font-medium">
                                    {{ userPhone.country_code }}
                                    {{ userPhone.phone }}
                                </span>
                                - You need to verify your phone number to send
                                messages and use all features.
                            </CardDescription>
                        </div>
                    </div>
                    <Button
                        class="h-6 w-6"
                        size="icon"
                        variant="ghost"
                        @click="dismissBanner"
                    >
                        <X class="h-4 w-4" />
                    </Button>
                </div>
            </CardHeader>

            <CardContent>
                <!-- Show any flash messages -->
                <div
                    v-if="$page.props.flash?.status"
                    class="mb-4 rounded-lg bg-green-50 p-3 text-sm text-green-700"
                >
                    {{ $page.props.flash.status }}
                </div>

                <!-- Show errors if any -->
                <div
                    v-if="sendForm.errors.phone"
                    class="mb-4 rounded-lg bg-red-50 p-3 text-sm text-red-700"
                >
                    {{ sendForm.errors.phone }}
                </div>

                <div v-if="verificationStep === 'initial'" class="space-y-4">
                    <p class="text-sm text-yellow-700">
                        We'll send a verification code to your WhatsApp number:
                        <span class="font-medium">
                            {{ userPhone.country_code }} {{ userPhone.phone }}
                        </span>
                    </p>
                    <Button
                        :disabled="sendForm.processing"
                        variant="default"
                        @click="sendOtp"
                    >
                        <LoaderCircle
                            v-if="sendForm.processing"
                            class="mr-2 h-4 w-4 animate-spin"
                        />
                        {{
                            sendForm.processing
                                ? 'Sending...'
                                : 'Send Verification Code'
                        }}
                    </Button>
                </div>

                <form v-else class="space-y-4" @submit.prevent="submitOtp">
                    <div class="space-y-3">
                        <p class="text-sm font-medium text-yellow-900">
                            Enter the 6-digit code sent to your WhatsApp
                        </p>
                        <div class="flex items-center justify-start">
                            <PinInput
                                id="otp"
                                v-model="code"
                                otp
                                placeholder="○"
                                type="number"
                            >
                                <PinInputGroup>
                                    <PinInputSlot
                                        v-for="(id, index) in 6"
                                        :key="id"
                                        :disabled="verifyForm.processing"
                                        :index="index"
                                    />
                                </PinInputGroup>
                            </PinInput>
                        </div>
                        <InputError :message="verifyForm.errors.otp_code" />
                    </div>

                    <div class="flex gap-2">
                        <Button
                            :disabled="
                                verifyForm.processing || codeValue.length !== 6
                            "
                            type="submit"
                        >
                            <LoaderCircle
                                v-if="verifyForm.processing"
                                class="mr-2 h-4 w-4 animate-spin"
                            />
                            Verify Code
                        </Button>
                        <Button
                            :disabled="sendForm.processing"
                            type="button"
                            variant="outline"
                            @click="sendOtp"
                        >
                            <LoaderCircle
                                v-if="sendForm.processing"
                                class="mr-2 h-4 w-4 animate-spin"
                            />
                            Resend Code
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    </div>

    <!-- Verified Badge -->
    <div
        v-if="phoneVerified"
        class="mb-6 flex items-center gap-2 rounded-lg border border-green-200 bg-green-50 p-4"
    >
        <CheckCircle2 class="h-5 w-5 text-green-600" />
        <p class="text-sm text-green-900">
            <span class="font-medium">Phone Verified:</span>
            {{ userPhone.country_code }} {{ userPhone.phone }}
        </p>
    </div>
</template>
