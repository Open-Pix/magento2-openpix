#!/usr/bin/env node
import git from 'simple-git';
import { Octokit } from '@octokit/rest';
import changelog from 'generate-changelog';
import fs from 'fs/promises';
import path from 'path';

import { config } from './config';

const root = path.join.bind(null, __dirname, '../');

// Use current version of the package by default

const version = process.env.CIRCLE_TAG || require('../package.json').version;
const tagVersion = `v${version}`;

const owner = process.env.CIRCLE_PROJECT_USERNAME || 'Open-Pix';
const repo = process.env.CIRCLE_PROJECT_REPONAME || 'magento2-openpix';

(async () => {
  try {
    const octokit = new Octokit({ auth: config.GITHUB_TOKEN });

    const resultTag = await git().tags();
    const currentTag = resultTag.all[resultTag.all.length - 2];

    const diffPattern = `${currentTag}..main`;

    const changelogContent = await changelog.generate({ tag: diffPattern });

    const body = changelogContent.replace(/^#### (.*)\n/gm, '');

    const release = await octokit.repos.createRelease({
      owner,
      repo,
      tag_name: tagVersion,
      name: tagVersion,
      body,
    });

    const data = await fs.readFile(root(`openpix_pix_pix.${version}.zip`), 'utf-8');

    await octokit.repos.uploadReleaseAsset({
      owner,
      repo,
      release_id: release.data.id,
      data,
      name: `openpix_pix_pix.${version}.zip`,
      mediaType: {
        format: 'application/zip',
      },
    });
  } catch (err) {
    // eslint-disable-next-line
    console.log('err: ', err);
  }
})();