export interface ScheduledPageReviewsBoot {
  restRoot: string;
  nonce: string;
  currentUserId: number;
  locale: string;
  dateFormat: string;
  pluginVersion: string;
  pageSlug: string;
  capabilities: {
    manage: boolean;
  };
}

declare global {
  interface Window {
    scheduledPageReviewsBoot?: ScheduledPageReviewsBoot;
  }
}

const fallback: ScheduledPageReviewsBoot = {
  restRoot: "",
  nonce: "",
  currentUserId: 0,
  locale: "en-US",
  dateFormat: "Y-m-d",
  pluginVersion: "0.0.0",
  pageSlug: "scheduled-page-reviews",
  capabilities: { manage: false },
};

export function getBoot(): ScheduledPageReviewsBoot {
  return window.scheduledPageReviewsBoot ?? fallback;
}
