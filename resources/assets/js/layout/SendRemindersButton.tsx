import { useState } from "react";
import { Mail } from "lucide-react";
import { toast } from "sonner";

import { __, sprintf } from "@wordpress/i18n";
import { Button } from "@/components/ui/button";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { useRunScanNow } from "@/api/queries";

interface SendRemindersButtonProps {
  inHeader?: boolean;
}

export function SendRemindersButton({ inHeader = false }: SendRemindersButtonProps) {
  const [open, setOpen] = useState(false);
  const m = useRunScanNow();

  const handleConfirm = () => {
    m.mutate(undefined, {
      onSuccess: (result) =>
        toast.success(
          sprintf(
            /* translators: 1: pages checked, 2: emails sent */
            __(
              "Reminders sent — %1$d pages checked, %2$d emails sent.",
              "content-ownership",
            ),
            result.processed,
            result.emails_sent,
          ),
        ),
      onError: (e) =>
        toast.error(
          e instanceof Error
            ? e.message
            : __("Failed to send reminders.", "content-ownership"),
        ),
      onSettled: () => setOpen(false),
    });
  };

  return (
    <>
      <Button
        type="button"
        variant={inHeader ? "headerAction" : "secondary"}
        size="sm"
        disabled={m.isPending}
        onClick={() => setOpen(true)}
      >
        <Mail className="mr-2 h-3.5 w-3.5" />
        {m.isPending
          ? __("Sending reminders…", "content-ownership")
          : __("Send reminders", "content-ownership")}
      </Button>

      <Dialog
        open={open}
        onOpenChange={(next) => {
          if (!m.isPending) {
            setOpen(next);
          }
        }}
      >
        <DialogContent>
          <DialogHeader>
            <DialogTitle>
              {__("Send review reminders now?", "content-ownership")}
            </DialogTitle>
            <DialogDescription>
              {__(
                "This checks all pages against your review rules and sends digest emails to owners who have pages that are due or overdue. Sent emails cannot be undone. On large sites this may take a while.",
                "content-ownership",
              )}
            </DialogDescription>
          </DialogHeader>
          <DialogFooter>
            <Button
              type="button"
              variant="outline"
              disabled={m.isPending}
              onClick={() => setOpen(false)}
            >
              {__("Cancel", "content-ownership")}
            </Button>
            <Button type="button" disabled={m.isPending} onClick={handleConfirm}>
              {m.isPending
                ? __("Sending reminders…", "content-ownership")
                : __("Send reminders", "content-ownership")}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </>
  );
}
