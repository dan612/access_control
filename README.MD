## To install, add this to your project composer.json
```
    "repositories": [
        {
            "type": "package",
            "package": {
                "name": "dan612/access_control",
                "version": "dev-master",
                "type":"drupal-custom",
                "source": {
                    "url": "https://github.com/dan612/access_control.git",
                    "type": "git",
                    "reference": "master"
                }
            }
        }
    ],

```
## Need to be sure composer installers is included:
```
    "require": {
        "composer/installers": "^1.2",
```
## And in extras section the installer path for custom drupal modules:
```
    "extra": {
        "installer-paths": {
            "web/modules/custom/{$name}": ["type:drupal-custom"]
        },
```

Run `composer update`