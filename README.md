# idc_ui_module

## Defining a custom page

To define a custom page:

- create a function in `PageController.php` that returns a theme that points to a template and whatever variables you want
- define the template in the `hook_theme()` in the `.module` file
- define routing configuration in `routing.yml`

To define a custom block:

- create a block php file in `src/Plugin/Block` that returns a theme that points to a template and whatever variables you want (similar to the Controller above) OR return a simple `#markup` string if you don't need anything complex.

## iDC Serializer

This module, and the iDC Serializer that is part of this module, incorporates, modifies and otherwise combines parts of the [Facets](https://www.drupal.org/project/facets) and [Pager Serializer](https://www.drupal.org/project/pager_serializer) projects. The date these modifications were first incorporated into this module is March 9, 2021.
