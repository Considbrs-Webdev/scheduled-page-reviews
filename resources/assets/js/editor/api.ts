import { wp } from "./wp";

export interface EditorBoot {
  restRoot: string;
  nonce: string;
  settingsUrl: string;
  pluginVersion: string;
  locale: string;
  dateFormat: string;
}

declare global {
  interface Window {
    contentOwnershipEditorBoot?: EditorBoot;
  }
}

export function getEditorBoot(): EditorBoot {
  const b = window.contentOwnershipEditorBoot;
  if (!b) {
    throw new Error("contentOwnershipEditorBoot is not defined; was the editor bundle enqueued before WP localised its data?");
  }
  return b;
}

export interface RuleResponse {
  page_id: number;
  effective: {
    interval_days: number;
    owners: number[];
    recipients: string[];
    notify_before: number;
  };
  rule: {
    interval_days: { value: number | null; scope: "local" | "subtree" | null };
    owners: { value: number[]; scope: "local" | "subtree" | null };
    recipients: { value: string[]; scope: "local" | "subtree" | null };
    notify_before: { value: number | null; scope: "local" | "subtree" | null };
  };
  last_reviewed_at: string | null;
  last_reviewed_by: number | null;
  next_review_at: string;
  bucket: "none" | "upcoming" | "overdue";
}

export interface MarkReviewedResponse {
  page_id: number;
  last_reviewed_at: string;
  last_reviewed_by: number;
  reviewer_display_name: string;
}

export async function fetchRule(pageId: number): Promise<RuleResponse> {
  return wp.apiFetch<RuleResponse>({ path: `/content-ownership/v1/pages/${pageId}/rule` });
}

export async function markReviewed(pageId: number): Promise<MarkReviewedResponse> {
  return wp.apiFetch<MarkReviewedResponse>({
    path: `/content-ownership/v1/pages/${pageId}/mark-reviewed`,
    method: "POST",
  });
}
