<template>
  <UDashboardPanel id="reports">
    <template #header>
      <UDashboardNavbar title="统计报表">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
      </UDashboardNavbar>
    </template>

    <template #body>
      <DashboardModulePlaceholder
        title="统计报表工作区"
        summary="用于展示样本处理量、巡检完成率、监测异常趋势和后续分析摘要。"
        :bullets="[
          '当前以占位图表与指标区域验证页面骨架。',
          '后续将接入真实业务指标与可筛选时间范围。',
          '支持继续扩展导出、汇总报表和分析结果回溯。'
        ]"
      />
    </template>
  </UDashboardPanel>
</template>
