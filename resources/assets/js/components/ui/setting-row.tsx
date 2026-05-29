import * as React from "react";

import { cn } from "@/lib/utils";

function SettingSection({
  title,
  description,
  className,
  children,
}: {
  title: string;
  description?: string;
  className?: string;
  children: React.ReactNode;
}) {
  return (
    <section className={cn("space-y-0", className)}>
      <div className="pb-3">
        <h3 className="text-base font-semibold">{title}</h3>
        {description ? (
          <p className="mt-1 text-sm text-muted-foreground">{description}</p>
        ) : null}
      </div>
      <div className="divide-y divide-border border-t border-border">
        {children}
      </div>
    </section>
  );
}

function SettingRow({
  label,
  description,
  className,
  children,
}: {
  label: string;
  description?: string;
  className?: string;
  children: React.ReactNode;
}) {
  return (
    <div
      className={cn(
        "grid gap-3 py-4 sm:grid-cols-[minmax(180px,240px)_1fr] sm:items-start",
        className,
      )}
    >
      <div className="space-y-1">
        <div className="text-sm font-medium leading-none">{label}</div>
        {description ? (
          <p className="text-xs text-muted-foreground">{description}</p>
        ) : null}
      </div>
      <div className="min-w-0 space-y-2">{children}</div>
    </div>
  );
}

export { SettingSection, SettingRow };
