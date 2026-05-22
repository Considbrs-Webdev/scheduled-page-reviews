import { __ } from "@wordpress/i18n";
import { getBoot } from "@/lib/boot";
import { Separator } from "@/components/ui/separator";
import { RunScanButton } from "./RunScanButton";

export function Header() {
  const boot = getBoot();
  return (
    <header className="flex items-start justify-between gap-4">
      <div>
        <h1 className="text-2xl font-semibold tracking-tight">
          {__("Content ownership", "content-ownership")}
        </h1>
        <p className="mt-1 text-sm text-muted-foreground">
          {__(
            "Manage page review intervals, notification recipients and hierarchical inheritance for the whole site.",
            "content-ownership",
          )}
        </p>
      </div>
      <div className="flex items-center gap-3">
        <span className="text-xs text-muted-foreground">v{boot.pluginVersion}</span>
        <Separator orientation="vertical" className="h-6" />
        <RunScanButton />
      </div>
    </header>
  );
}
