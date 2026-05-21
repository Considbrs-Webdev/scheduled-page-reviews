import { useState } from "react";
import { User2, Users, Mail, Plus } from "lucide-react";

import { __ } from "@wordpress/i18n";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Popover, PopoverContent, PopoverTrigger } from "@/components/ui/popover";
import type { RecipientTarget, Target } from "@/types";

import { UserPickerPopover } from "./UserPickerPopover";
import { RolePickerPopover } from "./RolePickerPopover";

interface TargetPickerProps {
  onAdd: (t: Target) => void;
  excludeUserIds?: number[];
  excludeRoleSlugs?: string[];
}

export function TargetPicker({ onAdd, excludeUserIds, excludeRoleSlugs }: TargetPickerProps) {
  return (
    <div className="flex flex-wrap gap-2">
      <UserPickerPopover
        excludeIds={excludeUserIds}
        onSelect={(t: RecipientTarget) => onAdd(t)}
        trigger={
          <Button type="button" variant="outline" size="sm">
            <User2 className="mr-1.5 h-3.5 w-3.5" /> {__("Add user", "content-ownership")}
          </Button>
        }
      />
      <RolePickerPopover
        excludeSlugs={excludeRoleSlugs}
        onSelect={(t) => onAdd(t)}
        trigger={
          <Button type="button" variant="outline" size="sm">
            <Users className="mr-1.5 h-3.5 w-3.5" /> {__("Add group", "content-ownership")}
          </Button>
        }
      />
      <EmailAdder onAdd={(t: RecipientTarget) => onAdd(t)} />
    </div>
  );
}

function EmailAdder({ onAdd }: { onAdd: (t: RecipientTarget) => void }) {
  const [open, setOpen] = useState(false);
  const [value, setValue] = useState("");
  const submit = () => {
    const email = value.trim();
    if (!email || !/^.+@.+\..+$/.test(email)) return;
    onAdd({ type: "email", value: email });
    setValue("");
    setOpen(false);
  };
  return (
    <Popover open={open} onOpenChange={setOpen}>
      <PopoverTrigger asChild>
        <Button type="button" variant="outline" size="sm">
          <Mail className="mr-1.5 h-3.5 w-3.5" /> {__("Add email", "content-ownership")}
        </Button>
      </PopoverTrigger>
      <PopoverContent className="w-64" align="start">
        <div className="grid gap-2">
          <label className="text-xs font-medium text-muted-foreground">
            {__("Email address", "content-ownership")}
          </label>
          <Input
            type="email"
            placeholder={__("alerts@example.com", "content-ownership")}
            value={value}
            onChange={(e) => setValue(e.target.value)}
            onKeyDown={(e) => { if (e.key === "Enter") { e.preventDefault(); submit(); } }}
          />
          <Button type="button" size="sm" onClick={submit}>
            <Plus className="mr-1 h-3.5 w-3.5" /> {__("Add", "content-ownership")}
          </Button>
        </div>
      </PopoverContent>
    </Popover>
  );
}
