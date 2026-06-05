import { wp } from "./wp";
import { getEditorBoot } from "./api-helpers";

export type { EditorBoot } from "./api-helpers";
export { getEditorBoot } from "./api-helpers";

export interface RuleResponse {
  page_id: number;
  effective: {
    interval_days: { value: number; source: string; from: number | null };
    recipients: { value: Array<{ type: string; value: string | number }>; source: string; from: number | null };
    notify_before: { value: number; source: string; from: number | null };
  };
  rule: Record<string, unknown>;
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

function restPath(suffix: string): string {
  const root = getEditorBoot().restRoot.replace(/\/$/, "");
  const pathname = new URL(root, window.location.origin).pathname;
  const wpJson = "/wp-json";
  const base =
    pathname.includes(wpJson)
      ? pathname.slice(pathname.indexOf(wpJson) + wpJson.length)
      : pathname;
  const normalizedBase = base.replace(/\/$/, "");
  const path = suffix.startsWith("/") ? suffix : `/${suffix}`;
  return `${normalizedBase}${path}`;
}

export async function fetchRule(pageId: number): Promise<RuleResponse> {
  return wp.apiFetch<RuleResponse>({ path: restPath(`/pages/${pageId}/rule`) });
}

export async function markReviewed(pageId: number): Promise<MarkReviewedResponse> {
  return wp.apiFetch<MarkReviewedResponse>({
    path: restPath(`/pages/${pageId}/mark-reviewed`),
    method: "POST",
  });
}
