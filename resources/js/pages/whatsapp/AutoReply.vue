<script lang="ts" setup>
import ComingSoon from '@/components/ComingSoon.vue';
import { useTranslation } from '@/composables/useTranslation';
import { MessageCircleReply } from 'lucide-vue-next';

const { t } = useTranslation();

const features = [
    t('coming_soon.auto_reply.feature_1'),
    t('coming_soon.auto_reply.feature_2'),
    t('coming_soon.auto_reply.feature_3'),
    t('coming_soon.auto_reply.feature_4'),
    t('coming_soon.auto_reply.feature_5'),
];
</script>

<template>
    <ComingSoon
        :title="t('whatsapp.auto_reply')"
        :description="t('coming_soon.auto_reply.description')"
        :icon="MessageCircleReply"
        :features="features"
    />
</template>
