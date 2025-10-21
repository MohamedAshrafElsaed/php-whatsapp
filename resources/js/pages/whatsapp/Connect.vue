<script lang="ts" setup>
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { useTranslation } from '@/composables/useTranslation';
import axios from 'axios';
import {
    Plus,
    ShieldCheck,
    Star,
    Trash2,
    Smartphone,
    RefreshCw,
    Timer,
    QrCode,
    Hash,
    X,
    Check,
    AlertCircle,
    Info
} from 'lucide-vue-next';
import { onMounted, onUnmounted, ref, computed } from 'vue';

interface Session {
    id: number;
    device_id: string;
    device_label: string;
    is_primary: boolean;
    status: 'pending' | 'connected' | 'expired' | 'disconnected';
    meta_json: {
        qr_link?: string;
        phone?: string;
        name?: string;
        platform?: string;
        pairing_code?: string;
        pairing_phone?: string;
        method?: 'qr' | 'pairing';
        qr_duration?: number;
    };
    bridge_instance_url?: string;
    bridge_instance_port?: number;
    last_seen_at: string;
    expires_at: string;
    created_at: string;
}

const props = defineProps<{
    sessions: Session[];
    maxDevices: number;
}>();

const { t, isRTL } = useTranslation();

const currentSessions = ref(props.sessions);
const polling = ref<number | null>(null);
const isLoading = ref(false);
const showAddDevice = ref(false);
const connectionMethod = ref<'qr' | 'pairing'>('qr');
const pairingPhone = ref('');
const deviceLabel = ref('');
const qrCode = ref<string | null>(null);
const pairingCode = ref<string | null>(null);
const currentDeviceId = ref<string | null>(null);

// Add these new refs after the existing ones (around line 60)
const reconnectingDeviceId = ref<string | null>(null);
const showReconnectModal = ref(false);
const reconnectMethod = ref<'qr' | 'pairing'>('qr');
const reconnectPhone = ref('');

// QR Expiry Tracking
const qrTimeRemaining = ref<number>(0);
const qrExpiryInterval = ref<number | null>(null);

const statusColor = {
    pending: 'secondary',
    connected: 'default',
    expired: 'destructive',
    disconnected: 'outline',
};

const connectedCount = computed(() => {
    return currentSessions.value.filter((s) => s.status === 'connected').length;
});

const canAddDevice = computed(() => {
    return currentSessions.value.length < props.maxDevices;
});

const isQrExpired = computed(() => {
    return qrTimeRemaining.value <= 0 && qrCode.value !== null;
});

const formatPhoneNumber = (phone: string | undefined): string => {
    if (!phone) return 'N/A';
    const cleanPhone = phone.replace(/:.*@s\.whatsapp\.net$/, '').replace('@s.whatsapp.net', '');
    if (cleanPhone.startsWith('+')) {
        return cleanPhone;
    }
    return `+${cleanPhone}`;
};

const startQrExpiryCountdown = (expiresIn: number) => {
    stopQrExpiryCountdown();
    qrTimeRemaining.value = expiresIn;

    qrExpiryInterval.value = window.setInterval(() => {
        qrTimeRemaining.value--;
        if (qrTimeRemaining.value <= 0) {
            stopQrExpiryCountdown();
        }
    }, 1000);
};

const stopQrExpiryCountdown = () => {
    if (qrExpiryInterval.value) {
        clearInterval(qrExpiryInterval.value);
        qrExpiryInterval.value = null;
    }
    qrTimeRemaining.value = 0;
};

const refreshQrCode = async () => {
    if (!currentDeviceId.value || isLoading.value) return;

    isLoading.value = true;
    try {
        const response = await axios.post(
            `/w/session/${currentDeviceId.value}/refresh-qr`,
        );

        if (response.data.success) {
            qrCode.value = response.data.qr_code;
            startQrExpiryCountdown(response.data.expires_in || 30);
        } else {
            alert(t('whatsapp.qr_refresh_failed'));
        }
    } catch (error: any) {
        console.error('QR refresh error:', error);
        alert(error.response?.data?.error || t('whatsapp.qr_refresh_failed'));
    } finally {
        isLoading.value = false;
    }
};

