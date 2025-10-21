<script lang="ts" setup>
import ComingSoon from '@/components/ComingSoon.vue';
import { useTranslation } from '@/composables/useTranslation';
import { BarChart3 } from 'lucide-vue-next';

const { t } = useTranslation();

const features = [
    t('coming_soon.reports.feature_1'),
    t('coming_soon.reports.feature_2'),
    t('coming_soon.reports.feature_3'),
    t('coming_soon.reports.feature_4'),
    t('coming_soon.reports.feature_5'),
];
</script>

<template>
    <ComingSoon
        :title="t('reports.title')"
        :description="t('coming_soon.reports.description')"
        :icon="BarChart3"
        :features="features"
    />
</template>
