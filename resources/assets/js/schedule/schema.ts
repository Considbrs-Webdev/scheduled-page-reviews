import { z } from "zod";

export const scheduleSettingsSchema = z.object({
  auto_scan_enabled: z.boolean(),
  scan_frequency: z.enum(["daily", "weekly"]),
  scan_time: z.string().regex(/^\d{2}:\d{2}$/),
  cron_batch_size: z.coerce.number().int().min(1).max(2000),
});

export type ScheduleSettingsFormValues = z.infer<typeof scheduleSettingsSchema>;
