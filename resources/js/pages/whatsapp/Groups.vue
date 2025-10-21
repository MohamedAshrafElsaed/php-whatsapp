<script lang="ts" setup>
import ComingSoon from '@/components/ComingSoon.vue';
import { useTranslation } from '@/composables/useTranslation';
import { UsersRound } from 'lucide-vue-next';

const { t } = useTranslation();

const features = [
    t('coming_soon.groups.feature_1'),
    t('coming_soon.groups.feature_2'),
    t('coming_soon.groups.feature_3'),
    t('coming_soon.groups.feature_4'),
    t('coming_soon.groups.feature_5'),
];
</script>

<template>
    <ComingSoon
        :title="t('whatsapp.groups')"
        :description="t('coming_soon.groups.description')"
        :icon="UsersRound"
        :features="features"
    />
</template>
