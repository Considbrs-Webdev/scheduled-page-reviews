import { useFormContext } from "react-hook-form";

import { __, sprintf } from "@wordpress/i18n";
import { Input } from "@/components/ui/input";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import type { EffectiveSettings } from "@/types";

import { InheritanceRadio } from "../InheritanceRadio";
import { InheritedFrom } from "../InheritedFrom";
import type { RuleFormValues } from "../schema";

export function NotifyBeforeField({ effective }: { effective: EffectiveSettings }) {
  const f = useFormContext<RuleFormValues>();
  const state = f.watch("notify_before.state");
  return (
    <Card>
      <CardHeader>
        <CardTitle>{__("Notify before due", "content-ownership")}</CardTitle>
      </CardHeader>
      <CardContent className="grid gap-4">
        <InheritanceRadio
          name="notify_before"
          value={state}
          onChange={(s) => f.setValue("notify_before.state", s, { shouldDirty: true })}
        />
        {state === "inherit"
          ? (
            <InheritedFrom
              resolution={effective.notify_before}
              formatValue={(n) => sprintf(__("%d days", "content-ownership"), n)}
            />
          )
          : (
            <div className="flex items-end gap-2">
              <div className="flex-1">
                <label className="mb-1 block text-xs font-medium text-muted-foreground">
                  {__("Days before review date", "content-ownership")}
                </label>
                <Input type="number" min={0} max={365} {...f.register("notify_before.value", { valueAsNumber: true })} />
              </div>
              <p className="pb-2 text-xs text-muted-foreground">
                {__(
                  'Show "due soon" in the dashboard widget this many days before the review date.',
                  "content-ownership",
                )}
              </p>
            </div>
          )}
      </CardContent>
    </Card>
  );
}
