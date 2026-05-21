import { __ } from "@wordpress/i18n";
import { getBoot } from "./boot";

export function formatDate(iso: string | null | undefined): string {
  if (!iso) return "";
  const d = new Date(iso);
  if (Number.isNaN(d.getTime())) return iso ?? "";
  return d.toLocaleDateString(getBoot().locale);
}

export function formatRelative(
  iso: string | null | undefined,
  now = new Date(),
): string {
  if (!iso) return "";
  const d = new Date(iso);
  if (Number.isNaN(d.getTime())) return iso ?? "";
  const diffMs = d.getTime() - now.getTime();
  const absDays = Math.round(Math.abs(diffMs) / 86_400_000);
  if (absDays === 0) return __("today", "content-ownership");
  const rtf = new Intl.RelativeTimeFormat(getBoot().locale, { numeric: "auto" });
  return rtf.format(Math.round(diffMs / 86_400_000), "day");
}
