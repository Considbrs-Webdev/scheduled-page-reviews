import { useEffect } from "react";

import { syncStoreFromUrl } from "@/lib/navigation";

/**
 * Keeps browser history and UI state aligned for tab/page deep links.
 */
export function useUrlStateSync(): void {
  useEffect(() => {
    const onPopState = () => {
      syncStoreFromUrl({ confirmUnsaved: true });
    };

    window.addEventListener("popstate", onPopState);
    return () => window.removeEventListener("popstate", onPopState);
  }, []);
}
