import { Popover, PopoverContent, PopoverTrigger } from "@/components/ui/popover";
import { useUserSearch } from "@/api/queries";

export function RoleMemberPreview({ slug, trigger }: { slug: string; trigger: React.ReactNode }) {
  const q = useUserSearch("", slug);
  const users = q.data ?? [];
  return (
    <Popover>
      <PopoverTrigger asChild>{trigger}</PopoverTrigger>
      <PopoverContent className="w-64" align="start">
        <div className="text-xs font-medium uppercase text-muted-foreground">Members</div>
        {q.isLoading && <div className="mt-2 text-sm text-muted-foreground">Loading…</div>}
        {!q.isLoading && users.length === 0 && (
          <div className="mt-2 text-sm text-muted-foreground">No users currently in this group.</div>
        )}
        <ul className="mt-2 max-h-56 space-y-1 overflow-auto text-sm">
          {users.slice(0, 8).map((u) => (
            <li key={u.id} className="flex flex-col">
              <span>{u.display_name}</span>
              <span className="text-xs text-muted-foreground">{u.user_email}</span>
            </li>
          ))}
        </ul>
        {users.length > 8 && (
          <div className="mt-2 text-xs text-muted-foreground">+{users.length - 8} more</div>
        )}
      </PopoverContent>
    </Popover>
  );
}
