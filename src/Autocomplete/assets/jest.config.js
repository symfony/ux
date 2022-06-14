const config = require('../../../jest.config.js');

config.setupFilesAfterEnv.push('./test/setup.js');

module.exports = config;
