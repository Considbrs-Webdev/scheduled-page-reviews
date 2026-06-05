import { useCallback, useEffect } from "react";

import { __ } from "@wordpress/i18n";
import { useForm, type Resolver } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { toast } from "sonner";
import { Save } from "lucide-react";

import { SettingsSkeleton } from "@/components/ui/loading-skeletons";
import { Button } from "@/components/ui/button";
import {
  Form,
  FormControl,
  FormDescription,
  FormField,
  FormItem,
  FormMessage,
} from "@/components/ui/form";
import { Input } from "@/components/ui/input";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { Switch } from "@/components/ui/switch";
import { Separator } from "@/components/ui/separator";
import { SettingRow, SettingSection } from "@/components/ui/setting-row";

import {
  useGlobalSettings,
  useScheduleInfo,
  useUpdateGlobalSettings,
} from "@/api/queries";
import { useUiStore } from "@/store/ui";

import {
  scheduleSettingsSchema,
  type ScheduleSettingsFormValues,
} from "./schema";

function cliExamples(): string {
  return `# ${__(
    "Run scan immediately (recommended when WP-Cron is disabled)",
    "scheduled-page-reviews",
  )}
wp scheduled-page-reviews scan

# ${__(
    "Background mode (schedules batched ticks via WP-Cron)",
    "scheduled-page-reviews",
  )}
wp scheduled-page-reviews scan --background

# ${__("Server crontab — sync scan daily at 22:00", "scheduled-page-reviews")}
0 22 * * * cd /path/to/wordpress && wp --path=wp scheduled-page-reviews scan

# ${__(
    "Alternative: background kickoff + execute due WP events",
    "scheduled-page-reviews",
  )}
0 22 * * * cd /path/to/wordpress && wp --path=wp scheduled-page-reviews scan --background
* * * * * cd /path/to/wordpress && wp --path=wp cron event run --due-now`;
}

function formatNextScheduled(iso: string | null, locale: string): string {
  if (!iso) {
    return __("Not scheduled", "scheduled-page-reviews");
  }
  const date = new Date(iso);
  if (Number.isNaN(date.getTime())) {
    return iso;
  }
  return date.toLocaleString(locale);
}

