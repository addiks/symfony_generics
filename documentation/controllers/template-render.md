# Symfony Generics
## Render template controller
===================================

Class: Addiks\SymfonyGenerics\Controllers\GenericTemplateRenderController

This generic controller allows to render a template and provide pre-configured arguments to that template.

Supported parameters:
| Key                     | Optional | Description                                         |
| ----------------------- | -------- | --------------------------------------------------- |
| template                | REQUIRED | Path to the template file.                          |
| arguments               | OPTIONAL | Configuration of arguments given to the template.   |
| authorization-attribute | OPTIONAL | Name of the attribute used for authorization check. |

To understand the authorization-attribute, see the [symfony documentation on voters][1].

For a more detailed description of the argument configuration, see the documentation of the [argument-compiler][2].

[1]: https://symfony.com/doc/current/security/voters.html
[2]: documentation/service/argument-compiler.md

Full symfony service XML example:

```xml
    <service
        id="my_render_template_controller"
        class="Addiks\SymfonyGenerics\Controllers\GenericTemplateRenderController"
    >
        <argument type="service" id="symfony_generics.controller_helper" />
        <argument type="service" id="symfony_generics.argument_compiler" />
        <argument type="collection">
            <argument key="template">MyCoolBundle::some/path.html.twig</argument>
            <argument key="authorization-attribute">display-my-thing</argument>
            <argument key="arguments" type="collection">
                <argument key="myThing">$some_parameter_from_the_request</argument>
            </argument>
        </argument>
    </service>
```
