import type { ReactNode } from "react";
import type { NodeApi } from "react-arborist";

import { cn } from "@/lib/utils";

import type { ArboristNode } from "./buildTree";

interface IndentGuidesProps {
  node: NodeApi<ArboristNode>;
  indent: number;
  className?: string;
}

/**
 * Vertical tree guides rendered inside the row's left padding area.
 */
export function IndentGuides({ node, indent, className }: IndentGuidesProps) {
  if (node.level === 0) {
    return null;
  }

  const columns: ReactNode[] = [];

  for (let depth = 0; depth < node.level; depth += 1) {
    let ancestor: NodeApi<ArboristNode> | null = node;
    for (let step = node.level; step > depth + 1; step -= 1) {
      ancestor = ancestor?.parent ?? null;
    }

    const isLastColumn = depth === node.level - 1;
    const hasSiblingBelow = ancestor?.nextSibling !== null;

    columns.push(
      <span
        key={depth}
        className="relative inline-block h-full shrink-0"
        style={{ width: indent }}
      >
        {!isLastColumn && hasSiblingBelow ? (
          <span
            aria-hidden
            className="absolute top-0 bottom-0 left-1/2 w-px -translate-x-1/2 bg-border/70"
          />
        ) : null}
        {isLastColumn ? (
          <>
            <span
              aria-hidden
              className="absolute top-0 left-1/2 h-1/2 w-px -translate-x-1/2 bg-border/70"
            />
            <span
              aria-hidden
              className="absolute top-1/2 left-1/2 h-px w-1/2 bg-border/70"
            />
          </>
        ) : null}
      </span>,
    );
  }

  return (
    <span
      aria-hidden
      className={cn("pointer-events-none absolute inset-y-0 left-0 flex", className)}
      style={{ width: node.level * indent }}
    >
      {columns}
    </span>
  );
}

export const TREE_INDENT_PX = 20;
