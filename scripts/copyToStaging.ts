import { exec as execCb } from 'child_process';
import path from 'path';

import util from 'util';

// eslint-disable-next-line
const argv = require('minimist')(process.argv.slice(1));

const exec = util.promisify(execCb);

const root = path.join.bind(this, __dirname, '../');

(async () => {
  // @todo copy the new version relead to magento2 bitnami
})();
