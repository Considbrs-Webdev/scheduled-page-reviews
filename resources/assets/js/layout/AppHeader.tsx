import { LayoutGrid, Settings } from "lucide-react";
import { __ } from "@wordpress/i18n";

import { TabsList, TabsTrigger } from "@/components/ui/tabs";
import { getBoot } from "@/lib/boot";

import { SendRemindersButton } from "./SendRemindersButton";

/**
 * Interactive header controls portaled into the PHP admin header shell.
 * Must render inside the root {@see Tabs} provider in App.tsx.
 */
export function AppHeader() {
  const boot = getBoot();

  return (
    <>
      <TabsList
        variant="header"
        animated
        className="h-10"
        aria-label={__("Main navigation", "scheduled-page-reviews")}
      >
        <TabsTrigger value="pages" className="gap-2">
          <LayoutGrid aria-hidden />
          {__("Pages", "scheduled-page-reviews")}
        </TabsTrigger>
        <TabsTrigger value="settings" className="gap-2">
          <Settings aria-hidden />
          {__("Settings", "scheduled-page-reviews")}
        </TabsTrigger>
      </TabsList>

      <div className="flex shrink-0 items-center gap-3">
        <span className="text-xs text-white/70">v{boot.pluginVersion}</span>
        <SendRemindersButton inHeader />
      </div>
    </>
  );
}
