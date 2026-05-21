import type { TreeNode } from "@/types";

export interface ArboristNode {
  id: string;
  pageId: number;
  name: string;
  has_local_rule: boolean;
  has_subtree_rule: boolean;
  has_children: boolean;
  children?: ArboristNode[];
}

export function buildArboristTree(flat: TreeNode[]): ArboristNode[] {
  const byId = new Map<number, ArboristNode>();
  const roots: ArboristNode[] = [];
  for (const n of flat) {
    const node: ArboristNode = {
      id: String(n.id),
      pageId: n.id,
      name: n.title || `Page #${n.id}`,
      has_local_rule: n.has_local_rule,
      has_subtree_rule: n.has_subtree_rule,
      has_children: n.has_children,
      children: n.has_children ? [] : undefined,
    };
    byId.set(n.id, node);
    if (n.parent === 0) {
      roots.push(node);
    } else {
      const parent = byId.get(n.parent);
      if (parent) {
        parent.children ??= [];
        parent.children.push(node);
      } else {
        roots.push(node);
      }
    }
  }
  return roots;
}

/**
 * Walk the arborist tree and return every node whose name contains
 * the search term (case-insensitive). Used to expand matching ancestors.
 */
export function findMatchingIds(
  nodes: ArboristNode[],
  search: string,
): Set<string> {
  const out = new Set<string>();
  const needle = search.trim().toLowerCase();
  if (!needle) return out;
  const visit = (n: ArboristNode, ancestors: string[]) => {
    if (n.name.toLowerCase().includes(needle)) {
      out.add(n.id);
      for (const a of ancestors) out.add(a);
    }
    if (n.children) {
      for (const c of n.children) visit(c, [...ancestors, n.id]);
    }
  };
  for (const r of nodes) visit(r, []);
  return out;
}
