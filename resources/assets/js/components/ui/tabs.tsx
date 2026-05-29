import * as React from "react";
import { cva, type VariantProps } from "class-variance-authority";
import { Tabs as TabsPrimitive } from "radix-ui";

import { cn } from "@/lib/utils";

function Tabs({
  className,
  orientation = "horizontal",
  ...props
}: React.ComponentProps<typeof TabsPrimitive.Root>) {
  return (
    <TabsPrimitive.Root
      data-slot="tabs"
      data-orientation={orientation}
      orientation={orientation}
      className={cn(
        "group/tabs flex gap-2 data-[orientation=horizontal]:flex-col",
        className,
      )}
      {...props}
    />
  );
}

const tabsListVariants = cva(
  "group/tabs-list inline-flex w-fit items-center justify-center group-data-[orientation=horizontal]/tabs:h-10 group-data-[orientation=vertical]/tabs:h-fit group-data-[orientation=vertical]/tabs:flex-col data-[variant=line]:rounded-none",
  {
    variants: {
      variant: {
        default: "rounded-lg border border-border bg-background p-1",
        line: "relative gap-4 bg-transparent",
        animated: "relative rounded-lg border border-foreground/15 bg-background p-1 shadow-sm",
        header: "relative rounded-lg bg-black/20 p-1 ring-1 ring-white/10",
      },
    },
    defaultVariants: {
      variant: "default",
    },
  },
);

type IndicatorStyle = {
  left: number;
  width: number;
  opacity: number;
};

function useSlidingTabIndicator(
  listRef: React.RefObject<HTMLDivElement | null>,
  enabled: boolean,
) {
  const [indicator, setIndicator] = React.useState<IndicatorStyle>({
    left: 0,
    width: 0,
    opacity: 0,
  });

  React.useLayoutEffect(() => {
    if (!enabled) return;

    const list = listRef.current;
    if (!list) return;

    const update = () => {
      const active = list.querySelector<HTMLElement>(
        '[data-slot="tabs-trigger"][data-state="active"]',
      );
      if (!active) {
        setIndicator((current) =>
          current.opacity === 0 ? current : { ...current, opacity: 0 },
        );
        return;
      }

      setIndicator({
        left: active.offsetLeft,
        width: active.offsetWidth,
        opacity: 1,
      });
    };

    update();

    const mutationObserver = new MutationObserver(update);
    mutationObserver.observe(list, {
      attributes: true,
      subtree: true,
      attributeFilter: ["data-state"],
    });

    const resizeObserver = new ResizeObserver(update);
    resizeObserver.observe(list);
    for (const trigger of list.querySelectorAll('[data-slot="tabs-trigger"]')) {
      resizeObserver.observe(trigger);
    }

    window.addEventListener("resize", update);

    return () => {
      mutationObserver.disconnect();
      resizeObserver.disconnect();
      window.removeEventListener("resize", update);
    };
  }, [enabled, listRef]);

  return indicator;
}

function TabsList({
  className,
  variant = "default",
  animated = false,
  ...props
}: React.ComponentProps<typeof TabsPrimitive.List> &
  VariantProps<typeof tabsListVariants> & {
    animated?: boolean;
  }) {
  const listRef = React.useRef<HTMLDivElement>(null);
  const useSlidingIndicator =
    variant === "header" ||
    variant === "animated" ||
    (variant === "line" && animated);
  const indicator = useSlidingTabIndicator(listRef, useSlidingIndicator);

  return (
    <TabsPrimitive.List
      ref={listRef}
      data-slot="tabs-list"
      data-variant={variant}
      className={cn(tabsListVariants({ variant }), className)}
      {...props}
    >
      {useSlidingIndicator ? (
        <span
          aria-hidden
          data-slot="tabs-indicator"
          className={cn(
            "pointer-events-none absolute z-10 transition-[left,width,opacity] duration-300 ease-in-out",
            variant === "header"
              ? "top-[3px] bottom-[3px] rounded-md bg-white/25 shadow-sm ring-1 ring-white/20"
              : variant === "line"
                ? "bottom-0 h-0.5 bg-primary"
                : "top-1 bottom-1 rounded-md bg-primary shadow-sm",
          )}
          style={{
            left: indicator.left,
            width: indicator.width,
            opacity: indicator.opacity,
          }}
        />
      ) : null}
      {props.children}
    </TabsPrimitive.List>
  );
}

