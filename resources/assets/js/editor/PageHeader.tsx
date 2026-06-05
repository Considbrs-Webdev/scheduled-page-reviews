import { useMemo } from "react";
import { CheckCircle2, ExternalLink } from "lucide-react";
import { toast } from "sonner";

import { __, sprintf } from "@wordpress/i18n";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { useMarkReviewed } from "@/api/queries";
import { formatDate, formatRelative } from "@/lib/format";
import type { Bucket } from "@/types";

interface PageHeaderProps {
  pageId: number;
  title: string;
  editLink: string | null;
  bucket: Bucket;
  nextReviewAt: string;
  lastReviewedAt: string | null;
}

export function PageHeader({
  pageId, title, editLink, bucket, nextReviewAt, lastReviewedAt,
}: PageHeaderProps) {
  const m = useMarkReviewed(pageId);
  const pill = useMemo(() => {
    if (bucket === "overdue") {
      return { label: __("Review overdue", "scheduled-page-reviews"), className: "bg-red-600 text-white hover:bg-red-600" };
    }
    if (bucket === "upcoming") {
      return { label: __("Review due soon", "scheduled-page-reviews"), className: "bg-amber-500 text-white hover:bg-amber-500" };
    }
    return { label: __("On track", "scheduled-page-reviews"), className: "bg-emerald-600 text-white hover:bg-emerald-600" };
  }, [bucket]);
  return (
    <div className="flex items-start justify-between gap-4 border-b p-4">
      <div className="min-w-0">
        <div className="text-xs uppercase tracking-wide text-muted-foreground">
          {sprintf(/* translators: %d: page ID */ __("Page #%d", "scheduled-page-reviews"), pageId)}
        </div>
        <h2 className="mt-1 truncate text-lg font-semibold tracking-tight">
          {title || sprintf(/* translators: %d: page ID */ __("Untitled page #%d", "scheduled-page-reviews"), pageId)}
          {editLink && (
            <a
              href={editLink}
              target="_blank"
              rel="noreferrer"
              className="ml-2 inline-flex items-center text-xs font-normal text-muted-foreground hover:text-foreground"
              title={__("Open in editor", "scheduled-page-reviews")}
            >
              <ExternalLink className="h-3 w-3" />
            </a>
          )}
        </h2>
        <div className="mt-2 flex flex-wrap items-center gap-3">
          <Badge className={pill.className}>{pill.label}</Badge>
          <span className="text-xs text-muted-foreground">
            {sprintf(
              /* translators: 1: relative date, 2: absolute date */
              __("Next review %1$s (%2$s)", "scheduled-page-reviews"),
              formatRelative(nextReviewAt),
              formatDate(nextReviewAt),
            )}
          </span>
          <span className="text-xs text-muted-foreground">
            {lastReviewedAt
              ? sprintf(
                  /* translators: %s: relative date */
                  __("Last reviewed %s", "scheduled-page-reviews"),
                  formatRelative(lastReviewedAt),
                )
              : __("Never reviewed", "scheduled-page-reviews")}
          </span>
        </div>
      </div>
      <Button
        type="button"
        variant="secondary"
        size="sm"
        disabled={m.isPending}
        onClick={() => m.mutate(undefined, {
          onSuccess: () => toast.success(__("Marked as reviewed.", "scheduled-page-reviews")),
          onError: (e) => toast.error(e instanceof Error ? e.message : __("Failed.", "scheduled-page-reviews")),
        })}
      >
        <CheckCircle2 className="mr-2 h-4 w-4" />
        {m.isPending ? __("Saving…", "scheduled-page-reviews") : __("Mark reviewed", "scheduled-page-reviews")}
      </Button>
    </div>
  );
}
