import { wp } from "./editor/wp";
import { SidebarPanel } from "./editor/SidebarPanel";

wp.plugins.registerPlugin("scheduled-page-reviews-editor", {
  render: () => wp.element.createElement(SidebarPanel),
});
