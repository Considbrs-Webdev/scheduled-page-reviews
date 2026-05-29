import { useFormContext } from "react-hook-form";

import { __, sprintf } from "@wordpress/i18n";
import { Input } from "@/components/ui/input";
import { SettingRow } from "@/components/ui/setting-row";
import type { EffectiveSettings } from "@/types";

import { InheritanceRadio } from "../InheritanceRadio";
import { InheritedFrom } from "../InheritedFrom";
import type { RuleFormValues } from "../schema";

export function IntervalField({ effective }: { effective: EffectiveSettings }) {
  const f = useFormContext<RuleFormValues>();
  const state = f.watch("interval.state");

  return (
    <SettingRow
      label={__("Review interval", "content-ownership")}
      description={__("How often this page must be reviewed.", "content-ownership")}
    >
      <div className="space-y-3">
        <InheritanceRadio
          name="interval"
          value={state}
          onChange={(s) => f.setValue("interval.state", s, { shouldDirty: true })}
        />
        {state === "inherit" ? (
          <InheritedFrom
            resolution={effective.interval_days}
            formatValue={(n) => sprintf(__("%d days", "content-ownership"), n)}
          />
        ) : (
          <div className="flex flex-wrap items-end gap-2">
            <div className="min-w-0 flex-1">
              <label className="mb-1 block text-xs font-medium text-muted-foreground">
                {__("Days between reviews", "content-ownership")}
              </label>
              <Input
                type="number"
                min={1}
                max={3650}
                className="max-w-40"
                {...f.register("interval.value", { valueAsNumber: true })}
              />
            </div>
            <p className="pb-2 text-xs text-muted-foreground">
              {__("e.g. 180 = twice a year", "content-ownership")}
            </p>
          </div>
        )}
      </div>
    </SettingRow>
  );
}
