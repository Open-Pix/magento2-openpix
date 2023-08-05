import path from 'path';
import dotenvSafe from 'dotenv-safe';

const root = path.join.bind(null, __dirname, '../');

dotenvSafe.config({
  path: root('.env'),
  sample: root('.env.example'),
});

export const config = {
  GITHUB_TOKEN: process.env.GITHUB_TOKEN,
};