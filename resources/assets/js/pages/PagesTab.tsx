import { Tree } from "@/tree/Tree";
import { PageDetail } from "@/editor/PageDetail";
import { useUiStore } from "@/store/ui";

export function PagesTab() {
  const pageId = useUiStore((s) => s.selectedPageId);
  return (
    <div className="grid h-full min-h-0 flex-1 grid-cols-[340px_1fr] grid-rows-1 gap-4 overflow-hidden">
      <aside className="flex min-h-0 flex-col overflow-hidden rounded-lg border bg-card">
        <Tree />
      </aside>
      <section className="flex min-h-0 flex-col overflow-hidden rounded-lg border bg-card">
        <PageDetail pageId={pageId} />
      </section>
    </div>
  );
}
