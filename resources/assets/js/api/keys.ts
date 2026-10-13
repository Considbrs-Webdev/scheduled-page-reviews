export const queryKeys = {
  settings: () => ["settings"] as const,
  scheduleInfo: () => ["scheduleInfo"] as const,
  tree: (parentId: number, recursive: boolean) =>
    ["tree", parentId, recursive] as const,
  pageRule: (pageId: number) => ["pageRule", pageId] as const,
  dashboard: (bucket: string) => ["dashboard", bucket] as const,
  groups: () => ["groups"] as const,
  users: (search: string, group: string) => ["users", search, group] as const,
  usersByIds: (ids: number[]) => ["users", "by-ids", ...ids] as const,
};
