import { getBoot } from "@/lib/boot";
import { Separator } from "@/components/ui/separator";
import { RunCronButton } from "./RunCronButton";

export function Header() {
  const boot = getBoot();
  return (
    <header className="flex items-start justify-between gap-4">
      <div>
        <h1 className="text-2xl font-semibold tracking-tight">Content ownership</h1>
        <p className="mt-1 text-sm text-muted-foreground">
          Manage page review intervals, notification recipients and hierarchical
          inheritance for the whole site.
        </p>
      </div>
      <div className="flex items-center gap-3">
        <span className="text-xs text-muted-foreground">v{boot.pluginVersion}</span>
        <Separator orientation="vertical" className="h-6" />
        <RunCronButton />
      </div>
    </header>
  );
}
