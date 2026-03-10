<script setup lang="ts">
defineProps<{
  title: string
  summary: string
  bullets: string[]
}>()
</script>

<template>
  <UCard>
    <template #header>
      <div>
        <h2 class="text-lg font-semibold text-highlighted">
          {{ title }}
        </h2>
        <p class="mt-1 text-sm text-toned">
          {{ summary }}
        </p>
      </div>
    </template>

    <ul class="space-y-3 text-sm text-toned">
      <li v-for="bullet in bullets" :key="bullet" class="flex items-start gap-2">
        <UIcon name="i-lucide-check-circle-2" class="mt-0.5 size-4 text-primary" />
        <span>{{ bullet }}</span>
      </li>
    </ul>
  </UCard>
</template>
