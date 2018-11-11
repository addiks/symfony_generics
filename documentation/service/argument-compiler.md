# Symfony Generics
## Argument Compiler
===================================

This describes the capabilities of the argument compiler that is used for most internal components to compile configured
argument configurations into actual arguments to use for other processes (entity-calls, template-parameters, ...).

The argument compiler knows two different ways to compile arguments: for custom function calls and for associative
arrays. The former is used to prepare arguments for custom services and entity-methods, the latter for all other things
like template-rendering.

Argument configurations are always in the form of an associated array (which may be multidimensional) where the key
represent either the arguments of a call or the parameters given to a template (or such). Every entry in this array may
be a string or an array to configure how that argument should be constructed / converted.

### Different types of simple string configurations:

| Name            | Pattern             | Example                   | Description                         |
| --------------- | ------------------- | ------------------------- | ----------------------------------- |
| Request         | $VARIABLE           | $user_name                | Variable from the request object.   |
| Service as is   | @SERVICE-ID         | @app.my_service           | Service from the service-container. |
| Service factory | @SERVICE-ID::METHOD | @user_factory::createUser | Uses a service as a factory.        |
| Static factory  | CLASS::METHOD       | Foo\Bar\Baz::createThingy | Uses a static method of a class.    |

Of the above, the two factory-using ways will create their factory-arguments using the argument-compiler (see below).

### Different types of array configurations:

| Description     | Parameter | Required | Parameter-Description          |
| --------------- | --------- | -------- | ------------------------------ |
| Fetch a Service | id        | REQUIRED | Id of the service to get.      |
|                 | method    | OPTIONAL | Calls a method on the service. |
|                 | arguments | OPTIONAL | Arguments for the call.        |

(These are still in a very unfinished state, there is currently only one array configuration; will probably change in
 the future.)

### Building arguments for method- or function-calls

There are a few special considereations when building arguments for function- or method calls. When building the
arguments for a call, the argument compiler will look at the defined arguments of that function or methed and tries to
automatically find the values for these arguments.

First it will look if the argument is defined by the argument-configuration (by name) and if so it uses that
configuration. If the argument is not defined by the argument-configuration, the compiler will next try to look if a
request-parameter with the same name exist and use that one. If the argument is neither defined in the configuration nor
included in the request it just assumes that the argument has a default value and use that one. If neither of these
three possible data-sources are true then an InvalidArgumentException ("Missing parameter ...") will be thrown.
