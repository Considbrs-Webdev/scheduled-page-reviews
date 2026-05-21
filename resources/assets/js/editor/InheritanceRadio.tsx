import { RadioGroup, RadioGroupItem } from "@/components/ui/radio-group";
import { Label } from "@/components/ui/label";

import type { FieldState } from "./schema";

interface InheritanceRadioProps {
  name: string;
  value: FieldState;
  onChange: (s: FieldState) => void;
}

export function InheritanceRadio({ name, value, onChange }: InheritanceRadioProps) {
  return (
    <RadioGroup
      value={value}
      onValueChange={(v) => onChange(v as FieldState)}
      className="flex flex-col gap-1.5"
    >
      <Option name={name} state="inherit" label="Inherit" hint="Use the value from the closest ancestor (or global default)." />
      <Option name={name} state="self" label="Override on this page only" hint="Replace the inherited value here. Descendants keep inheriting from above." />
      <Option name={name} state="subtree" label="Override and apply to subpages" hint="Replace the inherited value here AND become the new inherited value for descendants." />
    </RadioGroup>
  );
}

function Option({ name, state, label, hint }: { name: string; state: FieldState; label: string; hint: string }) {
  const id = `${name}-${state}`;
  return (
    <div className="flex items-start gap-2">
      <RadioGroupItem value={state} id={id} className="mt-0.5" />
      <Label htmlFor={id} className="cursor-pointer text-sm leading-5">
        <div>{label}</div>
        <div className="text-xs font-normal text-muted-foreground">{hint}</div>
      </Label>
    </div>
  );
}
