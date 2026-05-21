import { ChevronDown, ChevronRight, CornerDownRight, Tag } from "lucide-react";
import type { NodeRendererProps } from "react-arborist";

import { Tooltip, TooltipContent, TooltipTrigger } from "@/components/ui/tooltip";
import { cn } from "@/lib/utils";

import type { ArboristNode } from "./buildTree";

export function TreeNode({
  node,
  style,
  dragHandle,
}: NodeRendererProps<ArboristNode>) {
  const hasChildren = node.data.has_children;
  return (
    <div
      ref={dragHandle}
      style={style}
      onClick={() => node.select()}
      onDoubleClick={() => node.toggle()}
      className={cn(
        "group flex h-[30px] cursor-pointer items-center gap-1 rounded px-1 text-sm",
        node.isSelected
          ? "bg-accent text-accent-foreground"
          : "hover:bg-muted/60",
      )}
    >
      <button
        type="button"
        className="flex h-5 w-5 items-center justify-center text-muted-foreground"
        onClick={(e) => {
          e.stopPropagation();
          node.toggle();
        }}
        aria-label={node.isOpen ? "Collapse" : "Expand"}
        disabled={!hasChildren}
      >
        {hasChildren ? (
          node.isOpen ? (
            <ChevronDown className="h-4 w-4" />
          ) : (
            <ChevronRight className="h-4 w-4" />
          )
        ) : null}
      </button>
      <span className="flex-1 truncate">{node.data.name}</span>
      {node.data.has_local_rule && (
        <Tooltip>
          <TooltipTrigger asChild>
            <span className="inline-flex shrink-0" aria-label="Has local rule">
              <Tag className="h-3.5 w-3.5 text-muted-foreground" />
            </span>
          </TooltipTrigger>
          <TooltipContent>Local rule set on this page</TooltipContent>
        </Tooltip>
      )}
      {node.data.has_subtree_rule && (
        <Tooltip>
          <TooltipTrigger asChild>
            <span
              className="inline-flex shrink-0"
              aria-label="Propagates to subpages"
            >
              <CornerDownRight className="h-3.5 w-3.5 text-primary" />
            </span>
          </TooltipTrigger>
          <TooltipContent>Propagates to descendant pages</TooltipContent>
        </Tooltip>
      )}
    </div>
  );
}
