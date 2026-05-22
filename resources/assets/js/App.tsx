import { createPortal } from "react-dom";

import { NavigationGuard } from "@/layout/NavigationGuard";
import { AppHeader } from "@/layout/AppHeader";
import { useUrlStateSync } from "@/hooks/useUrlStateSync";
import { getHeaderInteractiveMount } from "@/lib/header-mount";
import { PagesTab } from "@/pages/PagesTab";
import { SettingsTab } from "@/settings/SettingsTab";
import { useUiStore } from "@/store/ui";

export function App() {
  useUrlStateSync();

  const activeTab = useUiStore((s) => s.activeTab);
  const headerMount = getHeaderInteractiveMount();

  return (
    <>
      {headerMount ? createPortal(<AppHeader />, headerMount) : null}
      <div className="content-ownership-shell mx-auto flex min-h-0 w-full max-w-[1400px] flex-1 flex-col px-4 pt-5 pb-4">
        <main className="flex min-h-0 flex-1 flex-col">
          {activeTab === "pages" ? <PagesTab /> : <SettingsTab />}
        </main>
      </div>
      <NavigationGuard />
    </>
  );
}
