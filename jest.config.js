const path = require('path');

module.exports = {
    testRegex: "test/.*\\.test.ts",
    testEnvironment: 'jsdom',
    setupFilesAfterEnv: [
        path.join(__dirname, 'test/setup.js'),
    ],
    transform: {
        '\\.(j|t)s$': ['babel-jest', { configFile: path.join(__dirname, './babel.config.js') }]
    },
}
