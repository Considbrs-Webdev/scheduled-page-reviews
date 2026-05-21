import {
  appUrlStateFromUi,
  appUrlStatesEqual,
  getAppUrlState,
  updateAppUrlState,
  type Tab,
} from "@/lib/url-state";
import { useUiStore } from "@/store/ui";

const DISCARD_MESSAGE =
  "You have unsaved changes. Discard them and leave this page?";

function confirmDiscardUnsaved(): boolean {
  const { hasUnsavedChanges } = useUiStore.getState();
  if (!hasUnsavedChanges) return true;
  return window.confirm(DISCARD_MESSAGE);
}

function applyUrlStateToStore(urlState: ReturnType<typeof getAppUrlState>): void {
  useUiStore.setState({
    activeTab: urlState.tab,
    selectedPageId: urlState.pageId,
    hasUnsavedChanges: false,
  });
}

/**
 * Switch top-level tabs and keep the admin URL in sync.
 */
export function navigateToTab(
  tab: Tab,
  { push = true }: { push?: boolean } = {},
): boolean {
  const store = useUiStore.getState();
  const next = appUrlStateFromUi({ activeTab: tab, selectedPageId: store.selectedPageId });

  if (appUrlStatesEqual(next, appUrlStateFromUi(store))) {
    return true;
  }

  if (!confirmDiscardUnsaved()) {
    return false;
  }

  applyUrlStateToStore({
    tab,
    pageId: tab === "pages" ? store.selectedPageId : null,
  });

  updateAppUrlState(appUrlStateFromUi(useUiStore.getState()), { push });
  return true;
}

/**
 * Select a page in the tree and keep the admin URL in sync.
 */
export function navigateToPage(
  pageId: number | null,
  { push = false }: { push?: boolean } = {},
): boolean {
  const store = useUiStore.getState();
  const next = appUrlStateFromUi({
    activeTab: "pages",
    selectedPageId: pageId,
  });

  if (appUrlStatesEqual(next, appUrlStateFromUi(store))) {
    return true;
  }

  if (!confirmDiscardUnsaved()) {
    return false;
  }

  applyUrlStateToStore(next);
  updateAppUrlState(next, { push });
  return true;
}

/**
 * Apply the current URL to UI state. Used on first load and browser navigation.
 */
export function syncStoreFromUrl(
  { confirmUnsaved = false }: { confirmUnsaved?: boolean } = {},
): boolean {
  const urlState = getAppUrlState();
  const store = useUiStore.getState();

  if (appUrlStatesEqual(urlState, appUrlStateFromUi(store))) {
    return true;
  }

  if (confirmUnsaved && store.hasUnsavedChanges) {
    if (!window.confirm(DISCARD_MESSAGE)) {
      updateAppUrlState(appUrlStateFromUi(store), { push: false });
      return false;
    }
  }

  applyUrlStateToStore(urlState);
  return true;
}
