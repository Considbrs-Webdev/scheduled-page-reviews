export interface WpElementApi {
  createElement: typeof import("react").createElement;
  Fragment: typeof import("react").Fragment;
  useState: typeof import("react").useState;
  useEffect: typeof import("react").useEffect;
  useCallback: typeof import("react").useCallback;
  useMemo: typeof import("react").useMemo;
}

export interface WpDataApi {
  select: (store: string) => any;
  useSelect: <T>(mapSelect: (select: (store: string) => any) => T, deps?: unknown[]) => T;
  useDispatch: (store: string) => any;
}

export interface WpPluginsApi {
  registerPlugin: (
    name: string,
    settings: { render: () => unknown; icon?: unknown }
  ) => void;
}

export interface WpComponentsApi {
  PanelBody: any;
  PanelRow: any;
  Button: any;
  Spinner: any;
  Notice: any;
  Flex: any;
  FlexItem: any;
  __experimentalText?: any;
}

export interface WpEditorApi {
  PluginDocumentSettingPanel: any;
}

export interface WpApiFetchApi {
  <T = unknown>(options: { path: string; method?: string; data?: unknown }): Promise<T>;
}

export interface WpI18nApi {
  __: (text: string, domain?: string) => string;
  sprintf: (format: string, ...args: unknown[]) => string;
  _n: (single: string, plural: string, number: number, domain?: string) => string;
}

interface WpRuntime {
  plugins: WpPluginsApi;
  components: WpComponentsApi;
  editor: WpEditorApi;
  data: WpDataApi;
  apiFetch: WpApiFetchApi;
  element: WpElementApi;
  i18n: WpI18nApi;
}

declare global {
  interface Window {
    wp: WpRuntime;
  }
}

export const wp: WpRuntime = window.wp;
