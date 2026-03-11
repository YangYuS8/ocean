<script setup lang="ts">
const { commandGroups } = useWorkspace()
const config = useRuntimeConfig()

const { data: summaryData } = await useFetch('/api/dashboard/summary', {
  baseURL: config.public.apiBase || undefined,
  default: () => ({
    data: {
      pending_samples: 128,
      today_inspection_tasks: 14,
      open_exceptions: 3,
      queued_analysis_jobs: 9
    }
  })
})

const metrics = computed(() => {
  const summary = summaryData.value?.data

  return [
  {
    title: '待处理样本',
    value: String(summary?.pending_samples ?? 128),
    description: '等待入库、检测或复核的样本总量',
    icon: 'i-lucide-flask-conical'
  },
  {
    title: '今日巡检任务',
    value: String(summary?.today_inspection_tasks ?? 14),
    description: '包含船载设备、实验设备与环境监测点位',
    icon: 'i-lucide-clipboard-check'
  },
  {
    title: '异常告警',
    value: String(summary?.open_exceptions ?? 3),
    description: '存在需人工确认的高优先级异常项',
    icon: 'i-lucide-bell-ring'
  },
  {
    title: '分析队列',
    value: String(summary?.queued_analysis_jobs ?? 9),
    description: '等待 Python 模块处理的图像与统计任务',
    icon: 'i-lucide-cpu'
  }
]
})

const quickLinks = commandGroups.value[0]?.items ?? []

const activityFeed = [
  '样本批次 A-204 已完成初检并等待图像质量评估。',
  '东海浮标巡检任务将在 14:30 开始，移动端已同步任务清单。',
  '温盐深传感器出现漂移告警，建议优先复核校准记录。'
]
</script>

<template>
  <UDashboardPanel id="overview">
    <template #header>
      <UDashboardNavbar title="总览工作台" :ui="{ right: 'gap-3' }">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>

        <template #right>
          <UBadge color="primary" variant="soft" size="lg">
            Nuxt UI Dashboard Adapted
          </UBadge>
        </template>
      </UDashboardNavbar>
    </template>

    <template #body>
      <section class="rounded-3xl border border-white/70 bg-[radial-gradient(circle_at_top_left,_rgba(13,148,136,0.18),_transparent_45%),linear-gradient(135deg,_#0f172a_0%,_#164e63_48%,_#0f766e_100%)] p-6 text-white shadow-sm md:p-8">
        <div class="max-w-3xl">
          <p class="text-sm uppercase tracking-[0.24em] text-white/70">
            Ocean Operations Workspace
          </p>
          <h1 class="mt-4 text-3xl font-semibold md:text-4xl">
            为海洋样本流转、巡检执行与监测分析提供统一工作台。
          </h1>
          <p class="mt-4 max-w-2xl text-sm leading-6 text-white/80 md:text-base">
            当前首页聚合样本进度、巡检任务、设备告警与分析队列，帮助科研与运维人员在同一入口内组织日常工作。
          </p>
          <div class="mt-6 flex flex-wrap gap-3">
            <UButton to="/samples" icon="i-lucide-arrow-right" trailing color="neutral" variant="solid">
              进入样本管理
            </UButton>
            <UButton to="/inspections" color="neutral" variant="outline">
              查看巡检任务
            </UButton>
          </div>
        </div>
      </section>

      <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <DashboardMetricCard
          v-for="metric in metrics"
          :key="metric.title"
          v-bind="metric"
        />
      </section>

      <section class="grid gap-4 xl:grid-cols-[1.2fr_0.8fr]">
        <UCard>
          <template #header>
            <div class="flex items-start justify-between gap-4">
              <div>
                <h2 class="text-lg font-semibold text-highlighted">
                  核心工作区入口
                </h2>
                <p class="mt-1 text-sm text-toned">
                  后续页面将在这些入口上逐步接入真实业务能力。
                </p>
              </div>
              <UBadge color="neutral" variant="soft">
                快捷访问
              </UBadge>
            </div>
          </template>

          <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
            <UButton
              v-for="link in quickLinks"
              :key="link.to"
              :to="link.to"
              color="neutral"
              variant="outline"
              class="justify-between"
            >
              <span class="flex items-center gap-2">
                <UIcon :name="link.icon" class="size-4" />
                {{ link.label }}
              </span>
              <UIcon name="i-lucide-chevron-right" class="size-4" />
            </UButton>
          </div>
        </UCard>

        <UCard>
          <template #header>
            <div>
              <h2 class="text-lg font-semibold text-highlighted">
                最新工作动态
              </h2>
              <p class="mt-1 text-sm text-toned">
                用于占位展示后续待接入的实时状态与人工处理提示。
              </p>
            </div>
          </template>

          <div class="space-y-4">
            <div v-for="item in activityFeed" :key="item" class="flex gap-3">
              <div class="mt-1 flex size-8 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary">
                <UIcon name="i-lucide-radar" class="size-4" />
              </div>
              <p class="text-sm leading-6 text-toned">
                {{ item }}
              </p>
            </div>
          </div>
        </UCard>
      </section>
    </template>
  </UDashboardPanel>
</template>
