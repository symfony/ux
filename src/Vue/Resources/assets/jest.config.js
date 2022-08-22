const { defaults } = require('jest-config');
const jestConfig = require('../../../../jest.config.js');

jestConfig.moduleFileExtensions = [...defaults.moduleFileExtensions, 'vue'];
jestConfig.transform['^.+\\.vue$'] = ['@vue/vue3-jest'];

module.exports = jestConfig;
