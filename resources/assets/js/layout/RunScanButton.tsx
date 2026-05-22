import { Play } from "lucide-react";
import { toast } from "sonner";

import { __, sprintf } from "@wordpress/i18n";
import { Button } from "@/components/ui/button";
import { cn } from "@/lib/utils";
import { useRunScanNow } from "@/api/queries";

interface RunScanButtonProps {
  variant?: "default" | "header";
}

export function RunScanButton({ variant = "default" }: RunScanButtonProps) {
  const m = useRunScanNow();
  return (
    <Button
      type="button"
      variant={variant === "header" ? "outline" : "secondary"}
      size="sm"
      className={cn(
        variant === "header" &&
          "border-white/30 bg-white/10 text-white hover:bg-white/20 hover:text-white",
      )}
      disabled={m.isPending}
      onClick={() =>
        m.mutate(undefined, {
          onSuccess: (result) =>
            toast.success(
              sprintf(
                /* translators: 1: pages processed, 2: emails sent */
                __(
                  "Scan complete — %1$d pages processed, %2$d emails sent.",
                  "content-ownership",
                ),
                result.processed,
                result.emails_sent,
              ),
            ),
          onError: (e) =>
            toast.error(
              e instanceof Error ? e.message : __("Failed to run scan.", "content-ownership"),
            ),
        })
      }
    >
      <Play className="mr-2 h-3.5 w-3.5" />
      {m.isPending ? __("Scanning…", "content-ownership") : __("Run scan now", "content-ownership")}
    </Button>
  );
}
