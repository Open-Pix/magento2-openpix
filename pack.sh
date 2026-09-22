#!/usr/bin/env bash
VERSION=$(jq '.version' package.json -r)

zip -r woovi_pix.${VERSION}.zip ./Pix/*
