#!/bin/bash

set -euo pipefail

mkdir mathjax
cd mathjax

# 2.7.9 is the last v2 release, which we are using for ILIAS 9
version=2.7.9
# Download MathJax tarball from GitHub releases and,
# Extract it into the current directory
curl -sSL -o - https://github.com/mathjax/MathJax/archive/refs/tags/$version.tar.gz | \
    tar xzf - --strip-components=1

# The URL to use in ILIAS config with this script:
# https://elearning.hslu.ch/ilias/mathjax/MathJax.js?config=TeX-AMS-MML_HTMLorMML
