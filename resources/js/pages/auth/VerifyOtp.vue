<script lang="ts" setup>
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    PinInput,
    PinInputGroup,
    PinInputSlot,
} from '@/components/ui/pin-input';
import { useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps<{
    phone_verified: boolean;
    country_code: string;
    phone: string;
}>();

const code = ref<string[]>([]);
const codeValue = computed<string>(() => code.value.join(''));

const sendForm = useForm({});
const verifyForm = useForm({
    otp_code: '',
});

const sendOtp = () => {
    sendForm.post('/verify-phone/send', {
        preserveScroll: true,
        preserveState: true,
    });
};

const submitOtp = () => {
    verifyForm.otp_code = codeValue.value;
    verifyForm.post('/verify-phone', {
        preserveScroll: true,
        onError: () => {
            code.value = [];
        },
    });
};
</script>

<template>
    <div class="mx-auto max-w-md space-y-6 p-6">
        <div class="text-center">
            <h2 class="text-2xl font-bold">Verify Your Phone Number</h2>
            <p class="mt-2 text-sm text-muted-foreground">
                {{ country_code }} {{ phone }}
            </p>
        </div>

        <div
            v-if="!phone_verified"
            class="rounded-lg border bg-yellow-50 p-4 text-center"
        >
            <p class="text-sm text-yellow-800">
                Your phone number is not verified. Please verify to use all
                features.
            </p>
        </div>

        <div v-if="!phone_verified" class="space-y-4">
            <Button
                :disabled="sendForm.processing"
                class="w-full"
                variant="outline"
                @click="sendOtp"
            >
                <LoaderCircle
                    v-if="sendForm.processing"
                    class="h-4 w-4 animate-spin"
                />
                Send Verification Code
            </Button>

            <form class="space-y-4" @submit.prevent="submitOtp">
                <div
                    class="flex flex-col items-center justify-center space-y-3 text-center"
                >
                    <Label class="text-sm font-medium" for="otp">
                        Enter verification code
                    </Label>
                    <div class="flex w-full items-center justify-center">
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

                <Button
                    :disabled="
                        verifyForm.processing || codeValue.length !== 6
                    "
                    class="w-full"
                    type="submit"
                >
                    <LoaderCircle
                        v-if="verifyForm.processing"
                        class="h-4 w-4 animate-spin"
                    />
                    Verify Phone
                </Button>
            </form>
        </div>

        <div v-else class="text-center text-green-600">
            ✓ Phone number verified
        </div>
    </div>
</template>
