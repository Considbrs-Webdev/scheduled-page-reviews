import { useState } from "react";
import { CalendarSync, Settings } from "lucide-react";
import { __ } from "@wordpress/i18n";

import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { GeneralSettingsTab } from "@/general/GeneralSettingsTab";
import { ScheduleTab } from "@/schedule/ScheduleTab";
import { useUiStore } from "@/store/ui";

type SettingsSection = "general" | "schedule";

const DISCARD_MESSAGE = __(
  "You have unsaved changes. Discard them and leave this page?",
  "content-ownership",
);

export function SettingsTab() {
  const [section, setSection] = useState<SettingsSection>("general");
  const hasUnsaved = useUiStore((s) => s.hasUnsavedChanges);

  const handleSectionChange = (value: string) => {
    if (value !== "general" && value !== "schedule") return;
    if (value === section) return;
    if (hasUnsaved && !window.confirm(DISCARD_MESSAGE)) return;
    useUiStore.setState({ hasUnsavedChanges: false });
    setSection(value);
  };

  return (
    <Tabs
      value={section}
      onValueChange={handleSectionChange}
      className="flex min-h-0 flex-1 flex-col"
    >
      <TabsList className="mb-4 h-auto w-fit">
        <TabsTrigger
          value="general"
          className="flex items-center gap-2 px-5 py-2.5 text-sm"
        >
          <Settings className="h-4 w-4 text-muted-foreground" aria-hidden />
          {__("General", "content-ownership")}
        </TabsTrigger>
        <TabsTrigger
          value="schedule"
          className="flex items-center gap-2 px-5 py-2.5 text-sm"
        >
          <CalendarSync className="h-4 w-4 text-muted-foreground" aria-hidden />
          {__("Schedule", "content-ownership")}
        </TabsTrigger>
      </TabsList>
      <TabsContent value="general" className="min-h-0 flex-1 overflow-auto">
        <GeneralSettingsTab />
      </TabsContent>
      <TabsContent value="schedule" className="min-h-0 flex-1 overflow-auto">
        <ScheduleTab />
      </TabsContent>
    </Tabs>
  );
}
