export interface ContentOwnershipBoot {
  restRoot: string;
  nonce: string;
  currentUserId: number;
  locale: string;
  dateFormat: string;
  pluginVersion: string;
  capabilities: {
    manage: boolean;
  };
}

declare global {
  interface Window {
    contentOwnershipBoot?: ContentOwnershipBoot;
  }
}

const fallback: ContentOwnershipBoot = {
  restRoot: "",
  nonce: "",
  currentUserId: 0,
  locale: "en-US",
  dateFormat: "Y-m-d",
  pluginVersion: "0.0.0",
  capabilities: { manage: false },
};

export function getBoot(): ContentOwnershipBoot {
  return window.contentOwnershipBoot ?? fallback;
}
