<?php
//
//  login.php
//  facebook.downloader
//
//  Created by Hendrik Lange on 28.11.16.
//  Copyright © 2016 SmartLogics Software & Consulting GmbH. All rights reserved.
//
require_once __DIR__ . '/vendor/autoload.php';

$fb = new Facebook\Facebook([
                            'app_id' => '1198793806879837',
                            'app_secret' => 'cbff68ff87ee790b807ceb104c973d0c',
                            'default_graph_version' => 'v2.8',
                            ]);

$helper = $fb->getRedirectLoginHelper();

$permissions = ['email']; // Optional permissions
$loginUrl = $helper->getLoginUrl('fb-callback.php', $permissions);

echo '<a href="' . htmlspecialchars($loginUrl) . '">Log in with Facebook!</a>';
?>
