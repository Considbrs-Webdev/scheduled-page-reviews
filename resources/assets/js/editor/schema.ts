import { z } from "zod";
import type { RecipientTarget, Rule } from "@/types";

export type FieldState = "inherit" | "self" | "subtree";

const fieldStateSchema = z.enum(["inherit", "self", "subtree"]);

const recipientTargetSchema = z.discriminatedUnion("type", [
  z.object({ type: z.literal("user"), value: z.number().int().positive() }),
  z.object({ type: z.literal("role"), value: z.string().min(1) }),
  z.object({ type: z.literal("email"), value: z.string().email() }),
]);

export const ruleFormSchema = z.object({
  interval: z.object({
    state: fieldStateSchema,
    value: z.number().int().min(1).max(3650),
  }),
  notify_before: z.object({
    state: fieldStateSchema,
    value: z.number().int().min(0).max(365),
  }),
  recipients: z.object({
    state: fieldStateSchema,
    value: z.array(recipientTargetSchema),
  }),
});

export type RuleFormValues = z.infer<typeof ruleFormSchema>;

/**
 * Build form initial values from the API's PageRuleResponse:
 * - If `rule.<field>` is present → state = its scope; value = its value.
 * - Otherwise → state = 'inherit'; value = the effective value (so when
 *   the user clicks "Override locally" they start from a sensible default).
 */
export function ruleResponseToFormValues(
  rule: Rule,
  effective: {
    interval_days: number;
    notify_before: number;
    recipients: RecipientTarget[];
  },
): RuleFormValues {
  return {
    interval: rule.interval_days
      ? { state: rule.interval_days.scope, value: rule.interval_days.value }
      : { state: "inherit", value: effective.interval_days },
    notify_before: rule.notify_before
      ? { state: rule.notify_before.scope, value: rule.notify_before.value }
      : { state: "inherit", value: effective.notify_before },
    recipients: rule.recipients
      ? { state: rule.recipients.scope, value: rule.recipients.value }
      : { state: "inherit", value: effective.recipients },
  };
}

/**
 * Project form values back to a sparse Rule for PUT /pages/<id>/rule.
 * Fields whose state is "inherit" are omitted (the backend then drops them).
 */
export function formValuesToRule(values: RuleFormValues): Rule {
  const out: Rule = {};
  if (values.interval.state !== "inherit") {
    out.interval_days = { value: values.interval.value, scope: values.interval.state };
  }
  if (values.notify_before.state !== "inherit") {
    out.notify_before = { value: values.notify_before.value, scope: values.notify_before.state };
  }
  if (values.recipients.state !== "inherit") {
    out.recipients = { value: values.recipients.value, scope: values.recipients.state };
  }
  return out;
}
