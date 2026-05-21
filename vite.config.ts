import { defineConfig } from "vite";
import { resolve } from "path";
import react from "@vitejs/plugin-react";
import tailwindcss from "@tailwindcss/vite";

export default defineConfig({
  build: {
    manifest: true,
    outDir: "dist",
    emptyOutDir: true,
    rollupOptions: {
      input: {
        admin: resolve(__dirname, "resources/assets/js/admin.tsx"),
        editor: resolve(__dirname, "resources/assets/js/editor.tsx"),
      },
      output: {
        entryFileNames: "js/[name].[hash].js",
        chunkFileNames: "js/[name].[hash].js",
        assetFileNames: ({ name }) => {
          if (name && /\.(css)$/.test(name)) {
            return "css/[name].[hash][extname]";
          }
          return "assets/[name].[hash][extname]";
        },
      },
    },
  },
  plugins: [react(), tailwindcss()],
  resolve: {
    extensions: [".mjs", ".js", ".ts", ".jsx", ".tsx", ".json"],
    alias: {
      "@": resolve(__dirname, "resources/assets/js"),
    },
  },
});
