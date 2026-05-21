import { wp } from "./wp";
import { fetchRule, markReviewed, getEditorBoot, type RuleResponse } from "./api";

const { createElement: h, Fragment, useState, useEffect, useCallback } = wp.element;
const { PanelRow, Button, Spinner, Notice, Flex, FlexItem } = wp.components;
const { PluginDocumentSettingPanel } = wp.editor;
const { __, sprintf } = wp.i18n;

type ReactNode = import("react").ReactNode;

const STATUS_STYLES: Record<RuleResponse["bucket"], { background: string; color: string; padding: string; borderRadius: number }> = {
  overdue: { background: "#c0392b", color: "#fff", padding: "2px 8px", borderRadius: 3 },
  upcoming: { background: "#d97706", color: "#fff", padding: "2px 8px", borderRadius: 3 },
  none: { background: "#16a34a", color: "#fff", padding: "2px 8px", borderRadius: 3 },
};

function statusLabel(bucket: RuleResponse["bucket"]): string {
  switch (bucket) {
    case "overdue":
      return __("Overdue", "content-ownership");
    case "upcoming":
      return __("Upcoming", "content-ownership");
    case "none":
      return __("On track", "content-ownership");
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
    return __("Never", "content-ownership");
  }
  const d = new Date(iso);
  if (Number.isNaN(d.getTime())) {
    return iso;
  }
  return d.toLocaleDateString(locale);
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
  const [marking, setMarking] = useState(false);
  const [flash, setFlash] = useState<string | null>(null);

  const load = useCallback(async () => {
    if (postId == null || postType !== "page") return;
    setLoading(true);
    setError(null);
    try {
      const r = await fetchRule(postId);
      setData(r);
    } catch (e) {
      setError(e instanceof Error ? e.message : String(e));
    } finally {
      setLoading(false);
    }
  }, [postId, postType]);

  useEffect(() => {
    load();
  }, [load]);

  if (postType !== "page") return null;

  const onMark = async () => {
    if (postId == null) return;
    setMarking(true);
    setError(null);
    try {
      await markReviewed(postId);
      setFlash(__("Marked as reviewed.", "content-ownership"));
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
          __("Status", "content-ownership"),
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
          __("Next review", "content-ownership"),
          formatDate(data.next_review_at, boot.locale)
        )
      );
    }

    dataRows.push(
      infoRow(
        __("Last reviewed", "content-ownership"),
        formatDate(data.last_reviewed_at, boot.locale)
      )
    );

    if (data.effective?.interval_days?.value != null) {
      dataRows.push(
        infoRow(
          __("Review interval", "content-ownership"),
          sprintf(__("%d days", "content-ownership"), data.effective.interval_days.value)
        )
      );
    }

    const recipientCount = data.effective?.recipients?.value?.length ?? 0;
    dataRows.push(
      infoRow(
        __("Who to notify", "content-ownership"),
        recipientCount === 0
          ? __("Nobody configured", "content-ownership")
          : sprintf(__("%d recipient(s)", "content-ownership"), recipientCount)
      )
    );

    dataRows.push(
      h(PanelRow, null, h(Button, {
        variant: "primary",
        isBusy: marking,
        disabled: marking,
        onClick: onMark,
      }, __("Mark as reviewed", "content-ownership")))
    );

    dataRows.push(
      h(
        PanelRow,
        null,
        h(
          "a",
          { href: boot.settingsUrl, target: "_blank", rel: "noreferrer" },
          __("Open content ownership settings", "content-ownership")
        )
      )
    );
  }

  return h(
    PluginDocumentSettingPanel,
    {
      name: "content-ownership-panel",
      title: __("Content ownership", "content-ownership"),
      className: "content-ownership-editor-panel",
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
