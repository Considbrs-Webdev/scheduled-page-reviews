import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { createElement, type PropsWithChildren } from "react";

let instance: QueryClient | null = null;

export function getQueryClient(): QueryClient {
  if (instance) return instance;
  instance = new QueryClient({
    defaultOptions: {
      queries: {
        staleTime: 5_000,
        refetchOnWindowFocus: false,
        retry: 1,
      },
      mutations: { retry: 0 },
    },
  });
  return instance;
}

export function QueryProvider({ children }: PropsWithChildren) {
  return createElement(
    QueryClientProvider,
    { client: getQueryClient() },
    children,
  );
}
