import { Play } from "lucide-react";
import { toast } from "sonner";

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
          onSuccess: () => toast.success("Cron run queued."),
          onError: (e) => toast.error(e instanceof Error ? e.message : "Failed to start cron."),
        })
      }
    >
      <Play className="mr-2 h-3.5 w-3.5" />
      Run cron now
    </Button>
  );
}
