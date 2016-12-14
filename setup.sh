#!/bin/sh

#  setup.sh
#  facebook.downloader
#
#  Created by Hendrik Lange on 28.11.16.
#  Copyright © 2016 SmartLogics Software & Consulting GmbH. All rights reserved.

/usr/bin/ruby -e "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/master/install)"

rm ~/.pearrc
brew update
brew install wget
brew tap homebrew/dupes
brew tap homebrew/versions
brew tap homebrew/homebrew-php
brew tap homebrew/php
brew install curl --with-libssh2 --with-openssl
brew uninstall php56
brew cleanup
xcode-select --install # An "install" window will appear. Click "yes" on all the things.
#brew install php56 --with-pear --with-homebrew-libressl --with-phpdbg --with-postgresql --with-homebrew-curl
brew install php56 --with-pear --with-phpdbg --with-postgresql --with-apache --with-homebrew-curl --with-homebrew-libxslt --with-homebrew-openssl --without-snmp
sudo ln -sfv /usr/local/opt/php56/*.plist /Library/LaunchDaemons
brew install php56-http

brew install php56-igbinary
brew install php56-mailparse
brew install php56-mcrypt

brew install php56-oauth
brew install php56-uploadprogress
brew install php56-tidy

brew install php56-opcache

chmod -R ug+w /usr/local/Cellar/php56/5.6.*/lib/php
pear config-set php_ini /usr/local/etc/php/5.6/php.ini
pecl config-set php_ini /usr/local/etc/php/5.6/php.ini
sudo pear config-set auto_discover 1
sudo pear update-channels
sudo pecl channel-update pecl.php.net
sudo pear upgrade-all

brew install composer
composer install

curl -fsSL https://raw.githubusercontent.com/dtompkins/fbcmd/master/fbcmd_update.php | php -- sudo
curl -fsSL https://raw.githubusercontent.com/dtompkins/fbcmd/master/fbcmd_update.php | php -- install
