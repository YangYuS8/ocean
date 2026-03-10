<template>
  <UDashboardPanel id="inspections">
    <template #header>
      <UDashboardNavbar title="巡检任务">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
      </UDashboardNavbar>
    </template>

    <template #body>
      <DashboardModulePlaceholder
        title="巡检任务工作区"
        summary="用于组织现场巡检流程、任务派发、点位签到与异常记录。"
        :bullets="[
          '优先适配平板和手机端的巡检视图。',
          '为弱网场景预留简化信息展示和任务回填能力。',
          '后续可接入巡检路径、检查清单和现场照片。'
        ]"
      />
    </template>
  </UDashboardPanel>
</template>
