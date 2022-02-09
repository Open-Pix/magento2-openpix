import { exec as execCb } from 'child_process';
import util from 'util';
const exec = util.promisify(execCb);

// @todo mock the exec to make this test works
it.skip('should change module.xml version and composer.json using sed', async () => {
  const latestVersion = '1.0.22';
  const newVersion = '1.0.23';

  await exec(
    `sed -i 's/${latestVersion}/${newVersion}/g' ../fixtures/plugin/Pix/etc/module.xml`,
  );
  await exec(
    `sed -i 's/${latestVersion}/${newVersion}/g' ../fixtures/plugin/Pix/composer.json`,
  );
  await exec(
    `sed -i 's/${latestVersion}/${newVersion}/g' ../fixtures/plugin/Pix/package.json`,
  );
});
