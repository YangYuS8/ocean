<script setup lang="ts">
type InspectionTaskDetail = {
  id: number
  task_code: string
  title: string
  description: string | null
  task_type: string
  priority: string
  status: string
  location_text: string | null
  planned_at: string | null
  due_at: string | null
  assigned_to: null | {
    id: number
    display_name: string
  }
  created_by: null | {
    id: number
    display_name: string
  }
  started_at: string | null
  submitted_at: string | null
  created_at: string | null
  updated_at: string | null
}

type DetailResponse<T> = {
  data: T
}

const route = useRoute()
const { baseURL, request, getErrorMessage } = useApiClient()

const taskId = computed(() => String(route.params.id))

const { data, pending, error, refresh } = await useFetch<DetailResponse<InspectionTaskDetail>>(() => `/api/inspection-tasks/${taskId.value}`, {
  baseURL,
  watch: [taskId]
})

const task = computed(() => data.value?.data ?? null)
const actionPending = ref(false)
const actionError = ref('')
const actionSuccess = ref('')

const operatorId = computed(() => task.value?.assigned_to?.id ?? task.value?.created_by?.id ?? null)

const statusTone = (value: string) => {
  switch (value) {
    case 'assigned':
      return 'warning'
    case 'in_progress':
      return 'primary'
    case 'submitted':
      return 'success'
    default:
      return 'neutral'
  }
}

const formatDateTime = (value: string | null) => {
  if (!value) {
    return '未设置'
  }

  return new Intl.DateTimeFormat('zh-CN', {
    dateStyle: 'medium',
    timeStyle: 'short'
  }).format(new Date(value))
}

const runAction = async (action: 'start' | 'submit') => {
  if (!operatorId.value) {
    actionError.value = '当前任务缺少可用操作人 ID，无法推进状态。'
    actionSuccess.value = ''
    return
  }

  actionPending.value = true
  actionError.value = ''
  actionSuccess.value = ''

  try {
    await request(`/api/inspection-tasks/${taskId.value}/${action}`, {
      method: 'POST',
      body: {
        operator_id: operatorId.value
      }
    })

    actionSuccess.value = action === 'start' ? '任务已开始，状态已刷新。' : '任务已提交，状态已刷新。'
    await refresh()
  }
  catch (requestError) {
    actionError.value = getErrorMessage(requestError, '任务状态推进失败，请稍后重试。')
  }
  finally {
    actionPending.value = false
  }
}
</script>

<template>
  <UDashboardPanel id="inspection-detail">
    <template #header>
      <UDashboardNavbar :title="task?.title || '巡检任务详情'">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>

        <template #right>
          <UButton to="/inspections" color="neutral" variant="outline" icon="i-lucide-arrow-left">
            返回列表
          </UButton>
        </template>
      </UDashboardNavbar>
    </template>

    <template #body>
      <UAlert
        v-if="error"
        color="error"
        variant="soft"
        title="任务详情加载失败"
        :description="'请检查任务是否存在，或稍后重试。'"
      />

      <UCard v-else-if="pending || !task">
        <div class="space-y-3">
          <div class="h-6 w-48 rounded bg-muted/60" />
          <div class="h-24 rounded-2xl bg-muted/40" />
          <div class="h-24 rounded-2xl bg-muted/40" />
        </div>
      </UCard>

      <div v-else class="grid gap-4 xl:grid-cols-[1.25fr_0.75fr]">
        <UCard>
          <template #header>
            <div class="flex flex-wrap items-center gap-2">
              <UBadge :color="statusTone(task.status)" variant="soft">
                {{ task.status }}
              </UBadge>
              <span class="text-xs uppercase tracking-[0.2em] text-muted">
                {{ task.task_code }}
              </span>
            </div>
          </template>

          <div class="space-y-6">
            <div>
              <h2 class="text-2xl font-semibold text-highlighted">
                {{ task.title }}
              </h2>
              <p class="mt-2 text-sm leading-6 text-toned">
                {{ task.description || '当前任务暂无补充描述。' }}
              </p>
            </div>

            <dl class="grid gap-4 text-sm text-toned sm:grid-cols-2">
              <div>
                <dt class="text-xs uppercase tracking-[0.18em] text-muted">任务类型</dt>
                <dd class="mt-1 text-default">{{ task.task_type }}</dd>
              </div>
              <div>
                <dt class="text-xs uppercase tracking-[0.18em] text-muted">优先级</dt>
                <dd class="mt-1 text-default">{{ task.priority }}</dd>
              </div>
              <div>
                <dt class="text-xs uppercase tracking-[0.18em] text-muted">巡检点位</dt>
                <dd class="mt-1 text-default">{{ task.location_text || '未设置' }}</dd>
              </div>
              <div>
                <dt class="text-xs uppercase tracking-[0.18em] text-muted">指派人</dt>
                <dd class="mt-1 text-default">{{ task.assigned_to?.display_name || '未指派' }}</dd>
              </div>
              <div>
                <dt class="text-xs uppercase tracking-[0.18em] text-muted">计划时间</dt>
                <dd class="mt-1 text-default">{{ formatDateTime(task.planned_at) }}</dd>
              </div>
              <div>
                <dt class="text-xs uppercase tracking-[0.18em] text-muted">截止时间</dt>
                <dd class="mt-1 text-default">{{ formatDateTime(task.due_at) }}</dd>
              </div>
              <div>
                <dt class="text-xs uppercase tracking-[0.18em] text-muted">开始时间</dt>
                <dd class="mt-1 text-default">{{ formatDateTime(task.started_at) }}</dd>
              </div>
              <div>
                <dt class="text-xs uppercase tracking-[0.18em] text-muted">提交时间</dt>
                <dd class="mt-1 text-default">{{ formatDateTime(task.submitted_at) }}</dd>
              </div>
            </dl>
          </div>
        </UCard>

        <div class="space-y-4">
          <UCard>
            <template #header>
              <div>
                <h2 class="text-lg font-semibold text-highlighted">
                  状态推进
                </h2>
                <p class="mt-1 text-sm text-toned">
                  当前仍使用显式传参身份字段，默认取指派人或创建人作为操作人。
                </p>
              </div>
            </template>

            <div class="space-y-4">
              <div class="rounded-2xl bg-primary/5 px-4 py-3 text-sm text-toned">
                <p>当前状态：<span class="font-semibold text-highlighted">{{ task.status }}</span></p>
                <p class="mt-1">
                  操作人 ID：<span class="font-semibold text-highlighted">{{ operatorId ?? '不可用' }}</span>
                </p>
              </div>

              <UAlert
                v-if="actionError"
                color="error"
                variant="soft"
                title="操作失败"
                :description="actionError"
              />

              <UAlert
                v-if="actionSuccess"
                color="success"
                variant="soft"
                title="操作成功"
                :description="actionSuccess"
              />

              <div class="flex flex-col gap-3">
                <UButton
                  v-if="task.status === 'assigned'"
                  :loading="actionPending"
                  @click="runAction('start')"
                >
                  开始任务
                </UButton>

                <UButton
                  v-if="task.status === 'in_progress'"
                  :loading="actionPending"
                  @click="runAction('submit')"
                >
                  提交任务
                </UButton>

                <UButton to="/samples" color="neutral" variant="outline" icon="i-lucide-flask-conical">
                  进入样本管理
                </UButton>
              </div>
            </div>
          </UCard>
        </div>
      </div>
    </template>
  </UDashboardPanel>
</template>
