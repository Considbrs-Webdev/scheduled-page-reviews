import { __, sprintf } from "@wordpress/i18n";
import { wp } from "./wp";
import { fetchRule, markReviewed, getEditorBoot, type RuleResponse } from "./api";

const { createElement: h, Fragment, useState, useEffect, useCallback } = wp.element;
const { PanelRow, Button, Spinner, Notice, Flex, FlexItem } = wp.components;
const { PluginDocumentSettingPanel } = wp.editor;

type ReactNode = import("react").ReactNode;

const STATUS_STYLES: Record<RuleResponse["bucket"], { background: string; color: string; padding: string; borderRadius: number }> = {
  overdue: { background: "#c0392b", color: "#fff", padding: "2px 8px", borderRadius: 3 },
  upcoming: { background: "#d97706", color: "#fff", padding: "2px 8px", borderRadius: 3 },
  none: { background: "#16a34a", color: "#fff", padding: "2px 8px", borderRadius: 3 },
};

function statusLabel(bucket: RuleResponse["bucket"]): string {
  switch (bucket) {
    case "overdue":
      return __("Overdue", "scheduled-page-reviews");
    case "upcoming":
      return __("Upcoming", "scheduled-page-reviews");
    case "none":
      return __("On track", "scheduled-page-reviews");
  }
}

function infoRow(label: string, value: ReactNode) {
  return h(
    PanelRow,
    null,
    h(
      Flex,
      { justify: "space-between", align: "center", style: { width: "100%" } },
      h(FlexItem, null, label),
      h(FlexItem, null, value)
    )
  );
}

function formatDate(iso: string | null | undefined, locale: string): string {
  if (!iso) {
    return __("Never", "scheduled-page-reviews");
  }
  const d = new Date(iso);
  if (Number.isNaN(d.getTime())) {
    return iso;
  }
  return d.toLocaleDateString(locale);
}

function isRestForbidden(error: unknown): boolean {
  if (error && typeof error === "object") {
    const e = error as { code?: string; data?: { status?: number } };
    if (e.code === "rest_forbidden" || e.code === "rest_cannot_view") {
      return true;
    }
    if (e.data?.status === 403) {
      return true;
    }
  }
  return false;
}

export function SidebarPanel() {
  const boot = getEditorBoot();

  const postId = wp.data.useSelect<number | null>(
    (select) => select("core/editor")?.getCurrentPostId() ?? null,
    []
  );
  const postType = wp.data.useSelect<string | null>(
    (select) => select("core/editor")?.getCurrentPostType() ?? null,
    []
  );

  const [data, setData] = useState<RuleResponse | null>(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [forbidden, setForbidden] = useState(false);
  const [marking, setMarking] = useState(false);
  const [flash, setFlash] = useState<string | null>(null);

  const load = useCallback(async () => {
    if (postId == null || postType !== "page") return;
    setLoading(true);
    setError(null);
    setForbidden(false);
    try {
      const r = await fetchRule(postId);
      setData(r);
    } catch (e) {
      if (isRestForbidden(e)) {
        setForbidden(true);
        setData(null);
        return;
      }
      setError(e instanceof Error ? e.message : String(e));
    } finally {
      setLoading(false);
    }
  }, [postId, postType]);

  useEffect(() => {
    load();
  }, [load]);

  if (postType !== "page" || forbidden) return null;

  const onMark = async () => {
    if (postId == null) return;
    setMarking(true);
    setError(null);
    try {
      await markReviewed(postId);
      setFlash(__("Marked as reviewed.", "scheduled-page-reviews"));
      await load();
    } catch (e) {
      setError(e instanceof Error ? e.message : String(e));
    } finally {
      setMarking(false);
    }
  };

  const dataRows: ReactNode[] = [];

  if (data) {
    if (data.bucket) {
      dataRows.push(
        infoRow(
          __("Status", "scheduled-page-reviews"),
          h(
            "span",
            { style: STATUS_STYLES[data.bucket] ?? STATUS_STYLES.none },
            statusLabel(data.bucket)
          )
        )
      );
    }

    if (data.next_review_at) {
      dataRows.push(
        infoRow(
          __("Next review", "scheduled-page-reviews"),
          formatDate(data.next_review_at, boot.locale)
        )
      );
    }

    dataRows.push(
      infoRow(
        __("Last reviewed", "scheduled-page-reviews"),
        formatDate(data.last_reviewed_at, boot.locale)
      )
    );

    if (data.effective?.interval_days?.value != null) {
      dataRows.push(
        infoRow(
          __("Review interval", "scheduled-page-reviews"),
          sprintf(__("%d days", "scheduled-page-reviews"), data.effective.interval_days.value)
        )
      );
    }

    const recipientCount = data.effective?.recipients?.value?.length ?? 0;
    dataRows.push(
      infoRow(
        __("Who to notify", "scheduled-page-reviews"),
        recipientCount === 0
          ? __("Nobody configured", "scheduled-page-reviews")
          : sprintf(__("%d recipient(s)", "scheduled-page-reviews"), recipientCount)
      )
    );

    dataRows.push(
      h(PanelRow, null, h(Button, {
        variant: "primary",
        isBusy: marking,
        disabled: marking,
        onClick: onMark,
      }, __("Mark as reviewed", "scheduled-page-reviews")))
    );

    if (boot.canManageSettings) {
      dataRows.push(
        h(
          PanelRow,
          null,
          h(
            "a",
            { href: boot.settingsUrl, target: "_blank", rel: "noreferrer" },
            __("Open Scheduled Page Reviews settings", "scheduled-page-reviews")
          )
        )
      );
    }
  }

  return h(
    PluginDocumentSettingPanel,
    {
      name: "scheduled-page-reviews-panel",
      title: __("Scheduled Page Reviews", "scheduled-page-reviews"),
      className: "scheduled-page-reviews-editor-panel",
    },
    loading && h(PanelRow, null, h(Spinner, null)),
    error && h(Notice, { status: "error", isDismissible: false }, error),
    flash && h(Notice, {
      status: "success",
      isDismissible: true,
      onRemove: () => setFlash(null),
    }, flash),
    data && h(Fragment, null, ...dataRows)
  );
}
