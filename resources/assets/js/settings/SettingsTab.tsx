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
  "scheduled-page-reviews",
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
      className="mx-auto flex min-h-0 w-full max-w-5xl flex-1 flex-col"
    >
      <TabsList variant="line" animated className="mb-5 h-11 w-fit">
        <TabsTrigger value="general" className="gap-2 px-5">
          <Settings aria-hidden />
          {__("General", "scheduled-page-reviews")}
        </TabsTrigger>
        <TabsTrigger value="schedule" className="gap-2 px-5">
          <CalendarSync aria-hidden />
          {__("Schedule", "scheduled-page-reviews")}
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
