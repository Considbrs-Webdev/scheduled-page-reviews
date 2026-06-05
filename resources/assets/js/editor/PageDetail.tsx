import { useEffect, useState } from "react";
import { useForm, FormProvider } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { CalendarClock, Save, Users } from "lucide-react";
import { toast } from "sonner";

import { __ } from "@wordpress/i18n";
import { PageDetailSkeleton } from "@/components/ui/loading-skeletons";
import { Button } from "@/components/ui/button";
import { Separator } from "@/components/ui/separator";
import { SettingSection } from "@/components/ui/setting-row";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { usePageRule, useUpdatePageRule } from "@/api/queries";
import { useUiStore } from "@/store/ui";

import { PageHeader } from "./PageHeader";
import { IntervalField } from "./fields/IntervalField";
import { NotifyBeforeField } from "./fields/NotifyBeforeField";
import { RecipientsField } from "./fields/RecipientsField";
import {
  ruleFormSchema,
  ruleResponseToFormValues,
  formValuesToRule,
  type RuleFormValues,
} from "./schema";

type EditorSection = "recipients" | "schedule";

interface PageDetailProps {
  pageId: number | null;
}

export function PageDetail({ pageId }: PageDetailProps) {
  if (pageId == null) {
    return (
      <div className="flex h-full items-center justify-center p-6 text-sm text-muted-foreground">
        {__(
          "Select a page from the tree to view and edit its scheduled-page-reviews rule.",
          "scheduled-page-reviews",
        )}
      </div>
    );
  }
  return <PageDetailInner pageId={pageId} />;
}

function PageDetailInner({ pageId }: { pageId: number }) {
  const [section, setSection] = useState<EditorSection>("recipients");
  const q = usePageRule(pageId);
  const m = useUpdatePageRule(pageId);
  const setUnsaved = useUiStore((s) => s.setHasUnsavedChanges);

  const form = useForm<RuleFormValues>({
    resolver: zodResolver(ruleFormSchema),
    defaultValues: {
      interval: { state: "inherit", value: 180 },
      notify_before: { state: "inherit", value: 14 },
      recipients: { state: "inherit", value: [] },
    },
  });

  useEffect(() => {
    if (!q.data) return;
    form.reset(
      ruleResponseToFormValues(q.data.rule, {
        interval_days: q.data.effective.interval_days.value,
        notify_before: q.data.effective.notify_before.value,
        recipients: q.data.effective.recipients.value,
      }),
    );
  }, [q.data, form, pageId]);

  useEffect(() => {
    setUnsaved(form.formState.isDirty);
    return () => setUnsaved(false);
  }, [form.formState.isDirty, setUnsaved]);

  if (q.isLoading) {
    return <PageDetailSkeleton />;
  }
  if (q.error || !q.data) {
    return (
      <div className="p-6 text-sm text-destructive">
        {q.error?.message ?? __("Failed to load.", "scheduled-page-reviews")}
      </div>
    );
  }

  const data = q.data;
  const onSubmit = form.handleSubmit((values) => {
    m.mutate(formValuesToRule(values), {
      onSuccess: () => {
        toast.success(__("Saved.", "scheduled-page-reviews"));
        form.reset(values);
      },
      onError: (e) =>
        toast.error(e instanceof Error ? e.message : __("Failed to save.", "scheduled-page-reviews")),
    });
  });

  return (
    <FormProvider {...form}>
      <form onSubmit={onSubmit} className="flex h-full flex-col">
        <PageHeader
          pageId={pageId}
          title={data.title}
          editLink={data.edit_link}
          bucket={data.bucket}
          nextReviewAt={data.next_review_at}
          lastReviewedAt={data.last_reviewed_at}
        />

        <Tabs
          value={section}
          onValueChange={(value) => {
            if (value === "recipients" || value === "schedule") {
              setSection(value);
            }
          }}
          className="flex min-h-0 flex-1 flex-col"
        >
          <div className="px-4 pt-4">
            <TabsList variant="line" animated className="h-10 w-fit">
              <TabsTrigger value="recipients" className="gap-2 px-4">
                <Users aria-hidden />
                {__("Recipients", "scheduled-page-reviews")}
              </TabsTrigger>
              <TabsTrigger value="schedule" className="gap-2 px-4">
                <CalendarClock aria-hidden />
                {__("Schedule", "scheduled-page-reviews")}
              </TabsTrigger>
            </TabsList>
          </div>

          <TabsContent value="recipients" className="min-h-0 flex-1 overflow-auto p-4">
            <SettingSection title={__("Notification recipients", "scheduled-page-reviews")}>
              <RecipientsField effective={data.effective} />
            </SettingSection>
          </TabsContent>

          <TabsContent value="schedule" className="min-h-0 flex-1 overflow-auto p-4">
            <SettingSection
              title={__("Review schedule", "scheduled-page-reviews")}
              description={__(
                "Interval and reminder timing for this page.",
                "scheduled-page-reviews",
              )}
            >
              <IntervalField effective={data.effective} />
              <NotifyBeforeField effective={data.effective} />
            </SettingSection>
          </TabsContent>
        </Tabs>

        <Separator />
        <div className="flex items-center justify-end gap-2 p-4">
          <Button
            type="button"
            variant="outline"
            disabled={!form.formState.isDirty || m.isPending}
            onClick={() => form.reset()}
          >
            {__("Reset", "scheduled-page-reviews")}
          </Button>
          <Button type="submit" disabled={!form.formState.isDirty || m.isPending}>
            <Save className="mr-2 h-4 w-4" />
            {m.isPending ? __("Saving…", "scheduled-page-reviews") : __("Save changes", "scheduled-page-reviews")}
          </Button>
        </div>
      </form>
    </FormProvider>
  );
}
