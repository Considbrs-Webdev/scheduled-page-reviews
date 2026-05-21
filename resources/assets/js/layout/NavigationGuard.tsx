import { useEffect } from "react";
import { useUiStore } from "@/store/ui";

export function NavigationGuard() {
  const hasUnsaved = useUiStore((s) => s.hasUnsavedChanges);
  useEffect(() => {
    if (!hasUnsaved) return;
    const handler = (e: BeforeUnloadEvent) => {
      e.preventDefault();
      e.returnValue = "";
    };
    window.addEventListener("beforeunload", handler);
    return () => window.removeEventListener("beforeunload", handler);
  }, [hasUnsaved]);
  return null;
}
