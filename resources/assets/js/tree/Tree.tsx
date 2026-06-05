import { useMemo, useRef, useEffect, useState } from "react";

import { __ } from "@wordpress/i18n";
import { Tree as Arborist, type NodeApi, type TreeApi } from "react-arborist";

import { TreeSkeleton } from "@/components/ui/loading-skeletons";
import { useTree } from "@/api/queries";
import { navigateToPage } from "@/lib/navigation";
import { useUiStore } from "@/store/ui";

import { TreeNode } from "./TreeNode";
import { TreeToolbar } from "./TreeToolbar";
import { TreeLegend } from "./TreeLegend";
import { TREE_INDENT_PX } from "./IndentGuides";
import {
  buildArboristTree,
  findMatchingIds,
  type ArboristNode,
} from "./buildTree";

export function Tree() {
  const q = useTree(0, true);
  const selectedPageId = useUiStore((s) => s.selectedPageId);
  const treeSearch = useUiStore((s) => s.treeSearch);
  const expandedIds = useUiStore((s) => s.expandedIds);
  const toggleExpanded = useUiStore((s) => s.toggleExpanded);

  const data = useMemo<ArboristNode[]>(
    () => buildArboristTree(q.data ?? []),
    [q.data],
  );

  const matchingIds = useMemo(
    () => findMatchingIds(data, treeSearch),
    [data, treeSearch],
  );

  const initialOpenState = useMemo<Record<string, boolean>>(() => {
    const map: Record<string, boolean> = {};
    for (const id of expandedIds) {
      map[String(id)] = true;
    }
    return map;
  }, [expandedIds]);

  const containerRef = useRef<HTMLDivElement | null>(null);
  const treeRef = useRef<TreeApi<ArboristNode> | null>(null);
  const [size, setSize] = useState({ w: 320, h: 480 });

  useEffect(() => {
    const el = containerRef.current;
    if (!el) return;
    const ro = new ResizeObserver(([entry]) => {
      if (!entry) return;
      setSize({
        w: Math.max(220, entry.contentRect.width),
        h: Math.max(200, entry.contentRect.height),
      });
    });
    ro.observe(el);
    return () => ro.disconnect();
  }, []);

  useEffect(() => {
    if (!treeSearch.trim() || !treeRef.current) return;
    for (const id of matchingIds) {
      treeRef.current.open(id);
    }
  }, [treeSearch, matchingIds]);

  return (
    <div className="flex h-full flex-col">
      <TreeToolbar pageCount={q.data?.length ?? 0} />
      <div ref={containerRef} className="flex-1 overflow-hidden px-2 py-2">
        {q.isLoading && <TreeSkeleton />}
        {q.error && (
          <div className="px-2 py-4 text-sm text-destructive">
            {q.error.message}
          </div>
        )}
        {q.data && data.length === 0 && (
          <div className="px-2 py-4 text-sm text-muted-foreground">
            {__("No pages found. Create some pages first.", "scheduled-page-reviews")}
          </div>
        )}
        {q.data && data.length > 0 && (
          <Arborist<ArboristNode>
            ref={treeRef}
            data={data}
            width={size.w - 16}
            height={size.h - 16}
            rowHeight={30}
            indent={TREE_INDENT_PX}
            openByDefault={false}
            initialOpenState={initialOpenState}
            disableDrag
            disableDrop
            disableMultiSelection
            searchTerm={treeSearch}
            searchMatch={(n, term) =>
              n.data.name.toLowerCase().includes(term.toLowerCase())
            }
            {...(selectedPageId != null
              ? { selection: String(selectedPageId) }
              : {})}
            onSelect={(nodes: NodeApi<ArboristNode>[]) => {
              const first = nodes[0];
              navigateToPage(first ? first.data.pageId : null);
            }}
            onToggle={(id) => toggleExpanded(Number(id))}
          >
            {TreeNode}
          </Arborist>
        )}
      </div>
      <TreeLegend />
    </div>
  );
}
