const { defaults } = require('jest-config');
const pkg = require('./package');

module.exports = {
  displayName: pkg.name,
  setupFilesAfterEnv: ['<rootDir>/test/setupTestFramework.js'],
  testPathIgnorePatterns: ['/node_modules/', './dist'],
  transformIgnorePatterns: ['node_modules/(?!d3-random)'],
  moduleFileExtensions: [...defaults.moduleFileExtensions, 'ts', 'tsx'],
  resetModules: false,
  transform: {
    '^.+\\.(js|ts|tsx)?$': 'babel-jest',
  },
  testRegex: '(/__tests__/.*|(\\.|/)(test|spec))\\.(js|ts|tsx)?$',
  cacheDirectory: '.jest-cache',
};
