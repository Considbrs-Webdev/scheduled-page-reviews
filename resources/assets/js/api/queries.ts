import {
  useMutation,
  useQuery,
  useQueryClient,
  type UseMutationOptions,
} from "@tanstack/react-query";

import { apiRequest } from "./client";
import { queryKeys } from "./keys";
import type {
  DashboardItem,
  GlobalSettings,
  GlobalSettingsUpdate,
  MarkReviewedResponse,
  PageRuleResponse,
  Rule,
  RoleListItem,
  TreeNode,
  UserListItem,
} from "@/types";

export function useGlobalSettings() {
  return useQuery({
    queryKey: queryKeys.settings(),
    queryFn: ({ signal }) =>
      apiRequest<GlobalSettings>("settings", { signal }),
  });
}

export function useUpdateGlobalSettings(
  options?: UseMutationOptions<GlobalSettings, Error, GlobalSettingsUpdate>,
) {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (values) =>
      apiRequest<GlobalSettings>("settings", { method: "POST", body: values }),
    onSuccess: (data) => {
      qc.setQueryData(queryKeys.settings(), data);
    },
    ...options,
  });
}

export function useTree(parentId = 0, recursive = true) {
  return useQuery({
    queryKey: queryKeys.tree(parentId, recursive),
    queryFn: ({ signal }) =>
      apiRequest<TreeNode[]>("tree", {
        query: { parent: parentId, recursive },
        signal,
      }),
  });
}

export function usePageRule(pageId: number | null) {
  return useQuery({
    enabled: pageId != null && pageId > 0,
    queryKey: queryKeys.pageRule(pageId ?? 0),
    queryFn: ({ signal }) =>
      apiRequest<PageRuleResponse>(`pages/${pageId}/rule`, { signal }),
  });
}

export function useUpdatePageRule(pageId: number) {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (rule: Rule) =>
      apiRequest<PageRuleResponse>(`pages/${pageId}/rule`, {
        method: "PUT",
        body: rule,
      }),
    onSuccess: (data) => {
      qc.setQueryData(queryKeys.pageRule(pageId), data);
      qc.invalidateQueries({ queryKey: ["tree"] });
    },
  });
}

export function useMarkReviewed(pageId: number) {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: () =>
      apiRequest<MarkReviewedResponse>(`pages/${pageId}/mark-reviewed`, {
        method: "POST",
      }),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: queryKeys.pageRule(pageId) });
      qc.invalidateQueries({ queryKey: ["dashboard"] });
    },
  });
}

export function useDashboard(
  bucket: "all" | "upcoming" | "overdue" = "all",
) {
  return useQuery({
    queryKey: queryKeys.dashboard(bucket),
    queryFn: ({ signal }) =>
      apiRequest<DashboardItem[]>("dashboard", { query: { bucket }, signal }),
  });
}

export function useRoles() {
  return useQuery({
    queryKey: queryKeys.roles(),
    queryFn: ({ signal }) => apiRequest<RoleListItem[]>("roles", { signal }),
    staleTime: 60_000,
  });
}

export function useUserSearch(search: string, role = "") {
  return useQuery({
    queryKey: queryKeys.users(search, role),
    queryFn: ({ signal }) =>
      apiRequest<UserListItem[]>("users", { query: { search, role }, signal }),
    staleTime: 30_000,
  });
}

export function useUsersByIds(ids: number[]) {
  return useQuery({
    enabled: ids.length > 0,
    queryKey: queryKeys.usersByIds(ids),
    queryFn: ({ signal }) =>
      apiRequest<UserListItem[]>("users", {
        query: { include: ids, per_page: ids.length },
        signal,
      }),
    staleTime: 60_000,
  });
}

export function useRunCronNow() {
  return useMutation({
    mutationFn: () => apiRequest<unknown>("cron/run-now", { method: "POST" }),
  });
}
