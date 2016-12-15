<?php
  
  // Copyright 2004-2009 Facebook. All Rights Reserved.
  //
  // +---------------------------------------------------------------------------+
  // | Facebook Platform PHP5 client                                             |
  // +---------------------------------------------------------------------------+
  // | Copyright (c) 2007 Facebook, Inc.                                         |
  // | All rights reserved.                                                      |
  // |                                                                           |
  // | Redistribution and use in source and binary forms, with or without        |
  // | modification, are permitted provided that the following conditions        |
  // | are met:                                                                  |
  // |                                                                           |
  // | 1. Redistributions of source code must retain the above copyright         |
  // |    notice, this list of conditions and the following disclaimer.          |
  // | 2. Redistributions in binary form must reproduce the above copyright      |
  // |    notice, this list of conditions and the following disclaimer in the    |
  // |    documentation and/or other materials provided with the distribution.   |
  // |                                                                           |
  // | THIS SOFTWARE IS PROVIDED BY THE AUTHOR ``AS IS'' AND ANY EXPRESS OR      |
  // | IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES |
  // | OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.   |
  // | IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT,          |
  // | INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT  |
  // | NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, |
  // | DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY     |
  // | THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT       |
  // | (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF  |
  // | THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.         |
  // +---------------------------------------------------------------------------+
  // | For help with this library, contact developers-help@facebook.com          |
  // +---------------------------------------------------------------------------+
  //
  
  /**
   *  This class extends and modifies the "Facebook" class to better
   *  suit desktop apps.
   */
  class FacebookDevice {
    // the application secret, which differs from the session secret
    public $api_key;
    public $client_key;
    public $verify_sig;
    
    public function __construct($api_key, $client_key) {
      $this->api_key = $api_key;
      $this->client_key = $client_key;
      $this->verify_sig = false;
      $this->api_client = new FacebookRestClient($api_key, '', null);
      $this->api_client->server_addr = Facebook::get_facebook_url('graph').'/v2.6/device/login';
    }
    
    public function login() {
      var_dump($this->api_client->call_method('',
        array('access_token' => $this->api_key.'|'.$this->client_key,
              'scope' => 'public_profile,user_likes')));
    }
  
?>
