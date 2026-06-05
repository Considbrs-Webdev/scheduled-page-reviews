import { Users } from "lucide-react";

import { __ } from "@wordpress/i18n";
import { Popover, PopoverContent, PopoverTrigger } from "@/components/ui/popover";
import { Command, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from "@/components/ui/command";
import { useRoles } from "@/api/queries";
import type { RoleTarget } from "@/types";

interface RolePickerPopoverProps {
  trigger: React.ReactNode;
  onSelect: (target: RoleTarget) => void;
  excludeSlugs?: string[];
}

export function RolePickerPopover({ trigger, onSelect, excludeSlugs = [] }: RolePickerPopoverProps) {
  const q = useRoles();
  const exclude = new Set(excludeSlugs);
  const roles = (q.data ?? []).filter((r) => !exclude.has(r.slug));

  return (
    <Popover>
      <PopoverTrigger asChild>{trigger}</PopoverTrigger>
      <PopoverContent className="w-72 p-0" align="start">
        <Command>
          <CommandInput placeholder={__("Filter groups…", "scheduled-page-reviews")} />
          <CommandList>
            <CommandEmpty>
              {q.isLoading ? __("Loading…", "scheduled-page-reviews") : __("No groups available.", "scheduled-page-reviews")}
            </CommandEmpty>
            <CommandGroup heading={__("Groups (roles)", "scheduled-page-reviews")}>
              {roles.map((r) => (
                <CommandItem
                  key={r.slug}
                  value={`${r.name} ${r.slug}`}
                  onSelect={() => {
                    onSelect({ type: "role", value: r.slug });
                  }}
                >
                  <Users className="mr-2 h-3.5 w-3.5" />
                  <div className="flex flex-1 items-center justify-between">
                    <span>{r.name}</span>
                    <span className="text-xs text-muted-foreground">{r.count}</span>
                  </div>
                </CommandItem>
              ))}
            </CommandGroup>
          </CommandList>
        </Command>
      </PopoverContent>
    </Popover>
  );
}
