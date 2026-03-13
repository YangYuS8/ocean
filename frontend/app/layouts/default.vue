<script setup lang="ts">
const { sidebarOpen, primaryLinks, supportLinks, commandGroups } = useWorkspace()
</script>

<template>
  <UDashboardGroup unit="rem">
    <UDashboardSidebar
      id="workspace"
      v-model:open="sidebarOpen"
      collapsible
      resizable
      class="bg-white/70 backdrop-blur"
      :ui="{ footer: 'lg:border-t lg:border-default' }"
    >
      <template #header="{ collapsed }">
        <div class="flex min-w-0 items-center gap-3">
          <div class="flex size-10 items-center justify-center rounded-xl bg-primary text-inverted shadow-sm">
            <UIcon name="i-lucide-waves" class="size-5" />
          </div>
          <div v-if="!collapsed" class="min-w-0">
            <p class="truncate text-sm font-semibold text-highlighted">
              海洋样本巡检系统
            </p>
            <p class="truncate text-xs text-muted">
              Ocean Operations Workspace
            </p>
          </div>
        </div>
      </template>

      <template #default="{ collapsed }">
        <UDashboardSearchButton :collapsed="collapsed" class="bg-transparent ring-default" />

        <UNavigationMenu
          :collapsed="collapsed"
          :items="primaryLinks"
          orientation="vertical"
          tooltip
          popover
        />

        <UNavigationMenu
          :collapsed="collapsed"
          :items="supportLinks"
          orientation="vertical"
          tooltip
          class="mt-auto"
        />
      </template>

      <template #footer="{ collapsed }">
        <div class="flex items-center gap-3">
          <div class="flex size-9 items-center justify-center rounded-lg bg-primary/10 text-primary">
            <UIcon name="i-lucide-ship-wheel" class="size-5" />
          </div>
          <div v-if="!collapsed" class="min-w-0">
            <p class="text-sm font-medium text-highlighted">
              当前阶段
            </p>
            <p class="truncate text-xs text-muted">
              MVP 核心工作流已接入
            </p>
          </div>
        </div>
      </template>
    </UDashboardSidebar>

    <UDashboardSearch :groups="commandGroups" />

    <slot />
  </UDashboardGroup>
</template>
