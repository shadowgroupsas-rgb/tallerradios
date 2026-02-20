#!/bin/bash
# create_release_zip.sh

# Remove existing zip
rm -f cruzroja_radio_deploy.zip

# Create temp directories for clear structure
mkdir -p temp_deploy/public_html
mkdir -p temp_deploy/server

# Copy PHP/MySQL files to public_html
# This creates a "flat" structure suitable for cPanel document root
cp -r public/* temp_deploy/public_html/
cp -r src/ temp_deploy/public_html/src
# Create config directory (ensure it exists)
mkdir -p temp_deploy/public_html/config
# Copy config files if any (usually db.php is generated, but we might want to preserve structure)
# If config/db.php exists, we skip it or make it .example?
# Let's just ensure the directory is there so the installer can write to it.
cp database.sql temp_deploy/public_html/
cp README.md temp_deploy/

# Copy Node.js files to server
cp -r server/* temp_deploy/server/

# Zip it up
cd temp_deploy
zip -r ../cruzroja_radio_deploy.zip ./*
cd ..

# Cleanup
rm -rf temp_deploy

echo "Deployment package 'cruzroja_radio_deploy.zip' created."
