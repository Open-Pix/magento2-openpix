import { exec as execCb } from 'child_process';
import path from 'path';

import util from 'util';

// eslint-disable-next-line
const argv = require('minimist')(process.argv.slice(1));

const exec = util.promisify(execCb);

const root = path.join.bind(this, __dirname, '../');

(async () => {
  await exec(
    `sudo scp -i ./pem/LightsailDefaultKey-us-east-1.pem ../../openpix_pix.2.0.5.zip bitnami@magento2.woovi.dev:/opt/bitnami/magento/app/code/OpenPix/ `,
  );
  // @todo copy the new version relead to magento2 bitnami
})();
