import { useFormContext } from "react-hook-form";

import { __, sprintf } from "@wordpress/i18n";
import { Input } from "@/components/ui/input";
import { SettingRow } from "@/components/ui/setting-row";
import type { EffectiveSettings } from "@/types";

import { InheritanceRadio } from "../InheritanceRadio";
import { InheritedFrom } from "../InheritedFrom";
import type { RuleFormValues } from "../schema";

export function NotifyBeforeField({ effective }: { effective: EffectiveSettings }) {
  const f = useFormContext<RuleFormValues>();
  const state = f.watch("notify_before.state");

  return (
    <SettingRow
      label={__("Notify before due", "scheduled-page-reviews")}
      description={__(
        'When to show "due soon" in the dashboard widget.',
        "scheduled-page-reviews",
      )}
    >
      <div className="space-y-3">
        <InheritanceRadio
          name="notify_before"
          value={state}
          onChange={(s) => f.setValue("notify_before.state", s, { shouldDirty: true })}
        />
        {state === "inherit" ? (
          <InheritedFrom
            resolution={effective.notify_before}
            formatValue={(n) => sprintf(__("%d days", "scheduled-page-reviews"), n)}
          />
        ) : (
          <div className="flex flex-wrap items-end gap-2">
            <div className="min-w-0 flex-1">
              <label className="mb-1 block text-xs font-medium text-muted-foreground">
                {__("Days before review date", "scheduled-page-reviews")}
              </label>
              <Input
                type="number"
                min={0}
                max={365}
                className="max-w-40"
                {...f.register("notify_before.value", { valueAsNumber: true })}
              />
            </div>
          </div>
        )}
      </div>
    </SettingRow>
  );
}
