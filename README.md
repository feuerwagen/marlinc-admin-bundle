# Admin for Marlinc Projects

This bundle extends / modifies the default [SonataAdminBundle](https://github.com/sonata-project/SonataAdminBundle).

It adds the following features:

- General modifications of the default styles / templates
- Improved export functionality
- Support for SoftDeletable / Trash / Restore
- Access control based on users assigned to entities (in addition to roles)
- Form extensions for common useful functions for admin forms

## Install

Be sure that SonataAdminBundle is working.

Install this bundle using composer:

```
composer require marlinc/admin-bundle
```

Register the bundle in your AppKernel:

```php
// app/AppKernel.php

public function registerBundles()
{
    return array(
        // ...
        new Marlinc\MarlincAdminBundle(),
        // ...
    );
}
```
