# Development Tools <em>for PocketMine-MP</em>
DevTools is a collection of utilities used for developing PocketMine-MP plugins.

## Features
- Generate skeleton files to bootstrap a new plugin
- Build plugin phars from source code
- Load plugins directly from source code (folder plugins), useful for rapid development
- Check player permissions using commands

## Commands
* _/genplugin <pluginName> <authorName>_: Generates skeleton files for a new plugin
* _/makeplugin \<pluginName\>_: Creates a Phar plugin archive for its distribution
* _/makeplugin *_: Creates Phar plugin archives for all loaded plugins
* _/checkperm \<node\> [playerName]_: Checks a permission node

## Using ConsoleScript to build a DevTools phar from source code
Contrary to popular belief, this is very simple. Assuming you have a php executable in your PATH variable, cd into the DevTools directory (the folder where plugin.yml is located) and simply run the following:
```
php -dphar.readonly=0 path/to/ConsoleScript.php --make path/to/DevTools --relative path/to/DevTools --out path/to/put/devtools/phar/in/DevTools.phar
```
You can then load the phar onto a PocketMine-MP server. A correctly-built DevTools phar can also be executed directly from the command line as if it was the ConsoleScript.

## Build plugin phars from the command line
You can also use the ConsoleScript or a DevTools phar from the command-line to build PocketMine-MP phars or plugin phars.

The script currently takes the following arguments:

| argument | required | description |
|:--------:|:--------:|:------------|
| `--make` | yes | The path to the files you want to bundle into a phar |
| `--relative` | no | Relative path to use when building the phar. This usually isn't necessary for plugins. Used to build PocketMine-MP phars with the `src` directory without including the files in the repository root. |
| `--entry` | no | PHP file within the phar to execute when running the phar from the command-line. Usually not needed for plugins, but required for a PocketMine-MP phar. Used to generate phar stubs. |
| `--stub` | no | (Optional) PHP file to use as a custom phar stub. The stub will be executed when the phar is run from the command line. |
| `--out` | yes | Path and filename of the output phar file. |

Example command line for building a plugin:
```
php -dphar.readonly=0 path/to/ConsoleScript.php --make path/to/your/plugin/sourcecode --out path/to/put/your/plugin.phar
```
