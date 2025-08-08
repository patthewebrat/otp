import { defineConfig } from 'vitest/config'

export default defineConfig({
  test: {
    environment: 'node',
    setupFiles: ['resources/js/tests/setup.ts'],
    globals: true,
    include: ['resources/js/tests/**/*.spec.*'],
  },
})

