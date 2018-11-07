[![Travis Build Status](https://travis-ci.org/addiks/symfony_generics.svg?branch=master)](https://travis-ci.org/addiks/symfony_generics)
[![Scrutinizer Build Status](https://scrutinizer-ci.com/g/addiks/symfony_generics/badges/build.png?b=master)](https://scrutinizer-ci.com/g/addiks/symfony_generics/build-status/master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/addiks/symfony_generics/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/addiks/symfony_generics/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/addiks/symfony_generics/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/addiks/symfony_generics/?branch=master)

# symfony_generics

THIS IS STILL UNFINISHED AND NOT YET QUITE READY FOR USE!

This is a collection of configurable generic components to use for symfony. (Currently it mostly consists of generic
controllers.) I believe that as a developer you should concentrate your work as much as possible to model and mold your
domain of choice into software. Other technical necessities (such as controllers) should be reduced as much as possible.

The generic components are built in a way that they can be re-used multiple times in different configurations to replace
components in your software that you would otherwise would have to build from scratch. This not only saves you time, it
also improves the quality of your software because all the components in this library are fully tested. Most
individually implemented components are not tested, especially the ones that are not directly related to the business-
model (please be honest, are your compenents fully tested?).

In short, these are the pro's of using this library:
* Less code to write and maintain
* More time for the things that make your software unique
* A bigger part of your application will be battle-tested
* More standardized parts that other developers will know how to work with

These components currently exist in the library:

| Type       | Name                               | Description                        |
| ---------- | ---------------------------------- | ---------------------------------- |
| Controller | API/GenericEntityCreateController  | API to create an entity.           |
| Controller | API/GenericEntityFetchController   | API to download an entity.         |
| Controller | API/GenericEntityInvokeController  | API to call a method on an entity. |
| Controller | API/GenericEntityListingController | API to get a list of entities.     |
| Controller | API/GenericEntityRemoveController  | API to remove an entity.           |
| Controller | API/GenericServiceInvokeController | API to call a method on a service. |
| Controller | GenericTemplateRenderController    | Renders a template with arguments. |
| Controller | GenericExceptionResponseController | Handle different exceptions.       |
