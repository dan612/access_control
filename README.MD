# Access Control (Drupal 8)
## This module provides a mechanism to block anonymous users from accessing the website.
---
### Setup
1.) Enable the module
```
drush en access_control
```
2.) Go to admin page for control switch located at:
```
admin/access-control/killswitch
```
3.) Enabling this will lockdown the site against anonymous traffic

For fun, it also shows a list of page titles on the response to anonymous users. Ex:
![](https://www.danchadwickdesign.com/sites/default/files/2020-01/Screen%20Shot%202020-01-27%20at%201.18.39%20PM.png)

---
### TBD / Future Dev
In development is the ability to restrict access based on the presence of a shared key and common header passed from Cloudflare. All requests lacking this header or not matching the shared key will be denied, effectively providing origin lockdown. Example CF worker script to add the header and key

```
    addEventListener('fetch', event => {
    event.respondWith(addHeader(event.request))
    })
    async function addHeader(request) {
        const sharedKey = "e96RTWcs7bqeqYmVETVKU9bSZFakzNH3SR2Mm3l0"
        req = new Request(request)
        req.headers.set('x-cf-key',sharedKey)
        return await fetch(req.url, req)
    }
```

To add into .module, similar to:
```
// @todo - domain detection
// @todo - pass secret key via CF workers - this gives origin lockdown
// meaning all requests would need to route via CF

if ($_SERVER['SERVER_NAME'] == "www.example.com") {
    accessCheck();
}

function accessCheck() {
    $hash = \Drupal::service('access_control.auth');
    $requestHash = $_SERVER['x-cf-key'];
    $cfCheck = $_SERVER['Server'];

    if ($hash != $requestHash or $cfCheck != "cloudflare") {
    //  Throw new NotFoundHttpException();
    }
}
```