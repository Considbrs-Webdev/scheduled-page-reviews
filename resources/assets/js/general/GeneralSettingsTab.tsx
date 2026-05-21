import { useCallback, useEffect } from "react";
import { useForm, type Resolver } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { toast } from "sonner";
import { Save } from "lucide-react";

import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Form, FormControl, FormDescription, FormField, FormItem, FormLabel, FormMessage } from "@/components/ui/form";
import { Input } from "@/components/ui/input";
import { Switch } from "@/components/ui/switch";
import { Textarea } from "@/components/ui/textarea";
import { Separator } from "@/components/ui/separator";

import { useGlobalSettings, useUpdateGlobalSettings } from "@/api/queries";
import { isEmailTarget, type RecipientTarget } from "@/types";
import { useUiStore } from "@/store/ui";

import { generalSettingsSchema, type GeneralSettingsFormValues } from "./schema";

export function GeneralSettingsTab() {
  const settingsQ = useGlobalSettings();
  const updateM   = useUpdateGlobalSettings({
    onSuccess: () => toast.success("Global settings saved."),
    onError: (e) => toast.error(e instanceof Error ? e.message : "Could not save settings."),
  });
  const setUnsaved = useUiStore((s) => s.setHasUnsavedChanges);

  const form = useForm<GeneralSettingsFormValues>({
    resolver: zodResolver(generalSettingsSchema) as Resolver<GeneralSettingsFormValues>,
    defaultValues: {
      default_interval_days: 180,
      notify_days_before: 14,
      send_reminder_after_due: true,
      reminder_cadence_days: 7,
      cron_batch_size: 200,
      default_recipient_emails_text: "",
    },
  });

  const resetToServer = useCallback(() => {
    if (!settingsQ.data) return;
    const emails = settingsQ.data.default_recipients
      .filter(isEmailTarget)
      .map((t) => t.value);
    form.reset({
      default_interval_days:   settingsQ.data.default_interval_days,
      notify_days_before:      settingsQ.data.notify_days_before,
      send_reminder_after_due: settingsQ.data.send_reminder_after_due,
      reminder_cadence_days:   settingsQ.data.reminder_cadence_days,
      cron_batch_size:         settingsQ.data.cron_batch_size,
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
    const existingNonEmail: RecipientTarget[] = (settingsQ.data?.default_recipients ?? [])
      .filter((t) => !isEmailTarget(t));

    const default_recipients: RecipientTarget[] = [
      ...existingNonEmail,
      ...emails.map((value: string) => ({ type: "email" as const, value })),
    ];

    updateM.mutate(
      {
        default_interval_days:   values.default_interval_days,
        notify_days_before:      values.notify_days_before,
        send_reminder_after_due: values.send_reminder_after_due,
        reminder_cadence_days:   values.reminder_cadence_days,
        cron_batch_size:         values.cron_batch_size,
        default_recipients,
      },
      { onSuccess: () => form.reset(values) }
    );
  });

  if (settingsQ.isLoading) {
    return <div className="text-sm text-muted-foreground">Loading settings…</div>;
  }
  if (settingsQ.error) {
    return <div className="text-sm text-destructive">Failed to load settings: {settingsQ.error.message}</div>;
  }

  return (
    <Form {...form}>
      <form onSubmit={onSubmit} className="grid gap-6">
        <Card>
          <CardHeader>
            <CardTitle>Review intervals</CardTitle>
            <CardDescription>How often pages must be reviewed and when reminders go out.</CardDescription>
          </CardHeader>
          <CardContent className="grid gap-4 sm:grid-cols-2">
            <FormField control={form.control} name="default_interval_days" render={({ field }) => (
              <FormItem>
                <FormLabel>Default review interval (days)</FormLabel>
                <FormControl><Input type="number" min={1} max={3650} {...field} /></FormControl>
                <FormDescription>Used when a page has no rule of its own.</FormDescription>
                <FormMessage />
              </FormItem>
            )} />
            <FormField control={form.control} name="notify_days_before" render={({ field }) => (
              <FormItem>
                <FormLabel>Notify days before</FormLabel>
                <FormControl><Input type="number" min={0} max={365} {...field} /></FormControl>
                <FormDescription>Window in which a page is "due soon".</FormDescription>
                <FormMessage />
              </FormItem>
            )} />
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Reminders</CardTitle>
            <CardDescription>Repeat reminders after a page is overdue.</CardDescription>
          </CardHeader>
          <CardContent className="grid gap-4 sm:grid-cols-2">
            <FormField control={form.control} name="send_reminder_after_due" render={({ field }) => (
              <FormItem className="flex flex-col gap-2">
                <FormLabel>Send reminders after due date</FormLabel>
                <FormControl>
                  <Switch checked={field.value} onCheckedChange={field.onChange} />
                </FormControl>
                <FormMessage />
              </FormItem>
            )} />
            <FormField control={form.control} name="reminder_cadence_days" render={({ field }) => (
              <FormItem>
                <FormLabel>Reminder cadence (days)</FormLabel>
                <FormControl><Input type="number" min={1} max={365} {...field} /></FormControl>
                <FormDescription>Wait at least this many days between reminders to the same recipient.</FormDescription>
                <FormMessage />
              </FormItem>
            )} />
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Default recipients</CardTitle>
            <CardDescription>
              Recipients used as a fallback when no per-page or inherited recipient is set. Email
              addresses only here for now; per-page rules support users and roles too.
            </CardDescription>
          </CardHeader>
          <CardContent>
            <FormField control={form.control} name="default_recipient_emails_text" render={({ field }) => (
              <FormItem>
                <FormLabel>Default recipient emails</FormLabel>
                <FormControl>
                  <Textarea rows={3} placeholder="alerts@example.com, ops@example.com" {...field} />
                </FormControl>
                <FormDescription>Separate multiple addresses with commas or whitespace.</FormDescription>
                <FormMessage />
              </FormItem>
            )} />
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Performance</CardTitle>
            <CardDescription>Tune how aggressively cron scans the site.</CardDescription>
          </CardHeader>
          <CardContent>
            <FormField control={form.control} name="cron_batch_size" render={({ field }) => (
              <FormItem>
                <FormLabel>Cron batch size</FormLabel>
                <FormControl><Input type="number" min={1} max={2000} {...field} /></FormControl>
                <FormDescription>Pages processed per cron tick. Larger values finish full scans faster but use more memory.</FormDescription>
                <FormMessage />
              </FormItem>
            )} />
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
          <Button type="submit" disabled={!form.formState.isDirty || updateM.isPending}>
            <Save className="mr-2 h-4 w-4" />
            {updateM.isPending ? "Saving…" : "Save changes"}
          </Button>
        </div>
      </form>
    </Form>
  );
}
