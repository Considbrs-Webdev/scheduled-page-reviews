import { useFormContext } from "react-hook-form";

import { __, sprintf } from "@wordpress/i18n";
import { SettingRow } from "@/components/ui/setting-row";
import type { EffectiveSettings, RecipientTarget } from "@/types";
import { isEmailTarget, isRoleTarget, isUserTarget, targetKey } from "@/types";

import { InheritanceRadio } from "../InheritanceRadio";
import { InheritedFrom } from "../InheritedFrom";
import { TargetChip } from "../pickers/TargetChip";
import { TargetPicker } from "../pickers/TargetPicker";
import type { RuleFormValues } from "../schema";

export function RecipientsField({ effective }: { effective: EffectiveSettings }) {
  const f = useFormContext<RuleFormValues>();
  const state = f.watch("recipients.state");
  const value = f.watch("recipients.value");

  const excludeUserIds = value.filter(isUserTarget).map((t) => t.value);
  const excludeRoleSlugs = value.filter(isRoleTarget).map((t) => t.value);
  const excludeEmails = new Set(value.filter(isEmailTarget).map((t) => t.value.toLowerCase()));

  const setValue = (next: RecipientTarget[]) =>
    f.setValue("recipients.value", next, { shouldDirty: true });

  return (
    <SettingRow
      label={__("Who to notify", "scheduled-page-reviews")}
      description={__(
        "Add WordPress users, groups, or email addresses. Users and groups receive review reminders and see these pages in their dashboard. Standalone email addresses receive reminders only.",
        "scheduled-page-reviews",
      )}
    >
      <div className="space-y-3">
        <InheritanceRadio
          name="recipients"
          value={state}
          onChange={(s) => f.setValue("recipients.state", s, { shouldDirty: true })}
        />
        {state === "inherit" ? (
          <InheritedFrom
            resolution={effective.recipients}
            formatValue={(v) =>
              v.length === 0
                ? __("nobody configured", "scheduled-page-reviews")
                : sprintf(__("%d recipient(s)", "scheduled-page-reviews"), v.length)}
          />
        ) : (
          <div className="grid gap-3">
            <div className="flex flex-wrap gap-1.5">
              {value.length === 0 ? (
                <span className="text-xs text-muted-foreground">
                  {__("No recipients assigned.", "scheduled-page-reviews")}
                </span>
              ) : (
                value.map((t) => (
                  <TargetChip
                    key={targetKey(t)}
                    target={t}
                    onRemove={() =>
                      setValue(value.filter((x) => targetKey(x) !== targetKey(t)))
                    }
                  />
                ))
              )}
            </div>
            <TargetPicker
              onAdd={(t) => {
                if (t.type === "email" && excludeEmails.has(t.value.toLowerCase())) return;
                if (value.some((x) => targetKey(x) === targetKey(t))) return;
                setValue([...value, t]);
              }}
              excludeUserIds={excludeUserIds}
              excludeRoleSlugs={excludeRoleSlugs}
            />
          </div>
        )}
      </div>
    </SettingRow>
  );
}
