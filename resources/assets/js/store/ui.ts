import { create } from "zustand";

import { getAppUrlState, type Tab } from "@/lib/url-state";

export type { Tab };

function initialNavigationState(): Pick<UiState, "activeTab" | "selectedPageId"> {
  const url = getAppUrlState();
  return {
    activeTab: url.tab,
    selectedPageId: url.pageId,
  };
}

interface UiState {
  /** Which top-level tab is visible. */
  activeTab: Tab;
  setActiveTab: (tab: Tab) => void;

  /** Currently selected page in the tree (null = none). */
  selectedPageId: number | null;
  setSelectedPageId: (id: number | null) => void;

  /** Whether expanded state should be all-open by default. */
  expandedIds: number[];
  setExpandedIds: (ids: number[]) => void;
  toggleExpanded: (id: number) => void;

  /**
   * Set to true by any form that has unsaved edits. The layout uses
   * this to confirm before switching tabs / selecting another page / unloading.
   */
  hasUnsavedChanges: boolean;
  setHasUnsavedChanges: (dirty: boolean) => void;

  /**
   * Free-text filter for the tree (matched against page title).
   * Empty string = no filter.
   */
  treeSearch: string;
  setTreeSearch: (search: string) => void;
}

export const useUiStore = create<UiState>((set, get) => ({
  ...initialNavigationState(),
  setActiveTab: (tab) => set({ activeTab: tab }),

  setSelectedPageId: (id) => set({ selectedPageId: id }),

  expandedIds: [],
  setExpandedIds: (ids) => set({ expandedIds: ids }),
  toggleExpanded: (id) => {
    const current = get().expandedIds;
    set({
      expandedIds: current.includes(id)
        ? current.filter((x) => x !== id)
        : [...current, id],
    });
  },

  hasUnsavedChanges: false,
  setHasUnsavedChanges: (dirty) => set({ hasUnsavedChanges: dirty }),

  treeSearch: "",
  setTreeSearch: (search) => set({ treeSearch: search }),
}));
