import { exec } from 'child_process';

import { version } from '../package.json';

(() => {
  try {
    exec(`"bash ../pack.sh" ${version}`);
  } catch (e) {
    // eslint-disable-next-line
    console.log(
      `Error while generating Magento2 version ${version}. Error: `,
      e,
    );
  }
})();
