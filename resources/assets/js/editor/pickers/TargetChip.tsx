import { User2, Users, Mail, X } from "lucide-react";

import { __, sprintf } from "@wordpress/i18n";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { useUsersByIds, useGroups } from "@/api/queries";
import type { Target } from "@/types";

import { GroupMemberPreview } from "./GroupMemberPreview";

interface TargetChipProps {
  target: Target;
  onRemove: () => void;
}

export function TargetChip({ target, onRemove }: TargetChipProps) {
  const isGroup = target.type === "group";
  const chip = (
    <Badge
      variant="secondary"
      className={
        "gap-1.5 pl-1.5 pr-1 py-0.5 text-xs " +
        (isGroup ? "cursor-pointer hover:bg-accent" : "")
      }
      title={isGroup ? __("Click to preview current members", "renewpage-scheduled-page-reviews") : undefined}
    >
      <Icon target={target} />
      <span className="truncate"><ChipLabel target={target} /></span>
      <Button
        type="button"
        variant="ghost"
        size="sm"
        className="h-5 w-5 p-0 hover:bg-transparent"
        onClick={(e) => { e.stopPropagation(); onRemove(); }}
        aria-label={__("Remove", "renewpage-scheduled-page-reviews")}
      >
        <X className="h-3 w-3" />
      </Button>
    </Badge>
  );

  if (target.type === "group") {
    return <GroupMemberPreview groupRef={target.value} trigger={chip} />;
  }
  return chip;
}

function Icon({ target }: { target: Target }) {
  if (target.type === "user") return <User2 className="h-3 w-3" />;
  if (target.type === "group") return <Users className="h-3 w-3" />;
  return <Mail className="h-3 w-3" />;
}

function ChipLabel({ target }: { target: Target }) {
  if (target.type === "email") return <>{target.value}</>;
  if (target.type === "user") return <UserName id={target.value} />;
  return <GroupName groupRef={target.value} />;
}

function UserName({ id }: { id: number }) {
  const q = useUsersByIds([id]);
  const u = q.data?.[0];
  return <>{u?.display_name ?? sprintf(/* translators: %d: user ID */ __("User #%d", "renewpage-scheduled-page-reviews"), id)}</>;
}

function GroupName({ groupRef }: { groupRef: string }) {
  const q = useGroups();
  const g = q.data?.flatMap((s) => s.groups).find((x) => x.ref === groupRef);
  return <>{g?.name ?? groupRef}</>;
}
