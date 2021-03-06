# Magento Project Mess Detector

Author: Fabrizio Branca ([fbrnc.net](http://fbrnc.net) / [@fbrnc](https://twitter.com/fbrnc))

[![Build Status](https://travis-ci.org/AOEpeople/mpmd.svg)](https://travis-ci.org/AOEpeople/mpmd)

Some additional commands for the excellent [n98-magerun Magento command-line tool](https://github.com/netz98/n98-magerun) that will help you find out how messed up a Magento instance is :)

```
n98-magerun.phar | grep mpmd
mpmd
 mpmd:codepooloverrides                  Find all code pool overrides
 mpmd:corehacks                          Find all core hacks
 mpmd:dependencycheck                    Find dependencies
 mpmd:dependencycheck:configured         Returns the list of modules that are CONFIGURED in these module's config xml.
 mpmd:dependencycheck:graph:class        Creates a class graph
 mpmd:dependencycheck:graph:configured   Creates a graph of all configured depencencies.
 mpmd:dependencycheck:graph:module       Creates a module graph
 mpmd:dependencycheck:verify             Checks if found dependencies match a given module's xml file

```

## Table of Contents

* [Installation](#installation)
* **Compare files**
	* [`mpmd:corehacks`](#command-mpmdcorehacks)
	* [`mpmd:codepooloverrides`](#command-mpmdcodepooloverrides)
* **[Dependency Checker](#dependency-checker)**
	* [How does it work?](#how-does-it-work)
	* [Why?](#why)
	* [Parsers](#parsers)
	* [Handlers](#handlers)
	* [Specifying sources](#specifying-sources)
	* Commands: Tables
		* [`mpmd:dependencychecker`](#command-mpmddependencychecker)
		* [`mpmd:dependencychecker:verify`](#command-mpmddependencycheckerverify)
		* [`mpmd:dependencychecker:configured`](#command-mpmddependencycheckerconfigured)
	* Commands: Graphs
		* [`mpmd:dependencychecker:graph:module`](#command-mpmddependencycheckergraphmodule)
		* [`mpmd:dependencychecker:graph:class`](#command-mpmddependencycheckergraphclass)
		* [`mpmd:dependencychecker:graph:configured`](#command-mpmddependencycheckergraphconfigured)
	* [How to run the unit tests](#how-to-run-the-unit-tests)
	* [Interesting Graphviz commands](#interesting-graphviz-commands)


## Installation

There are a few options. You can check out the different options in the [MageRun docs](http://magerun.net/introducting-the-new-n98-magerun-module-system/).

Here's the easiest:

0. Install n98-magerun if you haven't already. Find the instructions on the [n98-magerun wiki](https://github.com/netz98/n98-magerun/wiki/Installation-and-Update).

1. Create `~/.n98-magerun/modules/` if it doesn't already exist. (or `/usr/local/share/n98-magerun/modules` or put your modules inside your Magento instance in `lib/n98-magerun/modules` if you prefer that)
```
mkdir -p ~/.n98-magerun/modules/
```
2. Clone the mpmd repository in there. 
```
git clone https://github.com/AOEpeople/mpmd.git ~/.n98-magerun/modules/mpmd
```
3. It should be installed. To verify that it was installed correctly, check if the new commands show up in the command list:
```
n98-magerun.phar | grep mpmd
```

## Commands

### Command `mpmd:corehacks`
```
Usage:
 mpmd:corehacks [--format[="..."]] pathToVanillaCore [htmlReportOutputPath] [skipDirectories]

Arguments:
 pathToVanillaCore     Path to Vanilla Core used for comparison
 htmlReportOutputPath  Path to where the HTML report will be written
 skipDirectories       ':'-separated list of directories that will not be considered (defaults to '.svn:.git')
```

This command requires a vanilla version of Magento (same version and edition! Run `n98-magerun.phar sys:info` for more details) to be present somewhere in the filesystem.
It will then traverse all project files and compare them with the original files. 
This command will also be able to tell the difference between whitespace or code comments changes and real code changes.
It will generate a HTML report that also includes the diffs.

```
$ cd /var/www/magento/htdocs
$ n98-magerun.phar mpmd:corehacks /path/to/vanilla/magento /path/to/report.html
Comparing project files in 'var/www/magento/htdocs' to vanilla Magento code in '/path/to/vanilla/magento'...
+----------------------+-------+
| Type                 | Count |
+----------------------+-------+
| differentFileContent | 2     |
| identicalFiles       | 16049 |
| fileMissingInB       | 1     |
| sameFileButComments  | 0     |
+----------------------+-------+
Generating detailed HTML Report
```

Report preview:

![Image](/docs/img/corehacks.jpg)

### Command: `mpmd:codepooloverrides`
```
Usage:
 mpmd:codepooloverrides [--format[="..."]] [htmlReportOutputPath] [skipDirectories]

Arguments:
 htmlReportOutputPath  Path to where the HTML report will be written
 skipDirectories       ':'-separated list of directories that will not be considered (defaults to '.svn:.git')
```

This command will compare all code pools with each other and detect files that are overriding each other.
It will show identical files (What's the point of these? But yes, seen projects where this happened), copied files with changes in comments and whitespace only,
and real changes. Of course with diff...
 
Report preview:

![Image](/docs/img/codepooloverride.jpg)




## Dependency Checker

![](/docs/img/graph_intro.png)

The dependency checker parses one or more files or directories and detects PHP classes that are being "used" there. 

### How does it work?

The dependency checker is a "semi" static code analysis tool. That means it does the job without actually executing any of the PHP code you're pointing it to, but it does need that module to be installed correctly and it will invoke the Magento framework to resolve classpaths (`catalog/product` -> `Mage_Catalog_Model_Product`)

### Why?

While tools like [pdepend](http://pdepend.org/) exist those tools don't know anything about Magento in general, Magento's special classpaths and where they are being used. Also sometimes the numbers generated by pdepend are a little overwhelming and after all what are you going to do knowing that you're module has an avarage cyclomatic complexity of x? 

The **mpmd:dependencychecker** will 
- help you to detect other Magento modules that the module you're currently looking at depends on.
- check you module's configuration and let's you know if all actual dependencies are declared correctly and will also show if the module is declaring dependencies that this tool didn't detect (Note: this tool isn't perfect, so please double check before removing any dependencies)
- show you the relations between modules and individual classes
- produce pretty graphs that you can render with [Graphviz](http://www.graphviz.org/)
- help you detect code that requires some refactoring and will help you create cleaner - less dependent - modules in the first place. 

### Parsers

The dependency checker comes with two different parsers (and allows you to add new ones:)

| Parser    | Will process   | How it works                                                                                                                                        |
|-----------|----------------|-----------------------------------------------------------------------------------------------------------------------------------------------------|
| [Tokenizer](src/Mpmd/DependencyChecker/Parser/Tokenizer.php) | \*.php, \*.phtml | The `tokenizer` parser will split the PHP file into tokens and traverses them. Handlers can subscribe to token to detect various class usages. |
| [Xpath](src/Mpmd/DependencyChecker/Parser/Xpath.php)     | \*.xml          | The `xpath` parser will read the file into a `SimpleXMLElement` object and will pass this to all the subscribed handlers                            |

#### How to add your own parser

Add a new parser via n98-magerun's YAML configuration

```
commands:
  Mpmd\Magento\DependencyCheckCommand:
    parsers:
      - Mpmd\DependencyChecker\Parser\Tokenizer
      - Mpmd\DependencyChecker\Parser\Xpath
      - (... add your parser here ...)
```

All parsers need to implement [`Mpmd\DependencyChecker\Parser\ParserInterface`](src/Mpmd/DependencyChecker/Parser/ParserInterface.php). Also checkout the [`AbstractParser`](src/Mpmd/DependencyChecker/Parser/AbstractParser.php) that implements that interface and might be a good starting point. 

### Handlers

Every parser comes with a number of handlers. Here's the list of default handlers that come with the dependency checker:

| Parser    | Handler               | Will process                     | What it does                                                                                         |
|-----------|-----------------------|----------------------------------|------------------------------------------------------------------------------------------------------|
| [Tokenizer](src/Mpmd/DependencyChecker/Parser/Tokenizer.php) | [Interfaces](src/Mpmd/DependencyChecker/Parser/Tokenizer/Handler/Interfaces.php)            | `T_IMPLEMENTS`                   | Finds interfaces: `class A implements B {}`                                                          |
| [Tokenizer](src/Mpmd/DependencyChecker/Parser/Tokenizer.php) | [WhitespaceString](src/Mpmd/DependencyChecker/Parser/Tokenizer/Handler/WhitespaceString.php)      | `T_NEW`, `T_EXTENDS`, `T_CLASS`  | Finds classes instantiated with 'new': `$a = new B();`<br />Finds extended classes: `class A extends B {}` |
| [Tokenizer](src/Mpmd/DependencyChecker/Parser/Tokenizer.php) | [StaticCalls](src/Mpmd/DependencyChecker/Parser/Tokenizer/Handler/StaticCalls.php)           | `T_DOUBLE_COLON`                 | Finds static calls: `A::B` and `A::B()`                                                               |
| [Tokenizer](src/Mpmd/DependencyChecker/Parser/Tokenizer.php) | [TypeHints](src/Mpmd/DependencyChecker/Parser/Tokenizer/Handler/TypeHints.php)             | `T_FUNCTION`                     | Finds type hints: `function a (B $b) {}`                                                             |
| [Tokenizer](src/Mpmd/DependencyChecker/Parser/Tokenizer.php) | [MagentoFactoryMethods](src/Mpmd/DependencyChecker/Parser/Tokenizer/Handler/MagentoFactoryMethods.php) | `T_STRING` for specific keywords | Finds classes instantiated with one of Magento's factory methods and resolves them to real PHP classes not taking rewrites into account:<br />`Mage::getModel()`<br />`Mage::getSingleton()`<br />`Mage::getResourceModel()`<br />`Mage::getResourceSingleton()`<br />`$this->getLayout()->createBlock()`<br />`Mage::getBlockSingleton()`<br />`Mage::helper()`<br />`Mage::getResourceHelper()`<br />`Mage::getControllerInstance()`<br />                                                                                                  |
| [Xpath](src/Mpmd/DependencyChecker/Parser/Xpath.php)     | [LayoutXml](src/Mpmd/DependencyChecker/Parser/Xpath/Handler/LayoutXml.php)             | All xml files                    | Finds blocks and resolves them into real PHP classes: `<block type="core/text">`                      |
| [Xpath](src/Mpmd/DependencyChecker/Parser/Xpath.php)     | [SystemXml](src/Mpmd/DependencyChecker/Parser/Xpath/Handler/SystemXml.php)             | All xml files                    | Finds references to models in system.xml files:<br />`<frontend_model>adminhtml/system_config_form_field_notification</frontend_model>`<br />`<source_model>adminhtml/system_config_source_yesno</source_model>`<br />`<backend_model>adminhtml/system_config_backend_store</backend_model>`                                                                                                     |

**Note:** `MagentoFactoryMethods`, `LayoutXml` and `SystemXml` need to resolve Magento classpaths into real PHP classes. The challenge hereby is NOT to take rewrites into account since rewriting a class is a mechanism that was introduced to ALLOW decoupling without dependending on each other. In order to leverage Magento and it's configuration to resolve the class paths but not take the rewrite into accounts 
- the module that we're testing needs to be installed into a functioning Magento environment and all dependencies must be fulfilled
- we need to "[trick](src/Mpmd/Util/MagentoFactory.php)" something into being `Mage_Core_Model_Config` having access to the same data but doing things slightly differently. `Mpmd\Util\MagentoFactory` takes care of that and provides access to some of the original functions like `getModelClassName()` and `getBlockClassName()`...


#### How to add your own handler

Add a new handler via [n98-magerun's YAML configuration](https://github.com/netz98/n98-magerun/wiki/Config) (also checkout [n98-magerun's documentation for custom commands](https://github.com/netz98/n98-magerun/wiki/Add-custom-commands))

```
commands:
  Mpmd\Magento\DependencyCheckCommand:
	Mpmd\DependencyChecker\Parser\Tokenizer:
      handlers:
        - Mpmd\DependencyChecker\Parser\Tokenizer\Handler\WhitespaceString
        - Mpmd\DependencyChecker\Parser\Tokenizer\Handler\Interfaces
        - (... add your tokenizer handler here ...)
    <Parser>:
      handlers:
        - (... add your <parser> handler here ...)    
```

All handlers need to implement [`Mpmd\DependencyChecker\HandlerInterface`](src/Mpmd/DependencyChecker/HandlerInterface.php). Also checkout the [`AbstractHandler`](src/Mpmd/DependencyChecker/AbstractHandler.php) and the inheriting abstract handlers for the [tokenizer parser](src/Mpmd/DependencyChecker/Parser/Tokenizer/Handler/AbstractHandler.php) and the [xpath parser](src/Mpmd/DependencyChecker/Parser/Xpath/Handler/AbstractHandler.php) that implement that interface and might be a good starting point. 

### Specifying sources

Almost every command (and sub-command) of the dependency checker requires you to specify what you want to analyze. This can be one or more files or directories. And glob patterns are also supported. Here are some examples:

```
# Single file:
$ n98-magerun.phar mpmd:dependencycheck -m app/code/local/My/Module/Model/Test.php

# Full directory:
$ n98-magerun.phar mpmd:dependencycheck -m app/code/local/My/Module/

# Full modman directory (will also find the files you might have in app/design/):
$ n98-magerun.phar mpmd:dependencycheck -m .modman/My_Module

# Glob patterns are supported:
$ n98-magerun.phar mpmd:dependencycheck -m app/code/local/My/*/
$ n98-magerun.phar mpmd:dependencycheck -m app/code/local/*/*/
$ n98-magerun.phar mpmd:dependencycheck -m app/code/*/*/*/

# Multiple locations 
$ n98-magerun.phar mpmd:dependencycheck -m app/code/local/My/Module/ app/code/community/My/OtherModule/ app/design/frontend/mypackage
```
**Note:** Please specify absolute paths or paths relative to your **Magento root directory** (not relative to the current directory, which might be different if n98-magerun detected Magento in a different directory (e.g. htdocs/) or you're using `--root-dir=...` to tell n98-magerun where to find your Magento root)

### Command: `mpmd:dependencychecker`

This is the main command that gives you access to following options (specify one or more):

| Option        | Short option | What it does                                                                                                                 |
|---------------|--------------|------------------------------------------------------------------------------------------------------------------------------|
| `--modules`   | `-m`         | This shows you all modules that were detected in the specified sources and the modules that this code depends on.            |
| `--libraries` | `-l`         | This shows you all the libraries (`lib/*`) that the specified sources depend on                                              |
| `--classes`   | `-c`         | This detects all the classes in the specified sources (where available) and shows what other classes they are using and how. |
| `--details`   | `-d`         | This shows all the details (verbose!)                                                                                        |

#### Examples:

##### Modules `-m|--modules`	
```
$ n98-magerun.phar mpmd:dependencycheck -m app/code/core/Mage/Captcha

+---------------+----------------+
| Source Module | Target Module  |
+---------------+----------------+
| Mage_Captcha  | Mage_Core      |
| Mage_Captcha  | Mage_Admin     |
| Mage_Captcha  | Mage_Customer  |
| Mage_Captcha  | Mage_Checkout  |
| Mage_Captcha  | Mage_Adminhtml |
+---------------+----------------+
```

##### Libraries `-l|--libraries`
```
$ n98-magerun.phar mpmd:dependencycheck -l app/code/core/Mage/Captcha

+-----------+
| Libraries |
+-----------+
| Varien    |
| Zend      |
+-----------+
```

##### Classes `-c|--classes`
```
$ n98-magerun.phar mpmd:dependencycheck -c app/code/core/Mage/Captcha

+------------------------------------------+-----------------------------------------+--------------------------+
| Source class                             | Target Class                            | Access Types             |
+------------------------------------------+-----------------------------------------+--------------------------+
| Mage_Captcha_Block_Captcha               | Mage_Core_Block_Template                | extends                  |
| Mage_Captcha_Block_Captcha               | Mage_Captcha_Helper_Data                | helper                   |
| Mage_Captcha_Block_Captcha_Zend          | Mage_Core_Block_Template                | extends                  |
| Mage_Captcha_Block_Captcha_Zend          | Mage_Captcha_Helper_Data                | helper                   |
| Mage_Captcha_Model_Config_Form_Backend   | Mage_Captcha_Model_Config_Form_Abstract | extends                  |
| Mage_Captcha_Model_Config_Form_Abstract  | Mage_Core_Model_Config_Data             | extends                  |
| Mage_Captcha_Model_Config_Form_Frontend  | Mage_Captcha_Model_Config_Form_Abstract | extends                  |
...
```

##### Details `-d|--details`
```
$ n98-magerun.phar mpmd:dependencycheck -d app/code/core/Mage/Captcha

+------------------------------------------------------------------------+------------------+-------------------------------------------------+
| File                                                                   | Access Type      | Class                                           |
+------------------------------------------------------------------------+------------------+-------------------------------------------------+
| app/code/core/Mage/Captcha/Block/Captcha.php                           | class            | Mage_Captcha_Block_Captcha                      |
| app/code/core/Mage/Captcha/Block/Captcha.php                           | extends          | Mage_Core_Block_Template                        |
| app/code/core/Mage/Captcha/Block/Captcha.php                           | helper           | Mage_Captcha_Helper_Data                        |
| app/code/core/Mage/Captcha/Block/Captcha/Zend.php                      | class            | Mage_Captcha_Block_Captcha_Zend                 |
| app/code/core/Mage/Captcha/Block/Captcha/Zend.php                      | extends          | Mage_Core_Block_Template                        |
| app/code/core/Mage/Captcha/Block/Captcha/Zend.php                      | helper           | Mage_Captcha_Helper_Data                        |
| app/code/core/Mage/Captcha/etc/system.xml                              | source_model     | Mage_Adminhtml_Model_System_Config_Source_Yesno |
| app/code/core/Mage/Captcha/etc/system.xml                              | source_model     | Mage_Captcha_Model_Config_Font                  |
...
```

### Command: `mpmd:dependencychecker:verify`

This command compares the actual dependencies detected by looking at the code with the ones declared for a given module (specify with `-m <Module_Name>`)

Example:
```
$ n98-magerun.phar mpmd:dependencycheck:verify -m Mage_Catalog app/code/core/Mage/Catalog

+---------------------+---------------------------------------------+--------------+
| Declared Dependency | Actual Dependency                           | Status       |
+---------------------+---------------------------------------------+--------------+
| Mage_Cms            | Mage_Cms                                    | OK           |
| Mage_Dataflow       | Mage_Dataflow                               | OK           |
| Mage_Eav            | Mage_Eav                                    | OK           |
| Mage_Index          | Mage_Index                                  | OK           |
| -                   | Mage_Adminhtml                              |  Undeclared  |
| -                   | Mage_Api2                                   |  Undeclared  |
| -                   | Mage_Api                                    |  Undeclared  |
| -                   | Mage_Bundle                                 |  Undeclared  |
| -                   | Mage_CatalogIndex                           |  Undeclared  |
...
```

In this example you can see how `Mage_Catalog` only declares dependencies to `Mage_Cms`, `Mage_Dataflow`, `Mage_Eav` and `Mage_Index`. But in reality `Mage_Catalog` depends on many more modules...

**Note:** Since pointing to a directory in `app/code/` will not take the layout and template files into account that might belong to a module and will potentiallu also introduce dependencies it is recommended to always include all relevant directories to the `source` parameter, or - in case you're using modman and everything lives in a separate directory anyway point this command to that directory instead:
```
n98-magerun.phar mpmd:dependencycheck:verify -m My_Module ../.modman/My_Module
```

### Command: `mpmd:dependencychecker:configured`

This command returns a list of all **configured** dependencies (taken from config xml). This (and the corresponding `mpmd:dependencychecker:graph:configured`) is the only command that does not analyze any files but only reads the dependencies from the configuration.

The command accepts one or more module and also supports glob-like patterns:

Examples
```
# Single module
$ n98-magerun.phar mpmd:dependencycheck:configured My_Module

# Two modules 
$ n98-magerun.phar mpmd:dependencycheck:configured My_Module My_OtherModule

# Wildcard(s)
$ n98-magerun.phar mpmd:dependencycheck:configured 'Mage_*' 'Enterprise_*'

# All modules
$ n98-magerun.phar mpmd:dependencycheck:configured '*'
```
**Note:** since bash might replace your glob syntax with different paths in case they match something in the current directory you should wrap any glob patterns in `'single quotes'`/ 

### Command: `mpmd:dependencychecker:graph:module`

This command will render a dependency graph for the relevant modules as a `dot` file. Use the Graphviz tool (Ubuntu: `sudo apt-get install graphviz`) to create a svg (or many other formats).
Feel free to modify the dot-file to match any different styling. Find a full reference on the [Graphviz website](http://www.graphviz.org/).

Example:
```
$ n98-magerun.phar mpmd:dependencycheck:graph:module app/code/core/Mage/* | dot -Tsvg -o mage.svg

# or:
$ n98-magerun.phar mpmd:dependencycheck:graph:module app/code/core/Mage/* > mage.dot
# customize your graph in mage.dot...
$ dot -Tsvg mage.dot -o mage.svg
```
Here's a tiny(!) crop of the graph generated in this example (click the image for a full-sized svg). Sadly there are a ton of dependencies in the Magento core (and most likely also in your modules) so these graphs can quickly grow pretty huge: 

[![Image](/docs/img/mage_modulegraph.png)](/docs/img/mage_modulegraph.svg)

### Command: `mpmd:dependencychecker:graph:class`

While the previous command shows you a higher level view on modules only, `mpmd:dependencychecker:graph:class` will drill down into individual classes and optionally group them by module. Consider this graph "zooming in" into the modules in order to find out what classes are responsible for the dependency.

The graph shows different line types:

| Style  | Type                           |
|--------|--------------------------------|
| Solid  | **Inheritance:** extends, implements            |
| Dashed | **Composition**: new, type_hints, get*, blocks, source/backend/frontend_model |
| Dotted | everything else (static calls) |

(This might require some better categorization (e.g. implements != inheritance, type hint != composition)

Example: 
```
# ungrouped
$ n98-magerun.phar mpmd:dependencycheck:graph:class app/code/core/Mage/Captcha | dot -Tpng -o Mage_Captcha.png

# grouped
$ n98-magerun.phar mpmd:dependencycheck:graph:class --group app/code/core/Mage/Captcha | dot -Tpng -o Mage_Captcha.png
```

| Ungrouped  | Grouped |
|--------|--------------------------------|
| [![Image](/docs/img/Mage_Captcha.png)](/docs/img/Mage_Captcha.svg)  | [![Image](/docs/img/Mage_Captcha_grouped.png)](/docs/img/Mage_Captcha_grouped.svg) |


### Command: `mpmd:dependencychecker:graph:configured`

This command will create a graph from the **configured** dependencies. The syntax is the same used in `mpmd:dependencychecker:configured`:

Example:
```
$ n98-magerun.phar mpmd:dependencycheck:graph:configured 'Mage_*' | dot -Tsvg -o Mage.svg
```

**Disclaimer:** There's a good chance that some dependencies are **not detected** at all (actually if variables are being used when instanciating object like  `Mage::getModel($modelClass);`, `Mage::getModel("acme/$model");` or `new $class()` then mpmd is ignoring those - and there might be some more scenarious where mpmd might be missing some dependencies). Please do not solely rely on the mpmd report while refactoring or before removing any modules!

### How to run the unit tests

This plugin comes with unit tests. These unit tests will run outside of any n98-magerun or Magento context (they will mock everything from there). Running them by simply calling `phpunit` in the root directory. Also check the project out on [Travis CI](https://travis-ci.org/AOEpeople/mpmd) where the tests will be run on every commit. 
```
phpunit --debug
```

### Interesting Graphviz commands

There's a ton of things you can do with Graphviz. Starting from choosing different layouts to clustering nodes and coloring nodes and/or edges differently (e.g. by namespace or code pool?). Here are some examples:

In the following examples I replaced all nodes with simple black dots connected with black lines:
```
edge [arrowhead=vee, arrowtail=inv, arrowsize=.7, color="black"];
node [fontname="verdana", fixedsize=true, width="0.3", shape=point, style="filled", fillcolor="black"];
```

Replace splines with straight lines:
```
splines=false;
```
Circle pattern:
```
layout=circo;
```
![](docs/img/circo.png)

Radial pattern:
```
layout=twopi;
```
| Configured dependencies<br />`mpmd:dependencycheck:graph:configured 'Mage_*'` | Actual dependencies<br />`mpmd:dependencycheck:graph:module 'app/code/core/Mage/*'` |
|--------|--------------------------------|
| ![Image](/docs/img/twopi_small.png) | ![Image](/docs/img/twopi_actual_small.png) |

No overlapping:
```
overlap=false;
layout=twopi;
```
([Click for svg](/docs/img/mage.svg))
[![Image](/docs/img/mage.png)](/docs/img/mage.svg)