export function ScheduleTab() {
  const settingsQ = useGlobalSettings();
  const scheduleQ = useScheduleInfo();
  const updateM = useUpdateGlobalSettings({
    onSuccess: () =>
      toast.success(__("Schedule settings saved.", "scheduled-page-reviews")),
    onError: (e) =>
      toast.error(
        e instanceof Error
          ? e.message
          : __("Could not save schedule settings.", "scheduled-page-reviews"),
      ),
  });
  const setUnsaved = useUiStore((s) => s.setHasUnsavedChanges);

  const form = useForm<ScheduleSettingsFormValues>({
    resolver: zodResolver(
      scheduleSettingsSchema,
    ) as Resolver<ScheduleSettingsFormValues>,
    defaultValues: {
      auto_scan_enabled: false,
      scan_frequency: "daily",
      scan_time: "03:00",
      cron_batch_size: 200,
    },
  });

  const resetToServer = useCallback(() => {
    if (!settingsQ.data) return;
    form.reset({
      auto_scan_enabled: settingsQ.data.auto_scan_enabled,
      scan_frequency: settingsQ.data.scan_frequency,
      scan_time: settingsQ.data.scan_time,
      cron_batch_size: settingsQ.data.cron_batch_size,
    });
  }, [settingsQ.data, form]);

  useEffect(() => {
    resetToServer();
  }, [resetToServer]);

  useEffect(() => {
    setUnsaved(form.formState.isDirty);
    return () => setUnsaved(false);
  }, [form.formState.isDirty, setUnsaved]);

  const onSubmit = form.handleSubmit((values) => {
    updateM.mutate(values, { onSuccess: () => form.reset(values) });
  });

  if (settingsQ.isLoading) {
    return <SettingsSkeleton rows={2} />;
  }

  const scheduleInfo = scheduleQ.data;
  const locale = document.documentElement.lang || "en-US";

  return (
    <Form {...form}>
      <form
        onSubmit={onSubmit}
        className="mx-auto flex w-full  flex-col space-y-8 pb-8"
      >
        <SettingSection
          title={__("WP Cron", "scheduled-page-reviews")}
          description={__(
            "Registers an automatic scan in WordPress at the chosen time. This schedules the scan — it does not run until something executes scheduled WP events.",
            "scheduled-page-reviews",
          )}
        >
          <FormField
            control={form.control}
            name="auto_scan_enabled"
            render={({ field }) => (
              <SettingRow label={__("Automatic scan", "scheduled-page-reviews")}>
                <FormItem>
                  <FormControl>
                    <Switch
                      checked={field.value}
                      onCheckedChange={field.onChange}
                    />
                  </FormControl>
                  <FormDescription>
                    {__(
                      "Schedule a recurring scan via WordPress cron.",
                      "scheduled-page-reviews",
                    )}
                  </FormDescription>
                  <FormMessage />
                </FormItem>
              </SettingRow>
            )}
          />

          <FormField
            control={form.control}
            name="scan_frequency"
            render={({ field }) => (
              <SettingRow
                label={__("Scan frequency", "scheduled-page-reviews")}
                description={__(
                  "How often the scheduled scan is registered.",
                  "scheduled-page-reviews",
                )}
              >
                <FormItem>
                  <Select value={field.value} onValueChange={field.onChange}>
                    <FormControl>
                      <SelectTrigger className="max-w-40">
                        <SelectValue />
                      </SelectTrigger>
                    </FormControl>
                    <SelectContent>
                      <SelectItem value="daily">
                        {__("Daily", "scheduled-page-reviews")}
                      </SelectItem>
                      <SelectItem value="weekly">
                        {__("Weekly", "scheduled-page-reviews")}
                      </SelectItem>
                    </SelectContent>
                  </Select>
                  <FormMessage />
                </FormItem>
              </SettingRow>
            )}
          />

          <FormField
            control={form.control}
            name="scan_time"
            render={({ field }) => (
              <SettingRow
                label={__("Scan time", "scheduled-page-reviews")}
                description={__(
                  "Time of day to register the scan (server time).",
                  "scheduled-page-reviews",
                )}
              >
                <FormItem>
                  <FormControl>
                    <Input
                      type="time"
                      step={60}
                      className="max-w-44"
                      {...field}
                    />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              </SettingRow>
            )}
          />

          <FormField
            control={form.control}
            name="cron_batch_size"
            render={({ field }) => (
              <SettingRow
                label={__("Batch size", "scheduled-page-reviews")}
                description={__(
                  "Pages processed per background tick. Larger values finish scheduled scans faster but use more memory.",
                  "scheduled-page-reviews",
                )}
              >
                <FormItem>
                  <FormControl>
                    <Input
                      type="number"
                      min={1}
                      max={2000}
                      className="max-w-40"
                      {...field}
                    />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              </SettingRow>
            )}
          />

          {scheduleInfo ? (
            <SettingRow label={__("Next scheduled", "scheduled-page-reviews")}>
              <div className="rounded-lg border border-border bg-muted/40 px-4 py-3 text-sm">
                <p>
                  {formatNextScheduled(scheduleInfo.next_scheduled_iso, locale)}
                </p>
                {scheduleInfo.wp_cron_disabled ? (
                  <p className="mt-2 text-muted-foreground">
                    {__(
                      "WP-Cron is disabled on this site. Scheduled scans require something to execute due events (for example wp cron event run --due-now from server crontab), or use the WP-CLI examples below.",
                      "scheduled-page-reviews",
                    )}
                  </p>
                ) : null}
              </div>
            </SettingRow>
          ) : null}
        </SettingSection>

        <SettingSection
          title={__("WP-CLI & server crontab", "scheduled-page-reviews")}
          description={__(
            "Run scans from the command line or server crontab for reliable execution — especially when WP-Cron is disabled.",
            "scheduled-page-reviews",
          )}
        >
          <SettingRow label={__("Examples", "scheduled-page-reviews")}>
            <pre className="overflow-x-auto rounded-lg bg-zinc-950 p-4 text-xs text-zinc-100">
              <code>{cliExamples()}</code>
            </pre>
          </SettingRow>
        </SettingSection>

        <Separator />

        <div className="flex justify-end gap-2">
          <Button
            type="button"
            variant="outline"
            disabled={!form.formState.isDirty || updateM.isPending}
            onClick={resetToServer}
          >
            {__("Reset", "scheduled-page-reviews")}
          </Button>
          <Button
            type="submit"
            disabled={!form.formState.isDirty || updateM.isPending}
          >
            <Save className="mr-2 h-4 w-4" />
            {updateM.isPending
              ? __("Saving…", "scheduled-page-reviews")
              : __("Save changes", "scheduled-page-reviews")}
          </Button>
        </div>
      </form>
    </Form>
  );
}
