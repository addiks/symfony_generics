[![Travis Build Status][10]][9]
[![Scrutinizer Build Status][11]][12]
[![Scrutinizer Code Quality][13]][14]
[![Code Coverage][15]][16]

# Symfony Generic Components
===================================

This is a collection of configurable generic components to use for symfony. (Currently it mostly consists of generic
controllers.) I believe that as a developer you should focus your work as much as possible to model and mold your
domain of choice into software. Other technical necessities (such as controllers) should be reduced as much as possible.

The generic components in this library are built in a way that they can be re-used multiple times in different
configurations to replace components in your software that you would otherwise would have to build from scratch.
This not only saves you time, it also improves the quality of your software because all the components in this library
are fully tested and thus by extension a bigger part of your application becomes tested.

Additionally, if you use generic components instead of writing everything by hand, your application becomes more uniform
and machine-readable & -interpretable. For example: If you use generic controllers it is easy to tell which controllers
just render a template, you could write a [smoke-test][17] that just executes all template-render-controllers and checks
if any errors happen. Because you know that these controllers just render a template and do nothing else, you know that
you can execute them without side-effect. Without doing much, you would have already tested a big portion of your
application. (I actually plan to include such a smoke-test in this library in the future.)

In short, these are the advantages of using this library:
* Less code to write and maintain
* More time for the things that make your software unique
* A bigger part of your application will be battle-tested
* More standardized parts that other developers will know how to work with

These components currently exist in the library:

| Type       | Name                                    | Description                        |
| ---------- | --------------------------------------- | ---------------------------------- |
| Controller | [API/GenericEntityCreateController][1]  | API to create an entity.           |
| Controller | [API/GenericEntityFetchController][2]   | API to download an entity.         |
| Controller | [API/GenericEntityInvokeController][3]  | API to call a method on an entity. |
| Controller | [API/GenericEntityListingController][4] | API to get a list of entities.     |
| Controller | [API/GenericEntityRemoveController][5]  | API to remove an entity.           |
| Controller | [API/GenericServiceInvokeController][6] | API to call a method on a service. |
| Controller | [GenericTemplateRenderController][7]    | Renders a template with arguments. |
| Controller | [GenericExceptionResponseController][8] | Handle different exceptions.       |

[1]: documentation/controllers/api/entity-create.md
[2]: documentation/controllers/api/entity-fetch.md
[3]: documentation/controllers/api/entity-invoke.md
[4]: documentation/controllers/api/entity-listing.md
[5]: documentation/controllers/api/entity-remove.md
[6]: documentation/controllers/api/service-invoke.md
[7]: documentation/controllers/template-render.md
[8]: documentation/controllers/exception-response.md
[9]: https://travis-ci.org/addiks/symfony_generics
[10]: https://travis-ci.org/addiks/symfony_generics.svg?branch=master
[11]: https://scrutinizer-ci.com/g/addiks/symfony_generics/badges/build.png?b=master
[12]: https://scrutinizer-ci.com/g/addiks/symfony_generics/build-status/master
[13]: https://scrutinizer-ci.com/g/addiks/symfony_generics/badges/quality-score.png?b=master
[14]: https://scrutinizer-ci.com/g/addiks/symfony_generics/?branch=master
[15]: https://scrutinizer-ci.com/g/addiks/symfony_generics/badges/coverage.png?b=master
[16]: https://scrutinizer-ci.com/g/addiks/symfony_generics/?branch=master
[17]: https://en.wikipedia.org/wiki/Smoke_testing_%28software%29
