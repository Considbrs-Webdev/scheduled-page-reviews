import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Header } from "@/layout/Header";
import { NavigationGuard } from "@/layout/NavigationGuard";
import { GeneralSettingsTab } from "@/general/GeneralSettingsTab";
import { PagesTab } from "@/pages/PagesTab";
import { useUiStore } from "@/store/ui";

export function App() {
  const activeTab = useUiStore((s) => s.activeTab);
  const setActiveTab = useUiStore((s) => s.setActiveTab);
  const hasUnsaved = useUiStore((s) => s.hasUnsavedChanges);

  const handleTabChange = (value: string) => {
    if (value !== "pages" && value !== "general") return;
    if (hasUnsaved) {
      const confirmed = window.confirm(
        "You have unsaved changes. Discard them and switch tabs?"
      );
      if (!confirmed) return;
    }
    setActiveTab(value);
  };

  return (
    <div className="content-ownership-shell mx-auto max-w-[1400px] px-4 py-4">
      <Header />
      <Tabs value={activeTab} onValueChange={handleTabChange} className="mt-4">
        <TabsList>
          <TabsTrigger value="pages">Pages</TabsTrigger>
          <TabsTrigger value="general">General settings</TabsTrigger>
        </TabsList>
        <TabsContent value="pages" className="mt-4">
          <PagesTab />
        </TabsContent>
        <TabsContent value="general" className="mt-4">
          <GeneralSettingsTab />
        </TabsContent>
      </Tabs>
      <NavigationGuard />
    </div>
  );
}
