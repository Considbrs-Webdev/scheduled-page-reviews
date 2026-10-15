export interface RenewpageBoot {
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
    renewpageBoot?: RenewpageBoot;
  }
}

const fallback: RenewpageBoot = {
  restRoot: "",
  nonce: "",
  currentUserId: 0,
  locale: "en-US",
  dateFormat: "Y-m-d",
  pluginVersion: "0.0.0",
  pageSlug: "renewpage-scheduled-page-reviews",
  capabilities: { manage: false },
};

export function getBoot(): RenewpageBoot {
  return window.renewpageBoot ?? fallback;
}
