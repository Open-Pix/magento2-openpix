import fs from 'fs';
import { exec as execCb } from 'child_process';
import path from 'path';

import util from 'util';
import semver from 'semver';
import moment from 'moment';
// eslint-disable-next-line
import git from 'simple-git/promise';
// eslint-disable-next-line
import changelog from 'generate-changelog';
import dotenvSafe from 'dotenv-safe';
// eslint-disable-next-line
import { Octokit } from '@octokit/rest';

// eslint-disable-next-line
const argv = require('minimist')(process.argv.slice(1));

const exec = util.promisify(execCb);

const root = path.join.bind(this, __dirname, '../');

dotenvSafe.config({
  path: root('.env'),
  sample: root('.env.example'),
});

const owner = 'Open-Pix';
const repo = 'magento2-openpix';

const createPullRequest = async (branchName, tag) => {
  if (!process.env.GITHUB_TOKEN) {
    return;
  }

  const octokit = new Octokit({
    auth: process.env.GITHUB_TOKEN,
  });

  const now = moment().format('YYYY-MM-DD');

  // https://octokit.github.io/rest.js/#api-Repos-getReleases
  // https://developer.github.com/v3/repos/releases/#list-releases-for-a-repository
  const latestReleases = await octokit.repos.listReleases({
    owner,
    repo,
    per_page: 1,
  });
  const latestReleaseTag =
    latestReleases && latestReleases.data && latestReleases.data.length
      ? latestReleases.data[0].tag_name
      : 'master';

  await octokit.pulls.create({
    owner,
    repo,
    title: `Deploy Production - ${tag} - ${now}`,
    head: branchName,
    base: 'main',
    body: `https://github.com/${owner}/${repo}/compare/${latestReleaseTag}...master`,
  });
};

(async () => {
  try {
    const resultTag = await git().tags();
    const latestTag = resultTag.latest;

    const currentChangelog = fs.readFileSync('./CHANGELOG.md');

    const diffPattern = `${latestTag}..main`;

    const changelogContent = await changelog.generate({
      tag: diffPattern,
    });

    const rxVersion = /\d+\.\d+\.\d+/;
    const latestVersion =
      argv.version || changelogContent.match(rxVersion)?.[0];

    const getReleaseType = () => {
      if (argv.major) {
        return 'major';
      }

      if (argv.minor) {
        return 'minor';
      }

      return 'patch';
    };

    const newVersion = semver.inc(latestVersion, getReleaseType());

    const newChangelogContent =
      changelogContent.replace(rxVersion, newVersion) + currentChangelog;

    fs.writeFileSync('./CHANGELOG.md', newChangelogContent);

    const blankParamForMac = process.platform == 'darwin' ? "''" : '';

    await exec(
      `sed -i ${blankParamForMac} 's/${latestVersion}/${newVersion}/g' Pix/etc/module.xml`,
    );

    await exec(
      `sed -i ${blankParamForMac} 's/${latestVersion}/${newVersion}/g' Pix/composer.json`,
    );

    await exec(`npm version --no-git-tag-version ${newVersion}`);

    const tag = `v${newVersion}`;

    const today = new Date();

    const branchName = `feature-production/${today.getFullYear()}${
      today.getMonth() + 1
    }${today.getDate()}${today.getUTCHours()}${today.getUTCMinutes()}`;

    const releaseZipFileName = `openpix_pix.${newVersion}.zip`;

    await exec(`zip -r ${releaseZipFileName} ./Pix/*`);

    await git().checkout(['-B', branchName]);
    await git().add([
      'package.json',
      'CHANGELOG.md',
      'Pix/etc/module.xml',
      'Pix/composer.json',
      releaseZipFileName,
      '-f',
    ]);
    await git().commit(`build(change-log): ${tag}`, [], '-n');
    await git().addAnnotatedTag(`${tag}`, `build(tag): ${tag}`);
    await git().push(['--follow-tags', '-u', 'origin', branchName]);

    await createPullRequest(branchName, tag);
  } catch (err) {
    // eslint-disable-next-line
    console.log('err: ', err);
  }
})();
