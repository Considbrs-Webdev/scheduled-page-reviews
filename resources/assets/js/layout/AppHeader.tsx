import { LayoutGrid, Settings } from "lucide-react";
import { __ } from "@wordpress/i18n";

import { cn } from "@/lib/utils";
import { getBoot } from "@/lib/boot";
import { navigateToTab } from "@/lib/navigation";
import type { Tab } from "@/lib/url-state";
import { useUiStore } from "@/store/ui";

import { RunScanButton } from "./RunScanButton";

const NAV_ITEMS: { tab: Tab; label: string; icon: typeof LayoutGrid }[] = [
  { tab: "pages", label: __("Pages", "content-ownership"), icon: LayoutGrid },
  { tab: "settings", label: __("Settings", "content-ownership"), icon: Settings },
];

/**
 * Interactive header controls portaled into the PHP admin header shell.
 */
export function AppHeader() {
  const boot = getBoot();
  const activeTab = useUiStore((s) => s.activeTab);

  return (
    <>
      <nav
        className="co-app-header-nav flex items-center gap-2"
        aria-label={__("Main navigation", "content-ownership")}
      >
        {NAV_ITEMS.map(({ tab, label, icon: Icon }) => {
          const active = activeTab === tab;
          return (
            <button
              key={tab}
              type="button"
              onClick={() => navigateToTab(tab)}
              className={cn(
                "co-app-header-nav-link inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium text-white transition-colors",
                active && "co-app-header-nav-link-active",
              )}
              aria-current={active ? "page" : undefined}
            >
              <Icon className="h-4 w-4 shrink-0" aria-hidden />
              <span>{label}</span>
            </button>
          );
        })}
      </nav>

      <div className="flex shrink-0 items-center gap-3">
        <span className="text-xs text-white/70">v{boot.pluginVersion}</span>
        <RunScanButton variant="header" />
      </div>
    </>
  );
}
