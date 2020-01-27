<?php

namespace Drupal\access_control;

/**
 * Auth Class - this is incomplete.
 */
class Auth {

  /**
   * Key to be shared between app and CF.
   *
   * @var string
   */
  protected $key;

  /**
   * Constructor for Auth class.
   */
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
    // SHA256 Shared Key Check.
    $this->key = "e96RTWcs7bqeqYmVETVKU9bSZFakzNH3SR2Mm3l0";
    $this->hash = hash("sha256", $this->key);

  }

}
