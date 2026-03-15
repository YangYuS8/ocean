## 1. API 与字段文档校准

- [x] 1.1 更新 `docs/2.4-MVP-API-范围冻结稿.md` 中的 Analysis Jobs 章节，使其反映当前已落地的最小状态推进动作与失败后重新发起边界。
- [x] 1.2 更新 `docs/2.6-P0-API-字段草案.md` 中的 Analysis Jobs 字段与响应示例，使其覆盖 `started_at`、`finished_at`、`result_summary`、`error_message` 和 retry 语义。

## 2. 状态与实现现状文档校准

- [x] 2.1 更新 `docs/2.7-P0-状态流转草案.md` 中 analysis_jobs 的当前阶段实现建议，使其与已实现的最小生命周期动作保持一致。
- [x] 2.2 更新 `docs/2.9-P0-实现与验证说明.md` 中分析任务、验证路径和当前实现形态的描述，使其反映 Laravel 迁移后的真实基线。

## 3. 一致性复核

- [x] 3.1 通读上述文档中与 analysis jobs 相关的章节，确认它们与当前 OpenSpec 主规范不存在冲突表述。
- [x] 3.2 复核文档措辞，确保明确“analysis_jobs 是过程资源，不是最终业务结果”以及“失败后重新发起会新建任务记录”。
