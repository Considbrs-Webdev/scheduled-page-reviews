import { __ } from "@wordpress/i18n";
import { RadioGroup, RadioGroupItem } from "@/components/ui/radio-group";
import { Label } from "@/components/ui/label";

import type { FieldState } from "./schema";

interface InheritanceRadioProps {
  name: string;
  value: FieldState;
  onChange: (s: FieldState) => void;
}

const OPTIONS: { state: FieldState; label: string; hint: string }[] = [
  {
    state: "inherit",
    label: __("Inherit", "content-ownership"),
    hint: __(
      "Use the value from the closest ancestor (or global default).",
      "content-ownership",
    ),
  },
  {
    state: "self",
    label: __("This page only", "content-ownership"),
    hint: __(
      "Replace the inherited value here. Descendants keep inheriting from above.",
      "content-ownership",
    ),
  },
  {
    state: "subtree",
    label: __("This page and subpages", "content-ownership"),
    hint: __(
      "Replace the inherited value here and apply it to descendants.",
      "content-ownership",
    ),
  },
];

export function InheritanceRadio({ name, value, onChange }: InheritanceRadioProps) {
  const selected = OPTIONS.find((o) => o.state === value);

  return (
    <div className="space-y-2">
      <RadioGroup
        value={value}
        onValueChange={(v) => onChange(v as FieldState)}
        className="grid gap-1 sm:grid-cols-3"
      >
        {OPTIONS.map(({ state, label }) => {
          const id = `${name}-${state}`;
          return (
            <div key={state} className="flex items-center gap-2 rounded-md border border-border px-2 py-1.5">
              <RadioGroupItem value={state} id={id} />
              <Label htmlFor={id} className="cursor-pointer text-sm leading-tight">
                {label}
              </Label>
            </div>
          );
        })}
      </RadioGroup>
      {selected ? (
        <p className="text-xs text-muted-foreground">{selected.hint}</p>
      ) : null}
    </div>
  );
}
