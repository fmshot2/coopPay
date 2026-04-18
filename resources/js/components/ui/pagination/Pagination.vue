<script setup>
import { Link } from '@inertiajs/vue3'

defineProps({
    links: Array,
})
</script>

<template>
    <div v-if="links.length > 3">
        <div class="flex flex-wrap gap-1">
            <template v-for="(link, key) in links" :key="key">
                <div
                    v-if="link.url === null"
                    class="px-3 py-2 text-sm text-muted-foreground border rounded-md cursor-not-allowed opacity-50"
                    v-html="link.label"
                />
                <Link
                    v-else
                    class="px-3 py-2 text-sm border rounded-md transition-colors"
                    :class="[
                        link.active
                            ? 'bg-primary text-primary-foreground border-primary'
                            : 'bg-background text-foreground hover:bg-muted border-input'
                    ]"
                    :href="link.url"
                    v-html="link.label"
                />
            </template>
        </div>
    </div>
</template>
