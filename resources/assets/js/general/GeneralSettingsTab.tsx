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
import { Switch } from "@/components/ui/switch";
import { Textarea } from "@/components/ui/textarea";
import { Separator } from "@/components/ui/separator";
import { SettingRow, SettingSection } from "@/components/ui/setting-row";

import { useGlobalSettings, useUpdateGlobalSettings } from "@/api/queries";
import { isEmailTarget, type RecipientTarget } from "@/types";
import { useUiStore } from "@/store/ui";

import {
  generalSettingsSchema,
  type GeneralSettingsFormValues,
} from "./schema";

export function GeneralSettingsTab() {
  const settingsQ = useGlobalSettings();
  const updateM = useUpdateGlobalSettings({
    onSuccess: () => toast.success(__("Global settings saved.", "renewpage-scheduled-page-reviews")),
    onError: (e) =>
      toast.error(e instanceof Error ? e.message : __("Could not save settings.", "renewpage-scheduled-page-reviews")),
  });
  const setUnsaved = useUiStore((s) => s.setHasUnsavedChanges);

  const form = useForm<GeneralSettingsFormValues>({
    resolver: zodResolver(
      generalSettingsSchema,
    ) as Resolver<GeneralSettingsFormValues>,
    defaultValues: {
      default_interval_days: 180,
      notify_days_before: 14,
      send_reminder_after_due: true,
      reminder_cadence_days: 7,
      sync_wp_modified_on_review: false,
      default_recipient_emails_text: "",
    },
  });

  const resetToServer = useCallback(() => {
    if (!settingsQ.data) return;
    const emails = settingsQ.data.default_recipients
      .filter(isEmailTarget)
      .map((t) => t.value);
    form.reset({
      default_interval_days: settingsQ.data.default_interval_days,
      notify_days_before: settingsQ.data.notify_days_before,
      send_reminder_after_due: settingsQ.data.send_reminder_after_due,
      reminder_cadence_days: settingsQ.data.reminder_cadence_days,
      sync_wp_modified_on_review: settingsQ.data.sync_wp_modified_on_review,
      default_recipient_emails_text: emails.join(", "),
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
    const emails = values.default_recipient_emails_text
      .split(/[,\s]+/)
      .map((e: string) => e.trim())
      .filter((e: string) => e.length > 0);

    const existingNonEmail: RecipientTarget[] = (
      settingsQ.data?.default_recipients ?? []
    ).filter((t) => !isEmailTarget(t));

    const default_recipients: RecipientTarget[] = [
      ...existingNonEmail,
      ...emails.map((value: string) => ({ type: "email" as const, value })),
    ];

    updateM.mutate(
      {
        default_interval_days: values.default_interval_days,
        notify_days_before: values.notify_days_before,
        send_reminder_after_due: values.send_reminder_after_due,
        reminder_cadence_days: values.reminder_cadence_days,
        sync_wp_modified_on_review: values.sync_wp_modified_on_review,
        default_recipients,
      },
      { onSuccess: () => form.reset(values) },
    );
  });

  if (settingsQ.isLoading) {
    return <SettingsSkeleton rows={4} />;
  }
  if (settingsQ.error) {
    return (
      <div className="text-sm text-destructive">
        {__("Failed to load settings:", "renewpage-scheduled-page-reviews")} {settingsQ.error.message}
      </div>
    );
  }

  const sendReminderAfterDue = form.watch("send_reminder_after_due");

  return (
    <Form {...form}>
      <form onSubmit={onSubmit} className="space-y-8">
        <SettingSection
          title={__("Review intervals", "renewpage-scheduled-page-reviews")}
          description={__(
            "How often pages must be reviewed and when reminders go out.",
            "renewpage-scheduled-page-reviews",
          )}
        >
          <FormField
            control={form.control}
            name="default_interval_days"
            render={({ field }) => (
              <SettingRow
                label={__("Default review interval (days)", "renewpage-scheduled-page-reviews")}
                description={__(
                  "Used when a page has no rule of its own.",
                  "renewpage-scheduled-page-reviews",
                )}
              >
                <FormItem>
                  <FormControl>
                    <Input
                      type="number"
                      min={1}
                      max={3650}
                      className="max-w-40"
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
            name="notify_days_before"
            render={({ field }) => (
              <SettingRow
                label={__("Notify days before", "renewpage-scheduled-page-reviews")}
                description={__(
                  'Window in which a page is "due soon".',
                  "renewpage-scheduled-page-reviews",
                )}
              >
                <FormItem>
                  <FormControl>
                    <Input
                      type="number"
                      min={0}
                      max={365}
                      className="max-w-40"
                      {...field}
                    />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              </SettingRow>
            )}
          />
        </SettingSection>

        <SettingSection
          title={__("Reminders", "renewpage-scheduled-page-reviews")}
          description={__(
            "Control repeat emails while a page stays due or overdue. Marking a page reviewed starts a new cycle.",
            "renewpage-scheduled-page-reviews",
          )}
        >
          <FormField
            control={form.control}
            name="send_reminder_after_due"
            render={({ field }) => (
              <SettingRow label={__("Send reminders after due date", "renewpage-scheduled-page-reviews")}>
                <FormItem>
                  <FormControl>
                    <Switch
                      checked={field.value}
                      onCheckedChange={field.onChange}
                    />
                  </FormControl>
                  <FormDescription>
                    {__(
                      "When off, each page is included in at most one digest per review cycle (until marked reviewed). When on, overdue pages can appear again after the cadence interval.",
                      "renewpage-scheduled-page-reviews",
                    )}
                  </FormDescription>
                  <FormMessage />
                </FormItem>
              </SettingRow>
            )}
          />
          {sendReminderAfterDue ? (
            <FormField
              control={form.control}
              name="reminder_cadence_days"
              render={({ field }) => (
                <SettingRow
                  label={__("Reminder cadence (days)", "renewpage-scheduled-page-reviews")}
                  description={__(
                    "Minimum days before the same overdue page can appear in another digest. Currently tracked per page, not per recipient — see README.",
                    "renewpage-scheduled-page-reviews",
                  )}
                >
                  <FormItem>
                    <FormControl>
                      <Input
                        type="number"
                        min={1}
                        max={365}
                        className="max-w-40"
                        {...field}
                      />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                </SettingRow>
              )}
            />
          ) : null}
        </SettingSection>

        <SettingSection
          title={__("Mark as reviewed", "renewpage-scheduled-page-reviews")}
          description={__(
            "What happens when someone marks a page as reviewed. Plugin review meta is always stored.",
            "renewpage-scheduled-page-reviews",
          )}
        >
          <FormField
            control={form.control}
            name="sync_wp_modified_on_review"
            render={({ field }) => (
              <SettingRow label={__("Update WordPress last modified date", "renewpage-scheduled-page-reviews")}>
                <FormItem>
                  <FormControl>
                    <Switch
                      checked={field.value}
                      onCheckedChange={field.onChange}
                    />
                  </FormControl>
                  <FormDescription>
                    {__(
                      'Also updates the post\'s modified timestamp (shown as "updated" on the front-end). Does not create a new revision.',
                      "renewpage-scheduled-page-reviews",
                    )}
                  </FormDescription>
                  <FormMessage />
                </FormItem>
              </SettingRow>
            )}
          />
        </SettingSection>

        <SettingSection
          title={__("Default recipients", "renewpage-scheduled-page-reviews")}
          description={__(
            "Recipients used as a fallback when no per-page or inherited recipient is set. Email addresses only here for now; per-page rules support users and groups too.",
            "renewpage-scheduled-page-reviews",
          )}
        >
          <FormField
            control={form.control}
            name="default_recipient_emails_text"
            render={({ field }) => (
              <SettingRow
                label={__("Default recipient emails", "renewpage-scheduled-page-reviews")}
                description={__(
                  "Separate multiple addresses with commas or whitespace.",
                  "renewpage-scheduled-page-reviews",
                )}
              >
                <FormItem>
                  <FormControl>
                    <Textarea
                      rows={3}
                      className="min-h-28 max-w-xl"
                      placeholder={__("alerts@example.com, ops@example.com", "renewpage-scheduled-page-reviews")}
                      {...field}
                    />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              </SettingRow>
            )}
          />
        </SettingSection>

        <Separator />

        <div className="flex justify-end gap-2">
          <Button
            type="button"
            variant="outline"
            disabled={!form.formState.isDirty || updateM.isPending}
            onClick={resetToServer}
          >
            {__("Reset", "renewpage-scheduled-page-reviews")}
          </Button>
          <Button
            type="submit"
            disabled={!form.formState.isDirty || updateM.isPending}
          >
            <Save className="mr-2 h-4 w-4" />
            {updateM.isPending ? __("Saving…", "renewpage-scheduled-page-reviews") : __("Save changes", "renewpage-scheduled-page-reviews")}
          </Button>
        </div>
      </form>
    </Form>
  );
}
