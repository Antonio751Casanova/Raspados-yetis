// @ts-check
import { defineConfig } from 'astro/config';

import tailwindcss from '@tailwindcss/vite';

// https://astro.build/config
export default defineConfig({
  // AGREGA ESTO:
  vite: {
    plugins: [tailwindcss()],
    server: {
      allowedHosts: true
    }
  }
});