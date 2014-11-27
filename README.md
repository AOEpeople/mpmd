# Magento Project Mess Detector

Some additional commands for the excellent m98-magerun Magento command-line tool that will help you to find out how messed up a Magento instance is :)

## Installation

There are a few options. You can check out the different options in the [MageRundocs](http://magerun.net/introducting-the-new-n98-magerun-module-system/).

Here's the easiest:

1. Create `~/.n98-magerun/modules/` if it doesn't already exist. (or `/usr/local/share/n98-magerun/modules` if you prefer that)
```
mkdir -p ~/.n98-magerun/modules/
```
2. Clone the mpmd repository in there. 
```
git clone git@github.com:AOEpeople/mpmd.git ~/.n98-magerun/modules/mpmd
```
3. It should be installed.To see that it was installed, check to see if one of the new commands is in there, like `diff:files`.
```
n98-magerun mpmd:corehacks
```

## Commands

### mpmd:corehacks
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