<script setup lang="ts">
defineProps<{
  title: string
  value: string
  description: string
  icon: string
  tone?: 'primary' | 'success' | 'warning' | 'error'
}>()
</script>

<template>
  <UCard class="h-full">
    <div class="flex items-start justify-between gap-4">
      <div>
        <p class="text-sm text-muted">
          {{ title }}
        </p>
        <p class="mt-3 text-2xl font-semibold text-highlighted">
          {{ value }}
        </p>
        <p class="mt-2 text-sm text-toned">
          {{ description }}
        </p>
      </div>
      <div class="flex size-11 items-center justify-center rounded-xl bg-primary/10 text-primary">
        <UIcon :name="icon" class="size-5" />
      </div>
    </div>
  </UCard>
</template>
