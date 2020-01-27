<?php

namespace Drupal\access_control;

/**
 * Class auth - intended to be used for CF worker validation
 * @todo - change the name to something more descriptive
 */
class Auth {

    protected $key;

    public function __construct() {
        // @todo Get the shared key from config and set in CF worker
        /*
        * @example Cloudflare Worker (Javascript)
        *
        addEventListener('fetch', event => {
            event.respondWith(addHeader(event.request))
          })
          async function addHeader(request) {
            const sharedKey = "e96RTWcs7bqeqYmVETVKU9bSZFakzNH3SR2Mm3l0"
                req = new Request(request)
                req.headers.set('x-cf-key',sharedKey)
                return await fetch(req.url, req)
            } else {
                let random = Math.floor(Math.random() * 1000);
                let html = 'Not Allowed. Error code #' + random
                return new Response(html)
            }
          }
        */
        // SHA256 Shared Key Check
        $this->key = "e96RTWcs7bqeqYmVETVKU9bSZFakzNH3SR2Mm3l0";
        $hash = hash("sha256", $this->key);
        return print_r($hash);
    }

    public function process() {
        return print_r("test");
    }
}