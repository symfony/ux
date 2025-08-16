import react from '@vitejs/plugin-react';
import { mergeConfig } from 'vitest/config';
import configShared from '../../../vitest.config.unit.mjs';

export default mergeConfig(configShared, {
    plugins: [react()],
});
