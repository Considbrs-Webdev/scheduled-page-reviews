import { useState } from "react";
import { Search } from "lucide-react";

import { Popover, PopoverContent, PopoverTrigger } from "@/components/ui/popover";
import { Command, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from "@/components/ui/command";
import { useUserSearch } from "@/api/queries";
import type { UserTarget, UserListItem } from "@/types";

interface UserPickerPopoverProps {
  trigger: React.ReactNode;
  onSelect: (target: UserTarget) => void;
  excludeIds?: number[];
}

export function UserPickerPopover({ trigger, onSelect, excludeIds = [] }: UserPickerPopoverProps) {
  const [open, setOpen] = useState(false);
  const [search, setSearch] = useState("");
  const q = useUserSearch(search);
  const exclude = new Set(excludeIds);
  const results = (q.data ?? []).filter((u) => !exclude.has(u.id));

  return (
    <Popover open={open} onOpenChange={setOpen}>
      <PopoverTrigger asChild>{trigger}</PopoverTrigger>
      <PopoverContent className="w-72 p-0" align="start">
        <Command shouldFilter={false}>
          <CommandInput value={search} onValueChange={setSearch} placeholder="Search users…" />
          <CommandList>
            <CommandEmpty>{q.isLoading ? "Searching…" : "No users found."}</CommandEmpty>
            <CommandGroup heading="Users">
              {results.map((u: UserListItem) => (
                <CommandItem
                  key={u.id}
                  value={`${u.display_name} ${u.user_email}`}
                  onSelect={() => {
                    onSelect({ type: "user", value: u.id });
                    setOpen(false);
                    setSearch("");
                  }}
                >
                  <Search className="mr-2 h-3.5 w-3.5 text-muted-foreground" />
                  <div className="flex flex-col">
                    <span>{u.display_name}</span>
                    <span className="text-xs text-muted-foreground">{u.user_email}</span>
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
