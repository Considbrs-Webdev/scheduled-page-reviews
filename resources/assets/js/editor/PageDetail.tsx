import { useEffect } from "react";
import { useForm, FormProvider } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { Save } from "lucide-react";
import { toast } from "sonner";

import { Button } from "@/components/ui/button";
import { Separator } from "@/components/ui/separator";
import { usePageRule, useUpdatePageRule } from "@/api/queries";
import { useUiStore } from "@/store/ui";

import { PageHeader } from "./PageHeader";
import { IntervalField } from "./fields/IntervalField";
import { NotifyBeforeField } from "./fields/NotifyBeforeField";
import { RecipientsField } from "./fields/RecipientsField";
import { ruleFormSchema, ruleResponseToFormValues, formValuesToRule, type RuleFormValues } from "./schema";

interface PageDetailProps {
  pageId: number | null;
}

export function PageDetail({ pageId }: PageDetailProps) {
  if (pageId == null) {
    return (
      <div className="flex h-full items-center justify-center p-6 text-sm text-muted-foreground">
        Select a page from the tree to view and edit its content-ownership rule.
      </div>
    );
  }
  return <PageDetailInner pageId={pageId} />;
}

function PageDetailInner({ pageId }: { pageId: number }) {
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
    form.reset(ruleResponseToFormValues(q.data.rule, {
      interval_days: q.data.effective.interval_days.value,
      notify_before: q.data.effective.notify_before.value,
      recipients: q.data.effective.recipients.value,
    }));
  }, [q.data, form, pageId]);

  useEffect(() => {
    setUnsaved(form.formState.isDirty);
    return () => setUnsaved(false);
  }, [form.formState.isDirty, setUnsaved]);

  if (q.isLoading) return <div className="p-6 text-sm text-muted-foreground">Loading page rule…</div>;
  if (q.error || !q.data) return <div className="p-6 text-sm text-destructive">{q.error?.message ?? "Failed to load."}</div>;

  const data = q.data;
  const onSubmit = form.handleSubmit((values) => {
    m.mutate(formValuesToRule(values), {
      onSuccess: () => {
        toast.success("Saved.");
        form.reset(values);
      },
      onError: (e) => toast.error(e instanceof Error ? e.message : "Failed to save."),
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
        <div className="grid flex-1 gap-4 overflow-auto p-4">
          <IntervalField effective={data.effective} />
          <NotifyBeforeField effective={data.effective} />
          <RecipientsField effective={data.effective} />
        </div>
        <Separator />
        <div className="flex items-center justify-end gap-2 p-4">
          <Button type="button" variant="outline" disabled={!form.formState.isDirty || m.isPending} onClick={() => form.reset()}>
            Reset
          </Button>
          <Button type="submit" disabled={!form.formState.isDirty || m.isPending}>
            <Save className="mr-2 h-4 w-4" />
            {m.isPending ? "Saving…" : "Save changes"}
          </Button>
        </div>
      </form>
    </FormProvider>
  );
}
