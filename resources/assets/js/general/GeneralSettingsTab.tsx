import { useCallback, useEffect } from "react";
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
    onSuccess: () => toast.success("Global settings saved."),
    onError: (e) =>
      toast.error(e instanceof Error ? e.message : "Could not save settings."),
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
      cron_batch_size: 200,
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
      cron_batch_size: settingsQ.data.cron_batch_size,
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
        cron_batch_size: values.cron_batch_size,
        sync_wp_modified_on_review: values.sync_wp_modified_on_review,
        default_recipients,
      },
      { onSuccess: () => form.reset(values) },
    );
  });

  if (settingsQ.isLoading) {
    return (
      <div className="text-sm text-muted-foreground">Loading settings…</div>
    );
  }
  if (settingsQ.error) {
    return (
      <div className="text-sm text-destructive">
        Failed to load settings: {settingsQ.error.message}
      </div>
    );
  }

  const sendReminderAfterDue = form.watch("send_reminder_after_due");

  return (
    <Form {...form}>
      <form onSubmit={onSubmit} className="grid gap-6">
        <Card>
          <CardHeader>
            <CardTitle>Review intervals</CardTitle>
            <CardDescription>
              How often pages must be reviewed and when reminders go out.
            </CardDescription>
          </CardHeader>
          <CardContent className="grid gap-4 sm:grid-cols-2">
            <FormField
              control={form.control}
              name="default_interval_days"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>Default review interval (days)</FormLabel>
                  <FormControl>
                    <Input type="number" min={1} max={3650} {...field} />
                  </FormControl>
                  <FormDescription>
                    Used when a page has no rule of its own.
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
                  <FormLabel>Notify days before</FormLabel>
                  <FormControl>
                    <Input type="number" min={0} max={365} {...field} />
                  </FormControl>
                  <FormDescription>
                    Window in which a page is "due soon".
                  </FormDescription>
                  <FormMessage />
                </FormItem>
              )}
            />
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Reminders</CardTitle>
            <CardDescription>
              Control repeat emails while a page stays due or overdue. Marking a
              page reviewed starts a new cycle.
            </CardDescription>
          </CardHeader>
          <CardContent className="grid gap-4 sm:grid-cols-2">
            <FormField
              control={form.control}
              name="send_reminder_after_due"
              render={({ field }) => (
                <FormItem className="flex flex-col gap-2">
                  <FormLabel>Send reminders after due date</FormLabel>
                  <FormControl>
                    <Switch
                      checked={field.value}
                      onCheckedChange={field.onChange}
                    />
                  </FormControl>
                  <FormDescription>
                    When off, each page is included in at most one digest per
                    review cycle (until marked reviewed). When on, overdue pages
                    can appear again after the cadence interval.
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
                    <FormLabel>Reminder cadence (days)</FormLabel>
                    <FormControl>
                      <Input type="number" min={1} max={365} {...field} />
                    </FormControl>
                    <FormDescription>
                      Minimum days before the same overdue page can appear in
                      another digest. Currently tracked per page, not per
                      recipient — see README.
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
            <CardTitle>Mark as reviewed</CardTitle>
            <CardDescription>
              What happens when someone marks a page as reviewed. Plugin review
              meta is always stored.
            </CardDescription>
          </CardHeader>
          <CardContent>
            <FormField
              control={form.control}
              name="sync_wp_modified_on_review"
              render={({ field }) => (
                <FormItem className="flex flex-col gap-2">
                  <FormLabel>Update WordPress last modified date</FormLabel>
                  <FormControl>
                    <Switch
                      checked={field.value}
                      onCheckedChange={field.onChange}
                    />
                  </FormControl>
                  <FormDescription>
                    Also updates the post&apos;s modified timestamp (shown as
                    &quot;updated&quot; on the front-end). Does not create a new
                    revision.
                  </FormDescription>
                  <FormMessage />
                </FormItem>
              )}
            />
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Default recipients</CardTitle>
            <CardDescription>
              Recipients used as a fallback when no per-page or inherited
              recipient is set. Email addresses only here for now; per-page
              rules support users and roles too.
            </CardDescription>
          </CardHeader>
          <CardContent>
            <FormField
              control={form.control}
              name="default_recipient_emails_text"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>Default recipient emails</FormLabel>
                  <FormControl>
                    <Textarea
                      rows={3}
                      placeholder="alerts@example.com, ops@example.com"
                      {...field}
                    />
                  </FormControl>
                  <FormDescription>
                    Separate multiple addresses with commas or whitespace.
                  </FormDescription>
                  <FormMessage />
                </FormItem>
              )}
            />
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Performance</CardTitle>
            <CardDescription>
              Tune how aggressively cron scans the site.
            </CardDescription>
          </CardHeader>
          <CardContent>
            <FormField
              control={form.control}
              name="cron_batch_size"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>Cron batch size</FormLabel>
                  <FormControl>
                    <Input type="number" min={1} max={2000} {...field} />
                  </FormControl>
                  <FormDescription>
                    Pages processed per cron tick. Larger values finish full
                    scans faster but use more memory.
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
            Reset
          </Button>
          <Button
            type="submit"
            disabled={!form.formState.isDirty || updateM.isPending}
          >
            <Save className="mr-2 h-4 w-4" />
            {updateM.isPending ? "Saving…" : "Save changes"}
          </Button>
        </div>
      </form>
    </Form>
  );
}
