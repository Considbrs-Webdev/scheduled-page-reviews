import { CornerLeftDown, Globe } from "lucide-react";
import type { Resolution } from "@/types";

export function InheritedFrom<T>({ resolution, formatValue }: {
  resolution: Resolution<T>;
  formatValue: (value: T) => string;
}) {
  if (resolution.source === "default") {
    return (
      <div className="flex items-center gap-1.5 text-xs text-muted-foreground">
        <Globe className="h-3 w-3" />
        <span>Global default: <strong>{formatValue(resolution.value)}</strong></span>
      </div>
    );
  }
  if (resolution.source === "inherited") {
    return (
      <div className="flex items-center gap-1.5 text-xs text-muted-foreground">
        <CornerLeftDown className="h-3 w-3" />
        <span>Inherited from page #{resolution.from}: <strong>{formatValue(resolution.value)}</strong></span>
      </div>
    );
  }
  return null;
}
