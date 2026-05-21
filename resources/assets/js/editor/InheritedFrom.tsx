import { CornerLeftDown, Globe } from "lucide-react";

import { __, sprintf } from "@wordpress/i18n";
import type { Resolution } from "@/types";

export function InheritedFrom<T>({ resolution, formatValue }: {
  resolution: Resolution<T>;
  formatValue: (value: T) => string;
}) {
  if (resolution.source === "default") {
    return (
      <div className="flex items-center gap-1.5 text-xs text-muted-foreground">
        <Globe className="h-3 w-3" />
        <span>
          {__("Global default:", "content-ownership")}{" "}
          <strong>{formatValue(resolution.value)}</strong>
        </span>
      </div>
    );
  }
  if (resolution.source === "inherited" && resolution.from != null) {
    return (
      <div className="flex items-center gap-1.5 text-xs text-muted-foreground">
        <CornerLeftDown className="h-3 w-3" />
        <span>
          {sprintf(
            /* translators: %d: page ID */
            __("Inherited from page #%d:", "content-ownership"),
            resolution.from,
          )}{" "}
          <strong>{formatValue(resolution.value)}</strong>
        </span>
      </div>
    );
  }
  return null;
}