const generateQrCode = async () => {
    if (isLoading.value) return;

    isLoading.value = true;
    qrCode.value = null;
    stopQrExpiryCountdown();

    try {
        const response = await axios.post('/w/session/qr', {
            device_label: deviceLabel.value || undefined,
        });

        if (response.data.success) {
            qrCode.value = response.data.qr_code;
            currentDeviceId.value = response.data.device_id;
            startQrExpiryCountdown(response.data.expires_in || 30);
            startPolling();
        } else {
            alert(t('whatsapp.qr_generate_failed'));
        }
    } catch (error: any) {
        console.error('QR generation error:', error);
        alert(error.response?.data?.error || t('whatsapp.qr_generate_failed'));
    } finally {
        isLoading.value = false;
    }
};

// Add this function after the existing functions (around line 180)
const startReconnection = (session: Session) => {
    reconnectingDeviceId.value = session.device_id;
    showReconnectModal.value = true;
    reconnectMethod.value = 'qr';
    reconnectPhone.value = '';
    qrCode.value = null;
    pairingCode.value = null;
    stopQrExpiryCountdown();
};

const executeReconnection = async () => {
    if (!reconnectingDeviceId.value || isLoading.value) return;

    if (reconnectMethod.value === 'pairing' && (!reconnectPhone.value || reconnectPhone.value.length < 10)) {
        alert(t('whatsapp.invalid_phone'));
        return;
    }

    isLoading.value = true;

    try {
        const response = await axios.post(
            `/w/session/${reconnectingDeviceId.value}/reconnect`,
            {
                method: reconnectMethod.value,
                phone: reconnectPhone.value || undefined,
            }
        );

        if (response.data.success) {
            currentDeviceId.value = reconnectingDeviceId.value;

            if (reconnectMethod.value === 'qr') {
                qrCode.value = response.data.qr_code;
                startQrExpiryCountdown(response.data.expires_in || 30);
            } else {
                pairingCode.value = response.data.pairing_code;
            }

            showReconnectModal.value = false;
            showAddDevice.value = true;
            connectionMethod.value = reconnectMethod.value;
            startPolling();
        } else {
            alert(response.data.error || t('whatsapp.reconnection_failed'));
        }
    } catch (error: any) {
        console.error('Reconnection error:', error);
        alert(error.response?.data?.error || t('whatsapp.reconnection_failed'));
    } finally {
        isLoading.value = false;
    }
};

const cancelReconnection = () => {
    showReconnectModal.value = false;
    reconnectingDeviceId.value = null;
    reconnectPhone.value = '';
};

const generatePairingCode = async () => {
    if (isLoading.value) return;

    if (!pairingPhone.value || pairingPhone.value.length < 10) {
        alert(t('whatsapp.invalid_phone'));
        return;
    }

    isLoading.value = true;
    pairingCode.value = null;

    try {
        const response = await axios.post('/w/session/pairing', {
            phone: pairingPhone.value,
            device_label: deviceLabel.value || undefined,
        });

        if (response.data.success) {
            pairingCode.value = response.data.pairing_code;
            currentDeviceId.value = response.data.device_id;
            startPolling();
        } else {
            alert(t('whatsapp.pairing_generate_failed'));
        }
    } catch (error: any) {
        console.error('Pairing generation error:', error);
        alert(error.response?.data?.error || t('whatsapp.pairing_generate_failed'));
    } finally {
        isLoading.value = false;
    }
};

const disconnectDevice = (deviceId: string) => {
    if (confirm(t('whatsapp.disconnect_confirm'))) {
        isLoading.value = true;
        router.delete(`/w/session/${deviceId}`, {
            onFinish: () => {
                isLoading.value = false;
            },
        });
    }
};

const setPrimaryDevice = async (deviceId: string) => {
    try {
        const response = await axios.post(`/w/session/${deviceId}/set-primary`);
        if (response.data.success) {
            pollStatus();
        }
    } catch (error: any) {
        alert(t('whatsapp.set_primary_failed'));
    }
};

