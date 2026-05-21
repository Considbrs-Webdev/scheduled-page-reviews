import "../css/tailwind.css";

import { StrictMode } from "react";
import { createRoot } from "react-dom/client";

import { TooltipProvider } from "@/components/ui/tooltip";
import { Toaster } from "@/components/ui/sonner";
import { QueryProvider } from "@/lib/query-client";

import { App } from "./App";

const mountNode = document.getElementById("content-ownership-root");

if (mountNode) {
  createRoot(mountNode).render(
    <StrictMode>
      <QueryProvider>
        <TooltipProvider delayDuration={150}>
          <App />
          <Toaster position="bottom-right" richColors closeButton />
        </TooltipProvider>
      </QueryProvider>
    </StrictMode>
  );
}
