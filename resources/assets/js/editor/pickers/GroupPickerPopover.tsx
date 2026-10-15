import { Users } from "lucide-react";

import { __ } from "@wordpress/i18n";
import { Popover, PopoverContent, PopoverTrigger } from "@/components/ui/popover";
import { Command, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from "@/components/ui/command";
import { useGroups } from "@/api/queries";
import type { GroupTarget } from "@/types";

interface GroupPickerPopoverProps {
  trigger: React.ReactNode;
  onSelect: (target: GroupTarget) => void;
  excludeRefs?: string[];
}

export function GroupPickerPopover({ trigger, onSelect, excludeRefs = [] }: GroupPickerPopoverProps) {
  const q = useGroups();
  const exclude = new Set(excludeRefs);
  const sections = (q.data ?? [])
    .map((s) => ({ ...s, groups: s.groups.filter((g) => !exclude.has(g.ref)) }))
    .filter((s) => s.groups.length > 0);

  return (
    <Popover>
      <PopoverTrigger asChild>{trigger}</PopoverTrigger>
      <PopoverContent className="w-72 p-0" align="start">
        <Command>
          <CommandInput placeholder={__("Filter groups…", "renewpage-scheduled-page-reviews")} />
          <CommandList>
            <CommandEmpty>
              {q.isLoading ? __("Loading…", "renewpage-scheduled-page-reviews") : __("No groups available.", "renewpage-scheduled-page-reviews")}
            </CommandEmpty>
            {sections.map((section) => (
              <CommandGroup key={section.key} heading={section.label}>
                {section.groups.map((g) => (
                  <CommandItem
                    key={g.ref}
                    value={`${g.name} ${g.ref}`}
                    onSelect={() => {
                      onSelect({ type: "group", value: g.ref });
                    }}
                  >
                    <Users className="mr-2 h-3.5 w-3.5" />
                    <div className="flex flex-1 items-center justify-between">
                      <span>{g.name}</span>
                      {g.count != null && (
                        <span className="text-xs text-muted-foreground">{g.count}</span>
                      )}
                    </div>
                  </CommandItem>
                ))}
              </CommandGroup>
            ))}
          </CommandList>
        </Command>
      </PopoverContent>
    </Popover>
  );
}
