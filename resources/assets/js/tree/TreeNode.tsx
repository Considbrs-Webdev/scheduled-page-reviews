import {
  ChevronDown,
  ChevronRight,
  CornerDownRight,
  CornerLeftDown,
  Tag,
} from "lucide-react";
import type { NodeRendererProps } from "react-arborist";

import { __ } from "@wordpress/i18n";
import { Tooltip, TooltipContent, TooltipTrigger } from "@/components/ui/tooltip";
import { cn } from "@/lib/utils";

import type { ArboristNode } from "./buildTree";
import { IndentGuides, TREE_INDENT_PX } from "./IndentGuides";
import {
  inheritedRuleTooltip,
  localRuleTooltip,
  propagatedRuleTooltip,
} from "./inheritanceLabels";

export function TreeNode({
  node,
  style,
  dragHandle,
}: NodeRendererProps<ArboristNode>) {
  const hasChildren = node.data.has_children;
  const summary = node.data.inheritance_summary;

  return (
    <div
      ref={dragHandle}
      style={style}
      onClick={() => node.select()}
      onDoubleClick={() => node.toggle()}
      className={cn(
        "group relative flex h-[30px] cursor-pointer items-center gap-1 rounded pr-1 text-sm",
        node.isSelected
          ? "bg-accent text-accent-foreground"
          : "hover:bg-muted/60",
      )}
    >
      <IndentGuides node={node} indent={TREE_INDENT_PX} />
      <button
        type="button"
        className="relative z-10 flex h-5 w-5 shrink-0 items-center justify-center text-muted-foreground"
        onClick={(e) => {
          e.stopPropagation();
          node.toggle();
        }}
        aria-label={node.isOpen ? __("Collapse", "scheduled-page-reviews") : __("Expand", "scheduled-page-reviews")}
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
      <span className="relative z-10 flex-1 truncate">{node.data.name}</span>
      {node.data.has_local_rule && (
        <Tooltip>
          <TooltipTrigger asChild>
            <span className="relative z-10 inline-flex shrink-0" aria-label={__("Has local rule", "scheduled-page-reviews")}>
              <Tag className="h-3.5 w-3.5 text-muted-foreground" />
            </span>
          </TooltipTrigger>
          <TooltipContent>{localRuleTooltip(summary)}</TooltipContent>
        </Tooltip>
      )}
      {node.data.has_subtree_rule && (
        <Tooltip>
          <TooltipTrigger asChild>
            <span
              className="relative z-10 inline-flex shrink-0"
              aria-label={__("Propagates to subpages", "scheduled-page-reviews")}
            >
              <CornerDownRight className="h-3.5 w-3.5 text-primary" />
            </span>
          </TooltipTrigger>
          <TooltipContent>{propagatedRuleTooltip(summary)}</TooltipContent>
        </Tooltip>
      )}
      {summary.has_inherited && (
        <Tooltip>
          <TooltipTrigger asChild>
            <span
              className="relative z-10 inline-flex shrink-0"
              aria-label={__("Inherits from ancestor", "scheduled-page-reviews")}
            >
              <CornerLeftDown className="h-3.5 w-3.5 text-muted-foreground/80" />
            </span>
          </TooltipTrigger>
          <TooltipContent>{inheritedRuleTooltip(summary)}</TooltipContent>
        </Tooltip>
      )}
    </div>
  );
}
