# Admin for Marlinc Projects

This bundle extends / modifies the default [SonataAdminBundle](https://github.com/sonata-project/SonataAdminBundle) and [SonataClassificationBundle](https://github.com/sonata-project/SonataClassificationBundle).

It adds the following features:

- General modifications of the default styles / templates
- Improved export functionality
- Support for SoftDeletable / Trash / Restore
- Access control based on users assigned to entities (in addition to roles)
- Form extensions for common useful functions for admin forms

## Install

Be sure that SonataAdminBundle and SonataClassificationBundle are working.

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

## Usage

### Classification

This bundle provides three ways to classify other entities:

1. **Tag**: The most basic way to add common information to an entity. Has a *context*, a *slug* and can be *enabled / disabled*.
2. **Collection**: Supplements tags by providing a *description* field and the option to attach a *media* entity. Can also be marked as *hidden* for internal usage.
3. **Category**: Supplements collections by providing hierarchical information (*order*, *parent*, *children*).
