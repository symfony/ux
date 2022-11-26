const config = require('../../../jest.config.js');

config.transformIgnorePatterns = [
    // delegate-it needs to be transformed
    'node_modules/(?!(delegate-it)/)'
];

module.exports = config;
