<script setup>
import {
    CircleCheckIcon,
    InfoIcon,
    Loader2Icon,
    OctagonXIcon,
    TriangleAlertIcon,
    XIcon,
} from "lucide-vue-next";
import { Toaster as Sonner } from "vue-sonner";
import { cn } from "@/lib/utils";

const props = defineProps({
    id: { type: String, required: false },
    invert: { type: Boolean, required: false },
    theme: { type: String, required: false },
    position: { type: String, required: false },
    closeButtonPosition: { type: String, required: false },
    hotkey: { type: Array, required: false },
    richColors: { type: Boolean, required: false },
    expand: { type: Boolean, required: false },
    duration: { type: Number, required: false },
    gap: { type: Number, required: false },
    visibleToasts: { type: Number, required: false },
    closeButton: { type: Boolean, required: false },
    toastOptions: { type: Object, required: false },
    class: { type: String, required: false },
    style: { type: Object, required: false },
    offset: { type: [Object, String, Number], required: false },
    mobileOffset: { type: [Object, String, Number], required: false },
    dir: { type: String, required: false },
    swipeDirections: { type: Array, required: false },
    icons: { type: Object, required: false },
    containerAriaLabel: { type: String, required: false },
});
</script>

<template>
    <Sonner v-bind="props" :class="cn('toaster group', props.class)" position="top-center" theme="light" :style="{
        '--normal-bg': 'hsl(var(--popover))',
        '--normal-text': 'hsl(var(--popover-foreground))',
        '--normal-border': 'hsl(var(--border))',
        // '--success-bg': 'hsl(var(--popover))',
        // '--success-text': 'hsl(var(--popover-foreground))',
        // '--success-border': 'hsl(var(--border))',
        '--success-bg': '#16a34a',
        '--success-text': '#ffffff',
        '--success-border': '#15803d',
        '--error-bg': 'hsl(var(--popover))',
        '--error-text': 'hsl(var(--popover-foreground))',
        '--error-border': 'hsl(var(--border))',
        '--border-radius': 'var(--radius)',
    }">
        <template #success-icon>
            <CircleCheckIcon class="size-4" />
        </template>
        <template #info-icon>
            <InfoIcon class="size-4" />
        </template>
        <template #warning-icon>
            <TriangleAlertIcon class="size-4" />
        </template>
        <template #error-icon>
            <OctagonXIcon class="size-4" />
        </template>
        <template #loading-icon>
            <div>
                <Loader2Icon class="size-4 animate-spin" />
            </div>
        </template>
        <template #close-icon>
            <XIcon class="size-4" />
        </template>
    </Sonner>
</template>
