const { defaults } = require('jest-config');

module.exports = {
  testPathIgnorePatterns: ['/node_modules/', './dist'],
  transformIgnorePatterns: ['node_modules/(?!d3-random)'],
  moduleFileExtensions: [...defaults.moduleFileExtensions, 'ts', 'tsx'],
  transform: {
    '^.+\\.(js|ts|tsx)?$': '<rootDir>/test/babelJestUpward',
  },
};
