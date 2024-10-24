// vitest.config.ts
import { defineConfig, mergeConfig } from 'vitest/config';
import { svelte } from '@sveltejs/vite-plugin-svelte';
import configShared from '../../../vitest.config.mjs'

export default defineConfig(configEnv => mergeConfig(
	configShared,
	defineConfig({
		plugins: [svelte()],
		resolve: {
			conditions: configEnv.mode === 'test' ? ['browser'] : [],
		},
	})
))
