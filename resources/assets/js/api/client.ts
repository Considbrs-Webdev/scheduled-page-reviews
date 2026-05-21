import { getBoot } from "@/lib/boot";

export class ApiError extends Error {
  constructor(
    public readonly status: number,
    message: string,
    public readonly body?: unknown,
  ) {
    super(message);
    this.name = "ApiError";
  }
}

export interface ApiRequestOptions {
  method?: "GET" | "POST" | "PUT" | "DELETE";
  /** Querystring params; non-string values are JSON-stringified. */
  query?: Record<
    string,
    string | number | boolean | undefined | null | (string | number)[]
  >;
  /** JSON body. */
  body?: unknown;
  /** AbortSignal for cancellable requests. */
  signal?: AbortSignal;
}

export async function apiRequest<T>(
  path: string,
  options: ApiRequestOptions = {},
): Promise<T> {
  const boot = getBoot();
  const base = boot.restRoot.endsWith("/")
    ? boot.restRoot.slice(0, -1)
    : boot.restRoot;
  const suffix = path.startsWith("/") ? path : `/${path}`;
  const url = new URL(`${base}${suffix}`);

  if (options.query) {
    for (const [k, v] of Object.entries(options.query)) {
      if (v == null) continue;
      if (Array.isArray(v)) {
        for (const item of v) url.searchParams.append(`${k}[]`, String(item));
      } else {
        url.searchParams.set(k, String(v));
      }
    }
  }

  const init: RequestInit = {
    method: options.method ?? "GET",
    headers: {
      "Content-Type": "application/json",
      "X-WP-Nonce": boot.nonce,
    },
    credentials: "same-origin",
    signal: options.signal,
  };
  if (options.body !== undefined) init.body = JSON.stringify(options.body);

  const res = await fetch(url.toString(), init);
  const text = await res.text();
  let parsed: unknown = null;
  if (text.length > 0) {
    try {
      parsed = JSON.parse(text);
    } catch {
      parsed = text;
    }
  }
  if (!res.ok) {
    const message = isErrorBody(parsed)
      ? parsed.message
      : `${res.status} ${res.statusText}`;
    throw new ApiError(res.status, message, parsed);
  }
  return parsed as T;
}

function isErrorBody(v: unknown): v is { message: string } {
  return (
    typeof v === "object" &&
    v !== null &&
    typeof (v as { message?: unknown }).message === "string"
  );
}
