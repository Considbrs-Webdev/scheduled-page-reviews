import { useCallback, useEffect } from "react";

import { __ } from "@wordpress/i18n";
import { useForm, type Resolver } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { toast } from "sonner";
import { Save } from "lucide-react";

import { Button } from "@/components/ui/button";
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import {
  Form,
  FormControl,
  FormDescription,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from "@/components/ui/form";
import { Input } from "@/components/ui/input";
import { Switch } from "@/components/ui/switch";
import { Textarea } from "@/components/ui/textarea";
import { Separator } from "@/components/ui/separator";

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
    onSuccess: () => toast.success(__("Global settings saved.", "content-ownership")),
    onError: (e) =>
      toast.error(e instanceof Error ? e.message : __("Could not save settings.", "content-ownership")),
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

    // Preserve non-email targets the API might already have stored (user/role).
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
    return (
      <div className="text-sm text-muted-foreground">{__("Loading settings…", "content-ownership")}</div>
    );
  }
  if (settingsQ.error) {
    return (
      <div className="text-sm text-destructive">
        {__("Failed to load settings:", "content-ownership")} {settingsQ.error.message}
      </div>
    );
  }

  const sendReminderAfterDue = form.watch("send_reminder_after_due");

  return (
    <Form {...form}>
      <form onSubmit={onSubmit} className="grid gap-6">
        <Card>
          <CardHeader>
            <CardTitle>{__("Review intervals", "content-ownership")}</CardTitle>
            <CardDescription>
              {__(
                "How often pages must be reviewed and when reminders go out.",
                "content-ownership",
              )}
            </CardDescription>
          </CardHeader>
          <CardContent className="grid gap-4 sm:grid-cols-2">
            <FormField
              control={form.control}
              name="default_interval_days"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>{__("Default review interval (days)", "content-ownership")}</FormLabel>
                  <FormControl>
                    <Input
                      type="number"
                      min={1}
                      max={3650}
                      className="max-w-40"
                      {...field}
                    />
                  </FormControl>
                  <FormDescription>
                    {__("Used when a page has no rule of its own.", "content-ownership")}
                  </FormDescription>
                  <FormMessage />
                </FormItem>
              )}
            />
            <FormField
              control={form.control}
              name="notify_days_before"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>{__("Notify days before", "content-ownership")}</FormLabel>
                  <FormControl>
                    <Input
                      type="number"
                      min={0}
                      max={365}
                      className="max-w-40"
                      {...field}
                    />
                  </FormControl>
                  <FormDescription>
                    {__('Window in which a page is "due soon".', "content-ownership")}
                  </FormDescription>
                  <FormMessage />
                </FormItem>
              )}
            />
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>{__("Reminders", "content-ownership")}</CardTitle>
            <CardDescription>
              {__(
                "Control repeat emails while a page stays due or overdue. Marking a page reviewed starts a new cycle.",
                "content-ownership",
              )}
            </CardDescription>
          </CardHeader>
          <CardContent className="grid gap-4 sm:grid-cols-2">
            <FormField
              control={form.control}
              name="send_reminder_after_due"
              render={({ field }) => (
                <FormItem className="flex flex-col gap-2">
                  <FormLabel>{__("Send reminders after due date", "content-ownership")}</FormLabel>
                  <FormControl>
                    <Switch
                      checked={field.value}
                      onCheckedChange={field.onChange}
                    />
                  </FormControl>
                  <FormDescription>
                    {__(
                      "When off, each page is included in at most one digest per review cycle (until marked reviewed). When on, overdue pages can appear again after the cadence interval.",
                      "content-ownership",
                    )}
                  </FormDescription>
                  <FormMessage />
                </FormItem>
              )}
            />
            {sendReminderAfterDue ? (
              <FormField
                control={form.control}
                name="reminder_cadence_days"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>{__("Reminder cadence (days)", "content-ownership")}</FormLabel>
                    <FormControl>
                      <Input
                        type="number"
                        min={1}
                        max={365}
                        className="max-w-40"
                        {...field}
                      />
                    </FormControl>
                    <FormDescription>
                      {__(
                        "Minimum days before the same overdue page can appear in another digest. Currently tracked per page, not per recipient — see README.",
                        "content-ownership",
                      )}
                    </FormDescription>
                    <FormMessage />
                  </FormItem>
                )}
              />
            ) : null}
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>{__("Mark as reviewed", "content-ownership")}</CardTitle>
            <CardDescription>
              {__(
                "What happens when someone marks a page as reviewed. Plugin review meta is always stored.",
                "content-ownership",
              )}
            </CardDescription>
          </CardHeader>
          <CardContent>
            <FormField
              control={form.control}
              name="sync_wp_modified_on_review"
              render={({ field }) => (
                <FormItem className="flex flex-col gap-2">
                  <FormLabel>{__("Update WordPress last modified date", "content-ownership")}</FormLabel>
                  <FormControl>
                    <Switch
                      checked={field.value}
                      onCheckedChange={field.onChange}
                    />
                  </FormControl>
                  <FormDescription>
                    {__(
                      'Also updates the post\'s modified timestamp (shown as "updated" on the front-end). Does not create a new revision.',
                      "content-ownership",
                    )}
                  </FormDescription>
                  <FormMessage />
                </FormItem>
              )}
            />
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>{__("Default recipients", "content-ownership")}</CardTitle>
            <CardDescription>
              {__(
                "Recipients used as a fallback when no per-page or inherited recipient is set. Email addresses only here for now; per-page rules support users and roles too.",
                "content-ownership",
              )}
            </CardDescription>
          </CardHeader>
          <CardContent>
            <FormField
              control={form.control}
              name="default_recipient_emails_text"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>{__("Default recipient emails", "content-ownership")}</FormLabel>
                  <FormControl>
                    <Textarea
                      rows={3}
                      className="min-h-28"
                      placeholder={__("alerts@example.com, ops@example.com", "content-ownership")}
                      {...field}
                    />
                  </FormControl>
                  <FormDescription>
                    {__("Separate multiple addresses with commas or whitespace.", "content-ownership")}
                  </FormDescription>
                  <FormMessage />
                </FormItem>
              )}
            />
          </CardContent>
        </Card>

        <Separator />

        <div className="flex justify-end gap-2">
          <Button
            type="button"
            variant="outline"
            disabled={!form.formState.isDirty || updateM.isPending}
            onClick={resetToServer}
          >
            {__("Reset", "content-ownership")}
          </Button>
          <Button
            type="submit"
            disabled={!form.formState.isDirty || updateM.isPending}
          >
            <Save className="mr-2 h-4 w-4" />
            {updateM.isPending ? __("Saving…", "content-ownership") : __("Save changes", "content-ownership")}
          </Button>
        </div>
      </form>
    </Form>
  );
}
