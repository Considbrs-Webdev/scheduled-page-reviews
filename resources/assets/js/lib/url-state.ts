import { getBoot } from "@/lib/boot";

/** Dispatched after programmatic URL updates (pushState/replaceState). */
export const URL_STATE_EVENT = "scheduled-page-reviews:urlstatechange";

export type Tab = "pages" | "settings";

export interface AppUrlState {
  tab: Tab;
  pageId: number | null;
}

const TAB_KEY = "tab";
const PAGE_ID_KEY = "page_id";
const WP_PAGE_KEY = "page";

export const DEFAULT_TAB: Tab = "pages";

export function parseTab(value: string | null): Tab {
  if (
    value === "settings" ||
    value === "general" ||
    value === "schedule"
  ) {
    return "settings";
  }
  return "pages";
}

export function parsePageId(value: string | null): number | null {
  if (!value) return null;
  const parsed = Number.parseInt(value, 10);
  return Number.isFinite(parsed) && parsed > 0 ? parsed : null;
}

export function getAppUrlState(
  search = window.location.search,
): AppUrlState {
  const params = new URLSearchParams(search);
  const tab = parseTab(params.get(TAB_KEY));

  return {
    tab,
    pageId: tab === "pages" ? parsePageId(params.get(PAGE_ID_KEY)) : null,
  };
}

export function appUrlStateFromUi(state: {
  activeTab: Tab;
  selectedPageId: number | null;
}): AppUrlState {
  return {
    tab: state.activeTab,
    pageId:
      state.activeTab === "pages" ? state.selectedPageId : null,
  };
}

export function appUrlStatesEqual(a: AppUrlState, b: AppUrlState): boolean {
  return a.tab === b.tab && a.pageId === b.pageId;
}

export function buildAppSearchParams(state: AppUrlState): URLSearchParams {
  const params = new URLSearchParams(window.location.search);

  params.set(WP_PAGE_KEY, getBoot().pageSlug);

  if (state.tab === DEFAULT_TAB) {
    params.delete(TAB_KEY);
  } else {
    params.set(TAB_KEY, state.tab);
  }

  if (state.tab === "pages" && state.pageId != null) {
    params.set(PAGE_ID_KEY, String(state.pageId));
  } else {
    params.delete(PAGE_ID_KEY);
  }

  return params;
}

export function buildAppUrl(state: AppUrlState): string {
  const params = buildAppSearchParams(state);
  return `${window.location.pathname}?${params.toString()}`;
}

export function updateAppUrlState(
  state: Partial<AppUrlState>,
  { push = false }: { push?: boolean } = {},
): AppUrlState {
  const current = getAppUrlState();
  const merged: AppUrlState = {
    tab: state.tab ?? current.tab,
    pageId:
      state.pageId !== undefined
        ? state.pageId
        : current.tab === "pages"
          ? current.pageId
          : null,
  };

  if (merged.tab !== "pages") {
    merged.pageId = null;
  }

  const nextUrl = buildAppUrl(merged);
  const currentUrl = `${window.location.pathname}${window.location.search}`;

  if (nextUrl !== currentUrl) {
    if (push) {
      window.history.pushState(null, "", nextUrl);
    } else {
      window.history.replaceState(null, "", nextUrl);
    }
    window.dispatchEvent(new CustomEvent(URL_STATE_EVENT));
  }

  return merged;
}
