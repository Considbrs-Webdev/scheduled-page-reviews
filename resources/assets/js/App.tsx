import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { useUrlStateSync } from "@/hooks/useUrlStateSync";
import { GeneralSettingsTab } from "@/general/GeneralSettingsTab";
import { Header } from "@/layout/Header";
import { NavigationGuard } from "@/layout/NavigationGuard";
import { navigateToTab } from "@/lib/navigation";
import { PagesTab } from "@/pages/PagesTab";
import { useUiStore } from "@/store/ui";

export function App() {
  useUrlStateSync();

  const activeTab = useUiStore((s) => s.activeTab);

  const handleTabChange = (value: string) => {
    if (value !== "pages" && value !== "general") return;
    navigateToTab(value);
  };

  return (
    <div className="content-ownership-shell mx-auto flex min-h-0 w-full max-w-[1400px] flex-1 flex-col px-4 py-4">
      <Header />
      <Tabs
        value={activeTab}
        onValueChange={handleTabChange}
        className="mt-4 flex min-h-0 flex-1 flex-col"
      >
        <TabsList>
          <TabsTrigger value="pages">Pages</TabsTrigger>
          <TabsTrigger value="general">General settings</TabsTrigger>
        </TabsList>
        <TabsContent value="pages" className="mt-4 flex min-h-0 flex-1 flex-col">
          <PagesTab />
        </TabsContent>
        <TabsContent value="general" className="mt-4 min-h-0 flex-1 overflow-auto">
          <GeneralSettingsTab />
        </TabsContent>
      </Tabs>
      <NavigationGuard />
    </div>
  );
}
