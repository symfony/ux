const { defaults } = require('jest-config');
const jestConfig = require('../../../../jest.config.js');

jestConfig.moduleFileExtensions = [...defaults.moduleFileExtensions, 'svelte'];
jestConfig.transform['^.+\\.svelte$'] = ['svelte-jester'];

module.exports = jestConfig;
