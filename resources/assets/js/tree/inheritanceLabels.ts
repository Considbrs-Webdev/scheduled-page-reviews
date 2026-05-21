import { __, sprintf } from "@wordpress/i18n";
import type { InheritanceSummary } from "@/types";

function fieldLabel(field: string): string {
  const labels: Record<string, string> = {
    interval_days: __("Review interval", "content-ownership"),
    recipients: __("Who to notify", "content-ownership"),
    notify_before: __("Notify before due", "content-ownership"),
  };
  return labels[field] ?? field;
}

function formatFieldList(fields: string[]): string {
  return fields.map((field) => fieldLabel(field)).join(", ");
}

export function localRuleTooltip(summary: InheritanceSummary): string {
  if (summary.local_fields.length > 0) {
    return sprintf(
      /* translators: %s: comma-separated field labels */
      __("Local override on this page: %s", "content-ownership"),
      formatFieldList(summary.local_fields),
    );
  }
  if (summary.propagated_fields.length > 0) {
    return sprintf(
      /* translators: %s: comma-separated field labels */
      __("Rule set on this page (applies to subpages): %s", "content-ownership"),
      formatFieldList(summary.propagated_fields),
    );
  }
  return __("Local rule set on this page", "content-ownership");
}

export function propagatedRuleTooltip(summary: InheritanceSummary): string {
  if (summary.propagated_fields.length === 0) {
    return __("Propagates to descendant pages", "content-ownership");
  }
  return sprintf(
    /* translators: %s: comma-separated field labels */
    __("Applies to descendant pages: %s", "content-ownership"),
    formatFieldList(summary.propagated_fields),
  );
}

export function inheritedRuleTooltip(summary: InheritanceSummary): string {
  if (summary.inherited_fields.length === 0) {
    return __("Inherits settings from an ancestor page", "content-ownership");
  }

  const fields = formatFieldList(summary.inherited_fields);
  if (summary.inherited_from.length === 1) {
    return sprintf(
      /* translators: 1: field labels, 2: page ID */
      __("Inherits %1$s from page #%2$d", "content-ownership"),
      fields,
      summary.inherited_from[0],
    );
  }
  if (summary.inherited_from.length > 1) {
    const pages = summary.inherited_from.map((id) => `#${id}`).join(", ");
    return sprintf(
      /* translators: 1: field labels, 2: comma-separated page IDs */
      __("Inherits %1$s from pages %2$s", "content-ownership"),
      fields,
      pages,
    );
  }

  return sprintf(
    /* translators: %s: comma-separated field labels */
    __("Inherits %s from an ancestor page", "content-ownership"),
    fields,
  );
}
