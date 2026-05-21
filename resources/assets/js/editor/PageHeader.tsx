import { useMemo } from "react";
import { CheckCircle2, ExternalLink } from "lucide-react";
import { toast } from "sonner";

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
    if (bucket === "overdue") return { label: "Review overdue", className: "bg-red-600 text-white hover:bg-red-600" };
    if (bucket === "upcoming") return { label: "Review due soon", className: "bg-amber-500 text-white hover:bg-amber-500" };
    return { label: "On track", className: "bg-emerald-600 text-white hover:bg-emerald-600" };
  }, [bucket]);
  return (
    <div className="flex items-start justify-between gap-4 border-b p-4">
      <div className="min-w-0">
        <div className="text-xs uppercase tracking-wide text-muted-foreground">Page #{pageId}</div>
        <h2 className="mt-1 truncate text-lg font-semibold tracking-tight">
          {title || `Untitled page #${pageId}`}
          {editLink && (
            <a
              href={editLink}
              target="_blank"
              rel="noreferrer"
              className="ml-2 inline-flex items-center text-xs font-normal text-muted-foreground hover:text-foreground"
              title="Open in editor"
            >
              <ExternalLink className="h-3 w-3" />
            </a>
          )}
        </h2>
        <div className="mt-2 flex flex-wrap items-center gap-3">
          <Badge className={pill.className}>{pill.label}</Badge>
          <span className="text-xs text-muted-foreground">
            Next review {formatRelative(nextReviewAt)} ({formatDate(nextReviewAt)})
          </span>
          <span className="text-xs text-muted-foreground">
            {lastReviewedAt ? `Last reviewed ${formatRelative(lastReviewedAt)}` : "Never reviewed"}
          </span>
        </div>
      </div>
      <Button
        type="button"
        variant="secondary"
        size="sm"
        disabled={m.isPending}
        onClick={() => m.mutate(undefined, {
          onSuccess: () => toast.success("Marked as reviewed."),
          onError: (e) => toast.error(e instanceof Error ? e.message : "Failed."),
        })}
      >
        <CheckCircle2 className="mr-2 h-4 w-4" />
        {m.isPending ? "Saving…" : "Mark reviewed"}
      </Button>
    </div>
  );
}
