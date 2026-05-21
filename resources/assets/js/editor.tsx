import { wp } from "./editor/wp";
import { SidebarPanel } from "./editor/SidebarPanel";

wp.plugins.registerPlugin("content-ownership-editor", {
  render: () => wp.element.createElement(SidebarPanel),
});
