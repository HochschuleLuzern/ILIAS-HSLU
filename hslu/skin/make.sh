#!/bin/bash

set -euo pipefail

cd "$(dirname "$0")"

skindir=../../public/Customizing/skin/hslu
styledir=$skindir/hslu

if [[ -e $skindir ]]; then
    echo "Error: $skindir already exists. Please remove it first."
    exit 1
fi

mkdir -p $skindir
cp template.xml $skindir
mkdir $styledir

#####################################################################
### Compile CSS

# Download and unpack the latest version of Dart Sass
sass_version=1.99.0
curl -sSL -o dart-sass.tar.gz https://github.com/sass/dart-sass/releases/download/$sass_version/dart-sass-$sass_version-linux-x64.tar.gz
tar xzf dart-sass.tar.gz

# Compile the SCSS file to CSS
./dart-sass/sass hslu.scss $styledir/hslu.css

#####################################################################
### Copy the fonts

mkdir $skindir/fonts
cp -R ../../components/ILIAS/UI/resources/fonts/* $skindir/fonts
cp -R fonts/* $skindir/fonts

#####################################################################
### Process and copy the images

# Copy the images from Delos
cp -R ../../components/ILIAS/UI/resources/images $styledir

# Change dark blue fills to black and gray
find $styledir/images -type f -name '*.svg' -exec sed -i 's/fill:\s*#4c6586;/fill:#000000;/gi; s/fill:\s*#52658b;/fill:#000000;/gi; s/fill:\s*#58698e;/fill:#000000;/gi; s/fill:\s*#52668c;/fill:#303030;/gi' '{}' +

# Copy over our images
cp -R images/* $styledir/images

# For some reason images are accessed both under
# Customizing/skin/hslu/hslu/images (normal, inside the style dir) and
# Customizing/skin/hslu/images (weird, under the main skin dir).
# So, add a symlink:
ln -s hslu/images $skindir/images

#####################################################################
### Cleanup

rm -rf dart-sass.tar.gz dart-sass
