export interface EditorBoot {
  restRoot: string;
  nonce: string;
  settingsUrl: string;
  canManageSettings: boolean;
  pluginVersion: string;
  locale: string;
  dateFormat: string;
}

declare global {
  interface Window {
    scheduledPageReviewsEditorBoot?: EditorBoot;
  }
}

export function getEditorBoot(): EditorBoot {
  const b = window.scheduledPageReviewsEditorBoot;
  if (!b) {
    throw new Error(
      "scheduledPageReviewsEditorBoot is not defined; was the editor bundle enqueued before WP localised its data?",
    );
  }
  return b;
}
