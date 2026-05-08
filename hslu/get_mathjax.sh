#!/bin/bash

set -euo pipefail

mkdir public/mathjax
cd public/mathjax

# 3.2.2 is the last v3 release, couldn't get it work with v4 yet
version=3.2.2
# Download MathJax tarball from GitHub releases and,
# Extract the MathJax-$version/es5 folder into the current directory
curl -sSL -o - https://github.com/mathjax/MathJax/archive/refs/tags/$version.tar.gz | \
    tar xzf - --strip-components=2 MathJax-$version/es5

# The URL to use in ILIAS config with this script:
# https://elearning.hslu.ch/ilias/mathjax/tex-mml-chtml.js
