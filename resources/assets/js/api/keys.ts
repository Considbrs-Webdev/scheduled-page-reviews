export const queryKeys = {
  settings: () => ["settings"] as const,
  tree: (parentId: number, recursive: boolean) =>
    ["tree", parentId, recursive] as const,
  pageRule: (pageId: number) => ["pageRule", pageId] as const,
  dashboard: (bucket: string) => ["dashboard", bucket] as const,
  roles: () => ["roles"] as const,
  users: (search: string, role: string) => ["users", search, role] as const,
  usersByIds: (ids: number[]) => ["users", "by-ids", ...ids] as const,
};
