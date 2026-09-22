#!/usr/bin/env bash
VERSION=$(jq '.version' package.json -r)

zip -r openpix_pix.${VERSION}.zip ./Pix/*
