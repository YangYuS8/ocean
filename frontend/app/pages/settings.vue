<template>
  <UDashboardPanel id="settings">
    <template #header>
      <UDashboardNavbar title="系统设置">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
      </UDashboardNavbar>
    </template>

    <template #body>
      <DashboardModulePlaceholder
        title="系统设置工作区"
        summary="用于承接账号信息、系统参数、部署说明与后续权限配置。"
        :bullets="[
          '当前记录模板来源、运行方式和后续扩展方向。',
          '后续可接入账号管理、通知偏好和安全策略。',
          '保持与 Nginx、Nuxt、PHP 和 Redis 的现有运行模型兼容。'
        ]"
      />
    </template>
  </UDashboardPanel>
</template>
