import { CornerDownRight, CornerLeftDown, Tag } from "lucide-react";

export function TreeLegend() {
  return (
    <div className="border-t bg-muted/30 px-3 py-2 text-[11px] text-muted-foreground">
      <div className="flex flex-wrap items-center gap-x-2 gap-y-1">
        <span className="inline-flex items-center gap-1">
          <Tag className="h-3 w-3" />
          <span>Local rule</span>
        </span>
        <span className="opacity-50">·</span>
        <span className="inline-flex items-center gap-1">
          <CornerDownRight className="h-3 w-3 text-primary" />
          <span>Propagates to subpages</span>
        </span>
        <span className="opacity-50">·</span>
        <span className="inline-flex items-center gap-1">
          <CornerLeftDown className="h-3 w-3" />
          <span>Inherited from ancestor</span>
        </span>
      </div>
    </div>
  );
}
