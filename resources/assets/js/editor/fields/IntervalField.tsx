import { useFormContext } from "react-hook-form";

import { Input } from "@/components/ui/input";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import type { EffectiveSettings } from "@/types";

import { InheritanceRadio } from "../InheritanceRadio";
import { InheritedFrom } from "../InheritedFrom";
import type { RuleFormValues } from "../schema";

export function IntervalField({ effective }: { effective: EffectiveSettings }) {
  const f = useFormContext<RuleFormValues>();
  const state = f.watch("interval.state");
  return (
    <Card>
      <CardHeader>
        <CardTitle>Review interval</CardTitle>
      </CardHeader>
      <CardContent className="grid gap-4">
        <InheritanceRadio
          name="interval"
          value={state}
          onChange={(s) => f.setValue("interval.state", s, { shouldDirty: true })}
        />
        {state === "inherit"
          ? <InheritedFrom resolution={effective.interval_days} formatValue={(n) => `${n} days`} />
          : (
            <div className="flex items-end gap-2">
              <div className="flex-1">
                <label className="mb-1 block text-xs font-medium text-muted-foreground">Days between reviews</label>
                <Input type="number" min={1} max={3650} {...f.register("interval.value", { valueAsNumber: true })} />
              </div>
              <p className="pb-2 text-xs text-muted-foreground">e.g. 180 = twice a year</p>
            </div>
          )}
      </CardContent>
    </Card>
  );
}
