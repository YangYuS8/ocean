<template>
  <UDashboardPanel id="samples">
    <template #header>
      <UDashboardNavbar title="样本管理">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
      </UDashboardNavbar>
    </template>

    <template #body>
      <DashboardModulePlaceholder
        title="样本管理工作区"
        summary="用于承接样本登记、批次跟踪、检测结果汇总与图像资产关联。"
        :bullets="[
          '预留样本列表、批次状态与质量复核入口。',
          '后续将与 MariaDB 中的样本元数据和检测结果对接。',
          '支持继续扩展图像处理状态与异常复核动作。'
        ]"
      />
    </template>
  </UDashboardPanel>
</template>
