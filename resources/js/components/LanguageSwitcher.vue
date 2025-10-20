<script lang="ts" setup>
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useTranslation } from '@/composables/useTranslation';
import { Languages } from 'lucide-vue-next';
import { computed } from 'vue';

const { locale, switchLocale } = useTranslation();

const languages = [
    { code: 'ar', name: 'العربية', flag: '🇸🇦' },
    { code: 'en', name: 'English', flag: '🇬🇧' },
];

const currentLanguage = computed(() => {
    return languages.find((lang) => lang.code === locale.value) || languages[0];
});
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button class="gap-2" size="sm" variant="outline">
                <Languages class="h-4 w-4" />
                <span class="hidden sm:inline">{{ currentLanguage.flag }} {{ currentLanguage.name }}</span>
                <span class="sm:hidden">{{ currentLanguage.flag }}</span>
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end">
            <DropdownMenuItem
                v-for="lang in languages"
                :key="lang.code"
                :class="{ 'bg-accent': locale === lang.code }"
                @click="switchLocale(lang.code as 'en' | 'ar')"
            >
                <span class="mr-2">{{ lang.flag }}</span>
                {{ lang.name }}
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
