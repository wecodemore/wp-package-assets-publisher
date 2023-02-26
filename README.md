# WP Package Assets Publisher

## What is this

A Composer plugin that "publishes" assets for packages where WordPress can find them.

### Tell me more...

When building Composer-based "whole-websites" we might require via Composer packages containing 
WordPress code _without_ them being WP plugins or themes.

Such packages would be placed in the `vendor` folder which is oftentimes **outside webroot**.

And that means that **if such packages have assets such as images, scripts, or styles, the browser 
will not be able to reach them**.

That forces us to either write regular WP plugins or move the `vendor` folder inside the webroot.

Neither of the above solutions is ideal, even if both _might_ work depending on the use case.

This package provides an alternative: it symlinks (or copies) such assets files under 
`wp-content/plugins` folder, that is reachable by the browser.

Moreover, being that a WP standard path, we can use functions like `plugin_url()` to get the URL.


## How it works

This is a Composer plugin that provides **a custom installer for packages having 
**`"wordpress-package"`** as type**.

The installer does not change how the default Composer installer installs the package, but 
after the default installer has successfully installed the package, it looks into an 
**`extra.package-assets-paths`** property in the package's `composer.json` and symlinks all the
paths found there into the `/wp-content/plugins/.published-package-assets/{vendor}/{name}` folder.

The `wp-content` base folder is determined based on configuration on root package.

When using [WP Starter](https://github.com/wecodemore/wpstarter), its
[`wordpress-content-dir` setting](https://github.com/wecodemore/wpstarter/blob/dev/docs/04-WP-Starter-Configuration.md#generic-configuration)
suffices as configuration.

Of course, the `extra.wordpress-content-dir` property can be used even if WP Starter is not in use. 


## Usage example

Let's assume a package with a `composer.json` like the following:

```json
{
    "name": "acme/awesome-package",
    "type": "wordpress-package",
    "require": {
        "wecodemore/wp-package-assets-publisher": "@dev"
    },
    "autoload": {
        "files": [
            "bootstrap.php"
        ]
    },
    "extra": {
        "package-assets": [
            "./public"
        ]
    }
}
```

Let's now assume the files structure of the package is the following:

```
|⎯ 📄bootstrap.php
|⎯ 📄composer.json
|⎯ 📂 public
   |⎯ 📄main.css
   |⎯ 📄main.js
```

Now, suppose the above package is required in a WP Starter project with the following `composer.json`:

```json
{
    "name": "acme/acme-website",
    "type": "project",
    "require": {
		"wecodemore/wpstarter": "^3@dev",
		"roots/wordpress": "6.1.*",
		"acme/awesome-package": "^1.0"
    },
	"config": {
        "allow-plugins": {
            "composer/*": true,
            "wecodemore/*": true,
            "roots/wordpress-core-installer": true
        }
    },
	"extra": {
        "wordpress-install-dir": "./public/wp",
        "wordpress-content-dir": "./public/wp-content",
        "installer-paths": {
            "./public/wp-content/plugins/{$name}/": ["type:wordpress-plugin"],
            "./public/wp-content/themes/{$name}/": ["type:wordpress-theme"],
            "./public/wp-content/mu-plugins/{$name}/": ["type:wordpress-muplugin"]
        }
	}
}
```

After Composer install, we will find a folder structure like this (showing only the relevant bits):

```
|⎯ 📂 public
|  |⎯ 📂 wp-content
|     |⎯ 📂 plugins
|        |⎯ 📂.published-package-assets
|           |⎯ 📂 acme
|              |⎯ 📂 awesome-package
|                 |⎯ 📂 public
|                    |⎯ 📄main.css
|                    |⎯ 📄main.js
|⎯ 📂 vendor
   |⎯ 📂 acme
      |⎯ 📂 awesome-package
         |⎯ 📄bootstrap.php
         |⎯ 📄composer.json
         |⎯ 📂 public
            |⎯ 📄main.css
            |⎯ 📄main.js
```

So the package is regularly installed in `vendor/`, but its `public/` folder, that was listed in the
package's `extra.package-assets-paths` property, have been symlinked in 
`public/wp-content/.published-package-assets/acme/awesome-package/public/`.

Thanks to that, the package assets are now under webroot, and so reachable by the browser.

### Helper function

Getting the full URL of that package's assets can be hard, though. This is why this package ships
a helper function **`WeCodeMore\packageAssetUrl()`** which can be used anywhere like so:

```php
$url = WeCodeMore\packageAssetUrl('acme/awesome-package', 'public/main.js');
```

The function takes two arguments, the first is the name of the package as defined in its 
`composer.json`, and the second is the relative path of the asset to retrieve.
Of course, it works only if the requested path was configured in  the package's 
`extra.package-assets-paths` property, and so "published" in place by this package.


## Root config

### Target folder

In projects not using WP Starter, the `extra.wordpress-content-dir` property can still be used to
tell the assets published where to place th published assets.

Alternatively, the root-only **`extra.package-assets-publisher.publish-dir`** config can be used 
instead.

Here's  an example of using the package with the [Bedrock's folder structure](https://roots.io/bedrock/):

```json
{
    "extra": {
        "package-assets-publisher": {
            "publish-dir": "./web/app/plugins"
        }
    }
}
```

### Symlink or copy

The package by default tries to symlink assets paths and only resorts to copy them if symlink fails, 
similarly to how Composer does.

To force the package to copy the files, it is possible to use a different format for the
**`extra.package-assets-publisher.symlink`** property, like the following:

```json
{
    "extra": {
        "package-assets-publisher": {
            "symlink": false
        }
    }
}
```

To force the package to symlink the files and not attempt a copy, set 
`extra.package-assets-publisher.symlink` set to `true`.

Setting `extra.package-assets-publisher.symlink` to any non-boolean value, or not setting it at all
results in the default behavior that symlink are attempted with copy fallback.

### Fail hard

By default, when a failure happens, the package prints an error message, but does not break the
Composer installation/update process. 

To fail "hard", set `extra.package-assets-publisher.strict`  to `true`.

```json
{
    "extra": {
        "package-assets-publisher": {
            "strict": true
        }
    }
}
```

## Package config override

Both the `extra.package-assets-publisher.symlink` and the `extra.package-assets-publisher.strict`
root options, can be overridden at the package level.

That is done via the package-level `extra.package-assets` property, using it as an object like this:

```json
{
    "extra": {
        "package-assets": {
            "paths": [
                "./public"
            ],
            "options": {
                "symlink": false,
                "strict": true
            }
        }
    }
}
```

# System Requirements

 - PHP 7.4+
 - [Composer](https://getcomposer.org/) >= 2.3


# License

MIT. See [LICENSE](LICENSE) file.