const pollStatus = async () => {
    try {
        const response = await axios.get('/w/session/status');
        currentSessions.value = response.data.sessions;

        if (currentDeviceId.value) {
            const currentSession = currentSessions.value.find(
                (s) => s.device_id === currentDeviceId.value,
            );
            if (currentSession?.status === 'connected') {
                stopPolling();
                stopQrExpiryCountdown();
                resetAddDevice();
                alert(t('whatsapp.connected_success'));
            }
        }
    } catch (error) {
        console.error('Polling error:', error);
    }
};

const startPolling = () => {
    stopPolling();
    pollStatus();
    polling.value = window.setInterval(pollStatus, 3000);
};

const stopPolling = () => {
    if (polling.value) {
        clearInterval(polling.value);
        polling.value = null;
    }
};

const resetAddDevice = () => {
    showAddDevice.value = false;
    qrCode.value = null;
    pairingCode.value = null;
    deviceLabel.value = '';
    pairingPhone.value = '';
    currentDeviceId.value = null;
    stopQrExpiryCountdown();
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleString(isRTL ? 'ar-EG' : 'en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

onMounted(() => {
    const pendingSessions = currentSessions.value.filter((s) => s.status === 'pending');
    if (pendingSessions.length > 0) {
        startPolling();
    }
});

onUnmounted(() => {
    stopPolling();
    stopQrExpiryCountdown();
});
</script>

<template>
    <AppLayout>
        <Head :title="t('whatsapp.devices_title')" />

        <div class="min-h-screen bg-background">
            <div class="mx-auto max-w-6xl space-y-4 p-4 sm:space-y-6 sm:p-6 lg:p-8">
                <!-- Trust Badge -->
                <div class="rounded-lg border-2 border-blue-200 bg-gradient-to-r from-blue-50 to-blue-100 p-4 shadow-sm dark:border-blue-800 dark:from-blue-950/50 dark:to-blue-900/50">
                    <div class="flex items-start gap-3">
                        <div class="shrink-0 rounded-lg bg-blue-200 p-2 dark:bg-blue-900">
                            <ShieldCheck class="h-5 w-5 text-blue-700 dark:text-blue-300" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <h3 class="font-semibold text-blue-900 dark:text-blue-100">
                                {{ t('whatsapp.secure_connection') }}
                            </h3>
                            <p class="text-sm text-blue-700 dark:text-blue-300">
                                {{ t('whatsapp.max_devices', { count: maxDevices }) }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Header -->
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="space-y-1">
                        <h1 class="text-2xl font-bold tracking-tight sm:text-3xl">
                            {{ t('whatsapp.devices_title') }}
                        </h1>
                        <p class="text-sm text-muted-foreground">
                            {{ t('whatsapp.connected_status', {
                            connected: connectedCount,
                            total: currentSessions.length
                        }) }}
                        </p>
                    </div>

                    <Button
                        v-if="canAddDevice && !showAddDevice"
                        :disabled="isLoading"
                        class="w-full sm:w-auto"
                        @click="showAddDevice = true"
                    >
                        <Plus class="h-4 w-4 ltr:mr-2 rtl:ml-2" />
                        {{ t('whatsapp.add_device') }}
                    </Button>
                </div>

                <!-- Add New Device Card -->
                <Card v-if="showAddDevice" class="shadow-sm">
                    <CardHeader class="p-4 sm:p-6">
                        <div class="flex items-start justify-between gap-3">
                            <div class="space-y-1">
                                <CardTitle class="text-lg sm:text-xl">
                                    {{ t('whatsapp.add_new_device') }}
                                </CardTitle>
                                <CardDescription class="text-sm">
                                    {{ t('whatsapp.connect_another_account') }}
                                </CardDescription>
                            </div>
                            <Button size="icon" variant="ghost" class="h-8 w-8 shrink-0" @click="resetAddDevice">
                                <X class="h-4 w-4" />
                                <span class="sr-only">{{ t('common.close') }}</span>
                            </Button>
                        </div>
                    </CardHeader>
                    <CardContent class="space-y-4 p-4 sm:p-6">
                        <!-- Device Label -->
                        <div class="space-y-2">
                            <Label for="device-label">
                                {{ t('whatsapp.device_label') }}
                                <span class="text-xs text-muted-foreground">({{ t('common.optional') }})</span>
                            </Label>
                            <Input
                                id="device-label"
                                v-model="deviceLabel"
                                :disabled="isLoading"
                                :placeholder="t('whatsapp.device_label_placeholder')"
                                class="h-10 sm:h-11"
                            />
                        </div>

                        <!-- Method Selector -->
                        <div class="space-y-2">
                            <Label>{{ t('whatsapp.connection_method') }}</Label>
                            <div class="grid grid-cols-2 gap-2">
                                <Button
                                    :variant="connectionMethod === 'qr' ? 'default' : 'outline'"
                                    class="w-full"
                                    @click="
                                        connectionMethod = 'qr';
                                        pairingCode = null;
                                    "
                                >
                                    <QrCode class="h-4 w-4 ltr:mr-2 rtl:ml-2" />
                                    {{ t('whatsapp.qr_code') }}
                                </Button>
                                <Button
                                    :variant="connectionMethod === 'pairing' ? 'default' : 'outline'"
                                    class="w-full"
                                    @click="
                                        connectionMethod = 'pairing';
                                        qrCode = null;
                                        stopQrExpiryCountdown();
                                    "
                                >
                                    <Hash class="h-4 w-4 ltr:mr-2 rtl:ml-2" />
                                    {{ t('whatsapp.pairing_code') }}
                                </Button>
                            </div>
                        </div>

                        <!-- QR Code Method -->
                        <div v-if="connectionMethod === 'qr'" class="space-y-4">
                            <div v-if="qrCode" class="flex flex-col items-center space-y-4">
                                <div class="relative">
                                    <img
                                        :src="qrCode"
                                        :alt="t('whatsapp.qr_code_alt')"
                                        class="h-48 w-48 rounded-lg border shadow-sm sm:h-64 sm:w-64"
                                        :class="{ 'opacity-50 grayscale': isQrExpired }"
                                    />
                                    <div
                                        v-if="isQrExpired"
                                        class="absolute inset-0 flex items-center justify-center rounded-lg bg-black/60"
                                    >
                                        <Button
                                            size="sm"
                                            :disabled="isLoading"
                                            @click="refreshQrCode"
                                        >
                                            <RefreshCw
                                                class="h-4 w-4 ltr:mr-2 rtl:ml-2"
                                                :class="{ 'animate-spin': isLoading }"
                                            />
                                            {{ t('whatsapp.refresh') }}
                                        </Button>
                                    </div>
                                </div>

                                <!-- Timer -->
                                <div
                                    class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium"
                                    :class="{
                                        'bg-red-100 text-red-700 dark:bg-red-950/50 dark:text-red-400': qrTimeRemaining <= 10,
                                        'bg-yellow-100 text-yellow-700 dark:bg-yellow-950/50 dark:text-yellow-400': qrTimeRemaining > 10 && qrTimeRemaining <= 20,
                                        'bg-green-100 text-green-700 dark:bg-green-950/50 dark:text-green-400': qrTimeRemaining > 20
                                    }"
                                >
                                    <Timer class="h-4 w-4 shrink-0" />
                                    <span>
                                        {{ isQrExpired ? t('whatsapp.qr_expired') : t('whatsapp.expires_in', { seconds: qrTimeRemaining }) }}
                                    </span>
                                </div>

                                <Button
                                    v-if="!isQrExpired"
                                    size="sm"
                                    variant="outline"
                                    :disabled="isLoading"
                                    @click="refreshQrCode"
                                >
                                    <RefreshCw
                                        class="h-4 w-4 ltr:mr-2 rtl:ml-2"
                                        :class="{ 'animate-spin': isLoading }"
                                    />
                                    {{ t('whatsapp.refresh_now') }}
                                </Button>

                                <div class="rounded-lg border-2 border-blue-200 bg-blue-50 p-3 dark:border-blue-800 dark:bg-blue-950/50">
                                    <p class="flex items-start gap-2 text-xs text-blue-700 dark:text-blue-300">
                                        <Info class="mt-0.5 h-3.5 w-3.5 shrink-0" />
                                        <span>{{ t('whatsapp.qr_instructions') }}</span>
                                    </p>
                                </div>
                            </div>

                            <Button
                                v-if="!qrCode"
                                class="w-full"
                                :disabled="isLoading"
                                @click="generateQrCode"
                            >
                                <QrCode class="h-4 w-4 ltr:mr-2 rtl:ml-2" />
                                {{ isLoading ? t('whatsapp.generating') : t('whatsapp.generate_qr') }}
                            </Button>
                        </div>

                        <!-- Pairing Code Method -->
                        <div v-if="connectionMethod === 'pairing'" class="space-y-4">
                            <div class="space-y-2">
                                <Label for="pairing-phone">
                                    {{ t('whatsapp.phone_with_country') }}
                                </Label>
                                <Input
                                    id="pairing-phone"
                                    v-model="pairingPhone"
                                    :disabled="isLoading"
                                    :placeholder="t('whatsapp.phone_placeholder')"
                                    type="tel"
                                    class="h-10 sm:h-11"
                                />
                            </div>

                            <div v-if="pairingCode" class="flex flex-col items-center space-y-4">
                                <div class="rounded-lg bg-gradient-to-br from-primary/10 to-primary/20 px-6 py-4 shadow-inner sm:px-8 sm:py-6">
                                    <p class="font-mono text-3xl font-bold tracking-wider sm:text-4xl">
                                        {{ pairingCode }}
                                    </p>
                                </div>
                                <div class="rounded-lg border-2 border-green-200 bg-green-50 p-3 dark:border-green-800 dark:bg-green-950/50">
                                    <p class="flex items-start gap-2 text-center text-sm text-green-700 dark:text-green-300">
                                        <Check class="mt-0.5 h-4 w-4 shrink-0" />
                                        <span>{{ t('whatsapp.enter_code_on', { phone: pairingPhone }) }}</span>
                                    </p>
                                </div>
                            </div>

                            <Button
                                class="w-full"
                                :disabled="isLoading"
                                @click="generatePairingCode"
                            >
                                <Hash class="h-4 w-4 ltr:mr-2 rtl:ml-2" />
                                {{ isLoading ? t('whatsapp.generating') : t('whatsapp.generate_pairing') }}
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                <!-- Connected Devices Grid -->
                <div v-if="currentSessions.length > 0" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <Card v-for="session in currentSessions" :key="session.id" class="overflow-hidden shadow-sm">
                        <CardHeader class="space-y-0 p-4 pb-3">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex min-w-0 flex-1 items-start gap-2">
                                    <div class="shrink-0 rounded-lg bg-purple-100 p-2 dark:bg-purple-950">
                                        <Smartphone class="h-4 w-4 text-purple-600 dark:text-purple-400" />
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-1.5">
                                            <CardTitle class="truncate text-base">
                                                {{ session.device_label }}
                                            </CardTitle>
                                            <Star
                                                v-if="session.is_primary"
                                                class="h-3.5 w-3.5 shrink-0 fill-yellow-400 text-yellow-400"
                                                :title="t('whatsapp.primary_device')"
                                            />
                                        </div>
                                    </div>
                                </div>
                                <Badge
                                    :variant="statusColor[session.status]"
                                    class="shrink-0 text-[10px]"
                                >
                                    {{ t(`whatsapp.status_${session.status}`) }}
                                </Badge>
                            </div>
                        </CardHeader>
                        <CardContent class="space-y-3 p-4 pt-0">
                            <!-- Connected Info -->
                            <div
                                v-if="session.status === 'connected' && session.meta_json"
                                class="space-y-2 rounded-lg bg-muted/50 p-3"
                            >
                                <div class="flex items-start gap-2 text-sm">
                                    <span class="shrink-0 font-medium">{{ t('whatsapp.name') }}:</span>
                                    <span class="truncate text-muted-foreground">
                                        {{ session.meta_json.name || 'N/A' }}
                                    </span>
                                </div>
                                <div class="flex items-start gap-2 text-sm">
                                    <span class="shrink-0 font-medium">{{ t('whatsapp.phone') }}:</span>
                                    <span class="truncate text-muted-foreground">
                                        {{ formatPhoneNumber(session.meta_json.phone) }}
                                    </span>
                                </div>
                                <div
                                    v-if="session.last_seen_at"
                                    class="text-xs text-muted-foreground"
                                >
                                    {{ t('whatsapp.last_seen') }}: {{ formatDate(session.last_seen_at) }}
                                </div>
                            </div>

                            <!-- Pending State -->
                            <div
                                v-if="session.status === 'pending'"
                                class="flex items-start gap-2 rounded-lg bg-yellow-50 p-3 text-sm text-yellow-700 dark:bg-yellow-950/50 dark:text-yellow-300"
                            >
                                <AlertCircle class="mt-0.5 h-4 w-4 shrink-0" />
                                <span>{{ t('whatsapp.waiting_connection') }}</span>
                            </div>

                            <!-- Expired State -->
                            <div
                                v-if="session.status === 'expired'"
                                class="flex items-start gap-2 rounded-lg bg-red-50 p-3 text-sm text-red-700 dark:bg-red-950/50 dark:text-red-300"
                            >
                                <AlertCircle class="mt-0.5 h-4 w-4 shrink-0" />
                                <span>{{ t('whatsapp.connection_expired') }}</span>
                            </div>

                            <!-- Actions -->
                            <div class="flex flex-col gap-2 sm:flex-row">
                                <!-- Reconnect Button for Pending/Expired/Disconnected -->
                                <Button
                                    v-if="session.status === 'pending' || session.status === 'expired' || session.status === 'disconnected'"
                                    size="sm"
                                    variant="default"
                                    class="flex-1 text-xs"
                                    :disabled="isLoading"
                                    @click="startReconnection(session)"
                                >
                                    <RefreshCw class="h-3.5 w-3.5 ltr:mr-1.5 rtl:ml-1.5" />
                                    {{ t('whatsapp.reconnect') }}
                                </Button>

                                <!-- Set Primary Button -->
                                <Button
                                    v-if="session.status === 'connected' && !session.is_primary"
                                    size="sm"
                                    variant="outline"
                                    class="flex-1 text-xs"
                                    @click="setPrimaryDevice(session.device_id)"
                                >
                                    <Star class="h-3.5 w-3.5 ltr:mr-1.5 rtl:ml-1.5" />
                                    {{ t('whatsapp.set_primary') }}
                                </Button>

                                <!-- Disconnect Button -->
                                <Button
                                    v-if="session.status === 'connected'"
                                    size="sm"
                                    variant="destructive"
                                    class="flex-1 text-xs"
                                    :disabled="isLoading"
                                    @click="disconnectDevice(session.device_id)"
                                >
                                    <Trash2 class="h-3.5 w-3.5 ltr:mr-1.5 rtl:ml-1.5" />
                                    {{ t('whatsapp.disconnect') }}
                                </Button>

                                <!-- Force Delete Button (for pending/expired/disconnected) -->
                                <Button
                                    v-if="session.status === 'pending' || session.status === 'expired' || session.status === 'disconnected'"
                                    size="sm"
                                    variant="destructive"
                                    class="flex-1 text-xs"
                                    :disabled="isLoading"
                                    @click="forceDeleteDevice(session.device_id)"
                                >
                                    <Trash2 class="h-3.5 w-3.5 ltr:mr-1.5 rtl:ml-1.5" />
                                    {{ t('whatsapp.delete') }}
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Empty State -->
                <Card v-else class="shadow-sm">
                    <CardContent class="flex flex-col items-center justify-center py-12">
                        <div class="mb-4 rounded-full bg-muted p-4">
                            <Smartphone class="h-10 w-10 text-muted-foreground sm:h-12 sm:w-12" />
                        </div>
                        <h3 class="mb-2 text-center text-lg font-semibold">
                            {{ t('whatsapp.no_devices') }}
                        </h3>
                        <p class="mb-4 text-center text-sm text-muted-foreground">
                            {{ t('whatsapp.add_first_device') }}
                        </p>
                        <Button class="w-full sm:w-auto" @click="showAddDevice = true">
                            <Plus class="h-4 w-4 ltr:mr-2 rtl:ml-2" />
                            {{ t('whatsapp.add_device') }}
                        </Button>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>

    <!-- Reconnection Modal -->
    <div
        v-if="showReconnectModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
        @click.self="cancelReconnection"
    >
        <Card class="w-full max-w-md shadow-lg">
            <CardHeader class="p-4 sm:p-6">
                <div class="flex items-start justify-between gap-3">
                    <div class="space-y-1">
                        <CardTitle class="text-lg sm:text-xl">
                            {{ t('whatsapp.reconnect_device') }}
                        </CardTitle>
                        <CardDescription class="text-sm">
                            {{ t('whatsapp.choose_reconnection_method') }}
                        </CardDescription>
                    </div>
                    <Button
                        size="icon"
                        variant="ghost"
                        class="h-8 w-8 shrink-0"
                        @click="cancelReconnection"
                    >
                        <X class="h-4 w-4" />
                        <span class="sr-only">{{ t('common.close') }}</span>
                    </Button>
                </div>
            </CardHeader>
            <CardContent class="space-y-4 p-4 sm:p-6">
                <!-- Method Selector -->
                <div class="space-y-2">
                    <Label>{{ t('whatsapp.connection_method') }}</Label>
                    <div class="grid grid-cols-2 gap-2">
                        <Button
                            :variant="reconnectMethod === 'qr' ? 'default' : 'outline'"
                            class="w-full"
                            @click="reconnectMethod = 'qr'"
                        >
                            <QrCode class="h-4 w-4 ltr:mr-2 rtl:ml-2" />
                            {{ t('whatsapp.qr_code') }}
                        </Button>
                        <Button
                            :variant="reconnectMethod === 'pairing' ? 'default' : 'outline'"
                            class="w-full"
                            @click="reconnectMethod = 'pairing'"
                        >
                            <Hash class="h-4 w-4 ltr:mr-2 rtl:ml-2" />
                            {{ t('whatsapp.pairing_code') }}
                        </Button>
                    </div>
                </div>

                <!-- Phone input for pairing -->
                <div v-if="reconnectMethod === 'pairing'" class="space-y-2">
                    <Label for="reconnect-phone">
                        {{ t('whatsapp.phone_with_country') }}
                    </Label>
                    <Input
                        id="reconnect-phone"
                        v-model="reconnectPhone"
                        :disabled="isLoading"
                        :placeholder="t('whatsapp.phone_placeholder')"
                        type="tel"
                        class="h-10 sm:h-11"
                    />
                </div>

                <!-- Action Buttons -->
                <div class="flex gap-2">
                    <Button
                        variant="outline"
                        class="flex-1"
                        :disabled="isLoading"
                        @click="cancelReconnection"
                    >
                        {{ t('common.cancel') }}
                    </Button>
                    <Button
                        class="flex-1"
                        :disabled="isLoading"
                        @click="executeReconnection"
                    >
                        {{ isLoading ? t('whatsapp.reconnecting') : t('whatsapp.reconnect') }}
                    </Button>
                </div>
            </CardContent>
        </Card>
    </div>
</template>

<style scoped>
/* Cairo font for Arabic */
:root[dir="rtl"] {
    font-family: 'Cairo', sans-serif;
}

/* Inter font for English */
:root[dir="ltr"] {
    font-family: 'Inter', sans-serif;
}

/* Smooth transitions */
* {
    transition-property: color, background-color, border-color, text-decoration-color, fill, stroke;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    transition-duration: 150ms;
}
</style>
