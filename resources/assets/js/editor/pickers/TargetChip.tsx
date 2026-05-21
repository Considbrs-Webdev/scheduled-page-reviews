import { User2, Users, Mail, X } from "lucide-react";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { useUsersByIds, useRoles } from "@/api/queries";
import type { Target } from "@/types";

import { RoleMemberPreview } from "./RoleMemberPreview";

interface TargetChipProps {
  target: Target;
  onRemove: () => void;
}

export function TargetChip({ target, onRemove }: TargetChipProps) {
  const isRole = target.type === "role";
  const chip = (
    <Badge
      variant="secondary"
      className={
        "gap-1.5 pl-1.5 pr-1 py-0.5 text-xs " +
        (isRole ? "cursor-pointer hover:bg-accent" : "")
      }
      title={isRole ? "Click to preview current members" : undefined}
    >
      <Icon target={target} />
      <span className="truncate"><ChipLabel target={target} /></span>
      <Button
        type="button"
        variant="ghost"
        size="sm"
        className="h-5 w-5 p-0 hover:bg-transparent"
        onClick={(e) => { e.stopPropagation(); onRemove(); }}
        aria-label="Remove"
      >
        <X className="h-3 w-3" />
      </Button>
    </Badge>
  );

  if (target.type === "role") {
    return <RoleMemberPreview slug={target.value} trigger={chip} />;
  }
  return chip;
}

function Icon({ target }: { target: Target }) {
  if (target.type === "user") return <User2 className="h-3 w-3" />;
  if (target.type === "role") return <Users className="h-3 w-3" />;
  return <Mail className="h-3 w-3" />;
}

function ChipLabel({ target }: { target: Target }) {
  if (target.type === "email") return <>{target.value}</>;
  if (target.type === "user") return <UserName id={target.value} />;
  return <RoleName slug={target.value} />;
}

function UserName({ id }: { id: number }) {
  const q = useUsersByIds([id]);
  const u = q.data?.[0];
  return <>{u?.display_name ?? `User #${id}`}</>;
}

function RoleName({ slug }: { slug: string }) {
  const q = useRoles();
  const r = q.data?.find((x) => x.slug === slug);
  return <>{r?.name ?? slug}</>;
}
