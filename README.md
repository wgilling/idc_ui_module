# idc_ui_module

## Defining a custom page

To define a custom page:

- create a function in `PageController.php` that returns a theme that points to a template and whatever variables you want
- define the template in the `hook_theme()` in the `.module` file
- define routing configuration in `routing.yml`

To define a custom block:

- create a block php file in `src/Plugin/Block` that returns a theme that points to a template and whatever variables you want (similar to the Controller above) OR return a simple `#markup` string if you don't need anything complex.
