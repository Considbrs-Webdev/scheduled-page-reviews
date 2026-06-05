import { createPortal } from "react-dom";

import { Tabs, TabsContent } from "@/components/ui/tabs";
import { NavigationGuard } from "@/layout/NavigationGuard";
import { AppHeader } from "@/layout/AppHeader";
import { useUrlStateSync } from "@/hooks/useUrlStateSync";
import { getHeaderInteractiveMount } from "@/lib/header-mount";
import { navigateToTab } from "@/lib/navigation";
import type { Tab } from "@/lib/url-state";
import { PagesTab } from "@/pages/PagesTab";
import { SettingsTab } from "@/settings/SettingsTab";
import { useUiStore } from "@/store/ui";

export function App() {
  useUrlStateSync();

  const activeTab = useUiStore((s) => s.activeTab);
  const headerMount = getHeaderInteractiveMount();

  const handleTabChange = (value: string) => {
    if (value !== "pages" && value !== "settings") return;
    navigateToTab(value as Tab);
  };

  return (
    <>
      <Tabs
        value={activeTab}
        onValueChange={handleTabChange}
        className="flex min-h-0 flex-1 flex-col"
      >
        {headerMount ? createPortal(<AppHeader />, headerMount) : null}
        <div className="scheduled-page-reviews-shell mx-auto flex min-h-0 w-full max-w-[1400px] flex-1 flex-col px-4 pt-5 pb-4">
          <main className="flex min-h-0 flex-1 flex-col">
            <TabsContent value="pages" className="flex min-h-0 flex-1 flex-col">
              <PagesTab />
            </TabsContent>
            <TabsContent value="settings" className="min-h-0 flex-1 overflow-auto">
              <SettingsTab />
            </TabsContent>
          </main>
        </div>
      </Tabs>
      <NavigationGuard />
    </>
  );
}
