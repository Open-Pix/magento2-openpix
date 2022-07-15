import path from 'path';

import dotenvSafe from 'dotenv-safe';

const cwd = process.cwd();

const root = path.join.bind(cwd);

dotenvSafe.config({
  path: root('.env'),
  sample: root('.env.example'),
});

(async () => {
  console.log(process.env);
  console.log({
    e: process.env.PEM,
  });

  // await exec(
  //   `sudo scp -i ./pem/LightsailDefaultKey-us-east-1.pem ../../openpix_pix.2.0.5.zip bitnami@magento2.woovi.dev:/opt/bitnami/magento/app/code/OpenPix/ `,
  // );
  // @todo copy the new version released to magento2 bitnami
})();
