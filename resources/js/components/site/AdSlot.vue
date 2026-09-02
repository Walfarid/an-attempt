<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { onMounted } from 'vue';
import { useConsent } from '@/composables/useConsent';

const { consent } = useConsent();
const page = usePage();

const clientId = page.props.adsenseClientId as string | undefined;
const slotId = page.props.adsenseSlotId as string | undefined;

const canShow = () => consent.value === 'accepted' && !!clientId && !!slotId;

onMounted(() => {
    if (canShow()) {
        try {
            (window.adsbygoogle = window.adsbygoogle || []).push({});
        } catch {
            // AdSense may throw if the slot is already filled or blocked.
        }
    }
});
</script>

<template>
    <ins
        v-if="canShow()"
        class="adsbygoogle d-sharp block w-full border border-(--rule) bg-(--surface)"
        style="display: block; min-height: 90px"
        :data-ad-client="clientId"
        :data-ad-slot="slotId"
        data-ad-format="auto"
        data-full-width-responsive="true"
    />
</template>
