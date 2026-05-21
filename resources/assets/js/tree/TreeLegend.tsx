import { CornerDownRight, Tag } from "lucide-react";

export function TreeLegend() {
  return (
    <div className="border-t bg-muted/30 px-3 py-2 text-[11px] text-muted-foreground">
      <div className="flex items-center gap-2">
        <Tag className="h-3 w-3" />
        <span>Local rule</span>
        <span className="opacity-50">·</span>
        <CornerDownRight className="h-3 w-3 text-primary" />
        <span>Propagates to subpages</span>
      </div>
    </div>
  );
}
