import type { InheritanceSummary } from "@/types";

const FIELD_LABELS: Record<string, string> = {
  interval_days: "Review interval",
  recipients: "Who to notify",
  notify_before: "Notify before due",
};

function formatFieldList(fields: string[]): string {
  return fields
    .map((field) => FIELD_LABELS[field] ?? field)
    .join(", ");
}

export function localRuleTooltip(summary: InheritanceSummary): string {
  if (summary.local_fields.length > 0) {
    return `Local override on this page: ${formatFieldList(summary.local_fields)}`;
  }
  if (summary.propagated_fields.length > 0) {
    return `Rule set on this page (applies to subpages): ${formatFieldList(summary.propagated_fields)}`;
  }
  return "Local rule set on this page";
}

export function propagatedRuleTooltip(summary: InheritanceSummary): string {
  if (summary.propagated_fields.length === 0) {
    return "Propagates to descendant pages";
  }
  return `Applies to descendant pages: ${formatFieldList(summary.propagated_fields)}`;
}

export function inheritedRuleTooltip(summary: InheritanceSummary): string {
  if (summary.inherited_fields.length === 0) {
    return "Inherits settings from an ancestor page";
  }

  const fields = formatFieldList(summary.inherited_fields);
  if (summary.inherited_from.length === 1) {
    return `Inherits ${fields} from page #${summary.inherited_from[0]}`;
  }
  if (summary.inherited_from.length > 1) {
    const pages = summary.inherited_from.map((id) => `#${id}`).join(", ");
    return `Inherits ${fields} from pages ${pages}`;
  }

  return `Inherits ${fields} from an ancestor page`;
}
