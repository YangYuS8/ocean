<template>
  <UDashboardPanel id="equipment">
    <template #header>
      <UDashboardNavbar title="仪器监控">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
      </UDashboardNavbar>
    </template>

    <template #body>
      <DashboardModulePlaceholder
        title="仪器监控工作区"
        summary="用于查看设备运行状态、校准提醒、环境采集异常与监测链路健康度。"
        :bullets="[
          '预留关键设备卡片、状态指示和异常分级展示。',
          '后续可接入 Redis 队列状态与 Python 分析任务反馈。',
          '支持继续扩展到实时告警与历史趋势查询。'
        ]"
      />
    </template>
  </UDashboardPanel>
</template>
