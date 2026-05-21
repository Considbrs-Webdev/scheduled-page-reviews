import { Play } from "lucide-react";
import { toast } from "sonner";

import { __ } from "@wordpress/i18n";
import { Button } from "@/components/ui/button";
import { useRunCronNow } from "@/api/queries";

export function RunCronButton() {
  const m = useRunCronNow();
  return (
    <Button
      type="button"
      variant="secondary"
      size="sm"
      disabled={m.isPending}
      onClick={() =>
        m.mutate(undefined, {
          onSuccess: () => toast.success(__("Cron run queued.", "content-ownership")),
          onError: (e) =>
            toast.error(
              e instanceof Error ? e.message : __("Failed to start cron.", "content-ownership"),
            ),
        })
      }
    >
      <Play className="mr-2 h-3.5 w-3.5" />
      {__("Run cron now", "content-ownership")}
    </Button>
  );
}
