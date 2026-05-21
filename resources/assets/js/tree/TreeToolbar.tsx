import { useId } from "react";
import { Search, RefreshCw } from "lucide-react";
import { useQueryClient } from "@tanstack/react-query";

import { __, _n, sprintf } from "@wordpress/i18n";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { useUiStore } from "@/store/ui";

export function TreeToolbar({ pageCount }: { pageCount: number }) {
  const inputId = useId();
  const search = useUiStore((s) => s.treeSearch);
  const setSearch = useUiStore((s) => s.setTreeSearch);
  const qc = useQueryClient();
  return (
    <div className="border-b p-2">
      <div className="flex items-center gap-2">
        <div className="relative flex-1">
          <Search className="pointer-events-none absolute left-2 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-muted-foreground" />
          <Input
            id={inputId}
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            placeholder={__("Search pages…", "content-ownership")}
            className="h-8 pl-7"
          />
        </div>
        <Button
          type="button"
          variant="ghost"
          size="sm"
          className="h-8 w-8 p-0"
          onClick={() => qc.invalidateQueries({ queryKey: ["tree"] })}
          aria-label={__("Refresh tree", "content-ownership")}
        >
          <RefreshCw className="h-3.5 w-3.5" />
        </Button>
      </div>
      <div className="mt-1 px-1 text-xs text-muted-foreground">
        {sprintf(
          /* translators: %d: number of pages */
          _n("%d page", "%d pages", pageCount, "content-ownership"),
          pageCount,
        )}
      </div>
    </div>
  );
}
