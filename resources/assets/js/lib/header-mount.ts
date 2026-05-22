export const HEADER_INTERACTIVE_MOUNT_ID = "co-header-interactive";

export function getHeaderInteractiveMount(): HTMLElement | null {
  return document.getElementById(HEADER_INTERACTIVE_MOUNT_ID);
}
