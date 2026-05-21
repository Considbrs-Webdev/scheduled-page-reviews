import { CheckCircle2 } from "lucide-react";

import { Button } from "@/components/ui/button";
import { getBoot } from "@/lib/boot";

export function App() {
  const boot = getBoot();

  return (
    <div className="content-ownership-shell p-6 max-w-3xl">
      <header className="mb-6">
        <h1 className="text-2xl font-semibold tracking-tight">Content Ownership</h1>
        <p className="text-sm text-muted-foreground mt-1">
          Plugin scaffold is wired up. The settings UI will live here.
        </p>
      </header>

      <section className="rounded-lg border border-border bg-card p-5 shadow-sm">
        <div className="flex items-start gap-3">
          <CheckCircle2 className="mt-0.5 text-primary" />
          <div className="flex-1">
            <h2 className="text-base font-medium">React, Tailwind and shadcn are mounted.</h2>
            <dl className="mt-3 grid grid-cols-2 gap-x-6 gap-y-1 text-sm">
              <dt className="text-muted-foreground">Plugin version</dt>
              <dd className="font-mono">{boot.pluginVersion}</dd>

              <dt className="text-muted-foreground">REST root</dt>
              <dd className="font-mono truncate">{boot.restRoot || "(not set)"}</dd>

              <dt className="text-muted-foreground">Current user</dt>
              <dd className="font-mono">#{boot.currentUserId}</dd>

              <dt className="text-muted-foreground">Locale</dt>
              <dd className="font-mono">{boot.locale}</dd>

              <dt className="text-muted-foreground">Can manage</dt>
              <dd className="font-mono">{boot.capabilities.manage ? "yes" : "no"}</dd>
            </dl>
          </div>
        </div>
        <div className="mt-5 flex gap-2">
          <Button variant="default">Primary</Button>
          <Button variant="secondary">Secondary</Button>
          <Button variant="outline">Outline</Button>
          <Button variant="ghost">Ghost</Button>
        </div>
      </section>
    </div>
  );
}
