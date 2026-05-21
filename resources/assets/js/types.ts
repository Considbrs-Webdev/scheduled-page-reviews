// ----- Targets ------------------------------------------------------------
export type TargetType = "user" | "role" | "email";

export interface UserTarget {
  type: "user";
  value: number;
}
export interface RoleTarget {
  type: "role";
  value: string;
}
export interface EmailTarget {
  type: "email";
  value: string;
}

export type Target = UserTarget | RoleTarget | EmailTarget;
export type RecipientTarget = Target;

export function targetKey(t: Target): string {
  return `${t.type}:${t.value}`;
}

// ----- Rule ---------------------------------------------------------------
export type RuleScope = "self" | "subtree";

export interface ScopedValue<T> {
  value: T;
  scope: RuleScope;
}

/** Sparse — any field absent means "inherit / fall back". */
export interface Rule {
  interval_days?: ScopedValue<number>;
  recipients?: ScopedValue<RecipientTarget[]>;
  notify_before?: ScopedValue<number>;
}

// ----- Effective settings ------------------------------------------------
export type ResolutionSource =
  | "default"
  | "inherited"
  | "local"
  | "local-propagated";

export interface Resolution<T> {
  value: T;
  source: ResolutionSource;
  /** Page ID this value came from; null for `default`. */
  from: number | null;
}

export interface EffectiveSettings {
  interval_days: Resolution<number>;
  recipients: Resolution<RecipientTarget[]>;
  notify_before: Resolution<number>;
}

// ----- Buckets -----------------------------------------------------------
export type Bucket = "none" | "upcoming" | "overdue";

// ----- Page rule API ------------------------------------------------------
export interface PageRuleResponse {
  page_id: number;
  title: string;
  edit_link: string | null;
  rule: Rule;
  effective: EffectiveSettings;
  last_reviewed_at: string | null;
  last_reviewed_by: number | null;
  next_review_at: string;
  bucket: Bucket;
}

// ----- Tree --------------------------------------------------------------
export interface TreeNode {
  id: number;
  title: string;
  parent: number;
  depth: number;
  has_children: boolean;
  has_local_rule: boolean;
  has_subtree_rule: boolean;
}

// ----- Dashboard ---------------------------------------------------------
export interface DashboardItem {
  id: number;
  title: string;
  edit_link: string | null;
  bucket: Bucket;
  next_review_at: string;
  last_reviewed_at: string | null;
  last_reviewed_by: number | null;
}

// ----- Global settings ---------------------------------------------------
export interface GlobalSettings {
  default_interval_days: number;
  notify_days_before: number;
  send_reminder_after_due: boolean;
  reminder_cadence_days: number;
  default_recipients: RecipientTarget[];
  cron_batch_size: number;
  sync_wp_modified_on_review: boolean;
}

/** Partial shape for PUT /settings; PHP merges over the current settings. */
export type GlobalSettingsUpdate = Partial<GlobalSettings>;

// ----- Roles + users -----------------------------------------------------
export interface RoleListItem {
  slug: string;
  name: string;
  count: number;
}

export interface UserListItem {
  id: number;
  display_name: string;
  user_email: string;
  roles: string[];
}

// ----- Mark reviewed -----------------------------------------------------
export interface MarkReviewedResponse {
  page_id: number;
  last_reviewed_at: string;
  last_reviewed_by: number;
  reviewer_display_name: string;
}

export function isUserTarget(t: Target): t is UserTarget {
  return t.type === "user";
}
export function isRoleTarget(t: Target): t is RoleTarget {
  return t.type === "role";
}
export function isEmailTarget(t: Target): t is EmailTarget {
  return t.type === "email";
}
