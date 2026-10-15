export interface EditorBoot {
  restRoot: string;
  restNamespace: string;
  nonce: string;
  settingsUrl: string;
  canManageSettings: boolean;
  pluginVersion: string;
  locale: string;
  dateFormat: string;
}

declare global {
  interface Window {
    renewpageEditorBoot?: EditorBoot;
  }
}

export function getEditorBoot(): EditorBoot {
  const b = window.renewpageEditorBoot;
  if (!b) {
    throw new Error(
      "renewpageEditorBoot is not defined; was the editor bundle enqueued before WP localised its data?",
    );
  }
  return b;
}
