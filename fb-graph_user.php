#!/usr/bin/env php
<?php
//  graph_user.php
//  SocialMediaTracker
//
//  Created by Hendrik Lange on 09.12.16.
//  Copyright © 2016 SmartLogics Software & Consulting GmbH. All rights reserved.
    require_once __DIR__ . '/vendor/autoload.php';

        $target = __DIR__ . '/download';
        $fb = new Facebook\Facebook([
                                'app_id' => '1198793806879837',
                                'app_secret' => 'cbff68ff87ee790b807ceb104c973d0c',
                                'default_graph_version' => 'v2.8',
                                ]);
    
        $helper = $fb->getCanvasHelper();
        try {
            $accessToken = $helper->getAccessToken();
        } catch(Facebook\Exceptions\FacebookResponseException $e) {
            // When Graph returns an error
            echo 'Graph returned an error: ' . $e->getMessage();
            exit;
        } catch(Facebook\Exceptions\FacebookSDKException $e) {
            // When validation fails or other local issues
            echo 'Facebook SDK returned an error: ' . $e->getMessage();
            exit;
        }
        
        if (isset($accessToken)) {
            // Logged in.
            $fb->setDefaultAccessToken($accessToken->getValue());
        }
        
        try {
            // Returns a `Facebook\FacebookResponse` object
            $response = $fb->get('/168814226467089?fields=name,id');
        } catch(Facebook\Exceptions\FacebookResponseException $e) {
            echo 'Graph returned an error: ' . $e->getMessage();
            exit;
        } catch(Facebook\Exceptions\FacebookSDKException $e) {
            echo 'Facebook SDK returned an error: ' . $e->getMessage();
            exit;
        }
        
        $user = $response->getGraphUser();
        
        echo 'Name: ' . $user['name'];
        // OR
        // echo 'Name: ' . $user->getName();
        
        $dirTarget = $user['name'] ?: $target;
        $i = 0;
        //for p in pages:
        echo 'Downloading page no. ' . $i;
        $fileTarget = $this->combine((string) $user['name'] ?: $target, sprintf('content%i.json', $i));
        $outfile = fopen(sprintf('%s/content%i.json', $user->getName(), $i), 'w');
        //json->dump(p, outfile, indent = 4);
        $i += 1;
/*
        private function copyFile($from, $to, $tasks, $vars)
        {
            if (!is_file($from)) {
                throw new \RuntimeException('Invalid PEAR package. package.xml defines file that is not located inside tarball.');
            }
            
            $this->filesystem->ensureDirectoryExists(dirname($to));
            
            if (0 == count($tasks)) {
                $copied = copy($from, $to);
            } else {
                $content = file_get_contents($from);
                $replacements = array();
                foreach ($tasks as $task) {
                    $pattern = $task['from'];
                    $varName = $task['to'];
                    if (isset($vars[$varName])) {
                        if ($varName === 'php_bin' && false === strpos($to, '.bat')) {
                            $replacements[$pattern] = preg_replace('{\.bat$}', '', $vars[$varName]);
                        } else {
                            $replacements[$pattern] = $vars[$varName];
                        }
                    }
                }
                $content = strtr($content, $replacements);
                
                $copied = file_put_contents($to, $content);
            }
            
            if (false === $copied) {
                throw new \RuntimeException(sprintf('Failed to copy %s to %s', $from, $to));
            }
        }
        
        private function combine($left, $right)
        {
            return rtrim($left, '/') . '/' . ltrim($right, '/');
        }
    }
 */
?>