function TabsTrigger({
  className,
  ...props
}: React.ComponentProps<typeof TabsPrimitive.Trigger>) {
  return (
    <TabsPrimitive.Trigger
      data-slot="tabs-trigger"
      className={cn(
        "relative inline-flex h-full flex-1 items-center justify-center gap-1.5 rounded-md border border-transparent px-3 py-1 text-sm font-medium whitespace-nowrap text-foreground/60 transition-colors focus-visible:border-primary focus-visible:ring-2 focus-visible:ring-ring/30 focus-visible:outline-none disabled:pointer-events-none disabled:opacity-50 group-data-[orientation=vertical]/tabs:w-full group-data-[orientation=vertical]/tabs:justify-start [&_svg]:pointer-events-none [&_svg]:shrink-0 [&_svg:not([class*='size-'])]:size-4",
        "group-data-[variant=line]/tabs-list:relative group-data-[variant=line]/tabs-list:z-10 group-data-[variant=line]/tabs-list:flex-none group-data-[variant=line]/tabs-list:rounded-none group-data-[variant=line]/tabs-list:px-1 group-data-[variant=line]/tabs-list:pb-2.5 group-data-[variant=line]/tabs-list:text-muted-foreground group-data-[variant=line]/tabs-list:hover:text-foreground group-data-[variant=line]/tabs-list:data-[state=active]:bg-transparent group-data-[variant=line]/tabs-list:data-[state=active]:text-primary group-data-[variant=line]/tabs-list:data-[state=active]:shadow-none",
        "group-data-[variant=default]/tabs-list:data-[state=active]:bg-primary group-data-[variant=default]/tabs-list:data-[state=active]:text-primary-foreground group-data-[variant=default]/tabs-list:data-[state=active]:shadow-sm",
        "group-data-[variant=animated]/tabs-list:relative group-data-[variant=animated]/tabs-list:z-10 group-data-[variant=animated]/tabs-list:flex-none group-data-[variant=animated]/tabs-list:rounded-md group-data-[variant=animated]/tabs-list:px-4 group-data-[variant=animated]/tabs-list:text-foreground/60 group-data-[variant=animated]/tabs-list:hover:text-foreground group-data-[variant=animated]/tabs-list:data-[state=active]:bg-transparent group-data-[variant=animated]/tabs-list:data-[state=active]:text-primary-foreground group-data-[variant=animated]/tabs-list:data-[state=active]:shadow-none group-data-[variant=animated]/tabs-list:data-[state=inactive]:text-foreground/60",
        "group-data-[variant=header]/tabs-list:relative group-data-[variant=header]/tabs-list:z-10 group-data-[variant=header]/tabs-list:flex-none group-data-[variant=header]/tabs-list:px-4 group-data-[variant=header]/tabs-list:py-1.5 group-data-[variant=header]/tabs-list:text-white/60 group-data-[variant=header]/tabs-list:hover:text-white/80 group-data-[variant=header]/tabs-list:data-[state=active]:bg-transparent group-data-[variant=header]/tabs-list:data-[state=active]:text-white group-data-[variant=header]/tabs-list:data-[state=active]:shadow-none group-data-[variant=header]/tabs-list:data-[state=inactive]:text-white/60 group-data-[variant=header]/tabs-list:focus-visible:ring-white/30",
        "after:absolute after:bg-primary after:opacity-0 after:transition-opacity group-data-[orientation=horizontal]/tabs:after:inset-x-0 group-data-[orientation=horizontal]/tabs:after:bottom-[-5px] group-data-[orientation=horizontal]/tabs:after:h-0.5 group-data-[orientation=vertical]/tabs:after:inset-y-0 group-data-[orientation=vertical]/tabs:after:-right-1 group-data-[orientation=vertical]/tabs:after:w-0.5 group-data-[variant=animated]/tabs-list:after:opacity-0 group-data-[variant=header]/tabs-list:after:opacity-0",
        className,
      )}
      {...props}
    />
  );
}

function TabsContent({
  className,
  ...props
}: React.ComponentProps<typeof TabsPrimitive.Content>) {
  return (
    <TabsPrimitive.Content
      data-slot="tabs-content"
      className={cn("flex-1 outline-none", className)}
      {...props}
    />
  );
}

export { Tabs, TabsList, TabsTrigger, TabsContent, tabsListVariants };
