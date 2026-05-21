import { z } from "zod";

export const generalSettingsSchema = z.object({
  default_interval_days:   z.coerce.number().int().min(1).max(3650),
  notify_days_before:      z.coerce.number().int().min(0).max(365),
  send_reminder_after_due: z.boolean(),
  reminder_cadence_days:   z.coerce.number().int().min(1).max(365),
  cron_batch_size:         z.coerce.number().int().min(1).max(2000),
  sync_wp_modified_on_review: z.boolean(),
  default_recipient_emails_text: z.string(),
});

export type GeneralSettingsFormValues = z.infer<typeof generalSettingsSchema>;
