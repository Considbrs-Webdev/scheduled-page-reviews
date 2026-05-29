import { Skeleton } from "@/components/ui/skeleton";
import { TREE_INDENT_PX } from "@/tree/IndentGuides";

export function TreeSkeleton({ rows = 10 }: { rows?: number }) {
  return (
    <div className="space-y-1 px-1">
      {Array.from({ length: rows }, (_, index) => {
        const depth = index % 4;
        return (
          <div
            key={index}
            className="flex h-[30px] items-center gap-2"
            style={{ paddingLeft: depth * TREE_INDENT_PX }}
          >
            <Skeleton className="h-4 w-4 shrink-0 rounded-sm" />
            <Skeleton className="h-3.5 flex-1 max-w-[180px]" />
          </div>
        );
      })}
    </div>
  );
}

export function SettingsSkeleton({ rows = 4 }: { rows?: number }) {
  return (
    <div className="space-y-8">
      {Array.from({ length: rows }, (_, sectionIndex) => (
        <div key={sectionIndex} className="space-y-4">
          <div className="space-y-2">
            <Skeleton className="h-5 w-40" />
            <Skeleton className="h-4 w-72 max-w-full" />
          </div>
          <div className="space-y-4 border-t border-border pt-4">
            {Array.from({ length: 2 }, (_, rowIndex) => (
              <div
                key={rowIndex}
                className="grid gap-3 sm:grid-cols-[minmax(180px,240px)_1fr]"
              >
                <div className="space-y-2">
                  <Skeleton className="h-4 w-36" />
                  <Skeleton className="h-3 w-48 max-w-full" />
                </div>
                <Skeleton className="h-9 w-40" />
              </div>
            ))}
          </div>
        </div>
      ))}
    </div>
  );
}

export function PageDetailSkeleton() {
  return (
    <div className="flex h-full flex-col">
      <div className="space-y-3 border-b p-4">
        <Skeleton className="h-3 w-20" />
        <Skeleton className="h-6 w-64 max-w-full" />
        <div className="flex gap-2">
          <Skeleton className="h-5 w-16 rounded-full" />
          <Skeleton className="h-4 w-48" />
        </div>
      </div>
      <div className="border-b px-4 pt-4">
        <div className="flex gap-4 border-b border-border pb-2.5">
          <Skeleton className="h-4 w-24" />
          <Skeleton className="h-4 w-20" />
        </div>
      </div>
      <div className="flex-1 space-y-4 p-4">
        <Skeleton className="h-5 w-44" />
        <Skeleton className="h-24 w-full max-w-xl rounded-lg" />
        <Skeleton className="h-9 w-full max-w-md rounded-lg" />
      </div>
      <div className="flex justify-end gap-2 border-t p-4">
        <Skeleton className="h-9 w-20" />
        <Skeleton className="h-9 w-32" />
      </div>
    </div>
  );
}
