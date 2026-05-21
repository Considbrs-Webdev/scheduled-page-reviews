import { Tree } from "@/tree/Tree";
import { PageDetail } from "@/editor/PageDetail";
import { useUiStore } from "@/store/ui";

export function PagesTab() {
  const pageId = useUiStore((s) => s.selectedPageId);
  return (
    <div className="grid h-[calc(100vh-220px)] grid-cols-[340px_1fr] gap-4 overflow-hidden">
      <aside className="flex h-full flex-col overflow-hidden rounded-lg border bg-card">
        <Tree />
      </aside>
      <section className="overflow-auto rounded-lg border bg-card">
        <PageDetail pageId={pageId} />
      </section>
    </div>
  );
}
