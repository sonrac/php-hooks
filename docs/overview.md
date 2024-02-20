# Pre commit hook

For local check we can use pre-commit-hook before commit.

During checks we can check:

* Code style
* Run static analyzer
* Run tests
* etc.

Example hook script for this package you can see [here](../../scripts/hooks/_pre-commit.docker.sh)

Hook starts only for modified files not for whole project if it possible.
Some checks we can not start only for some files such as deptrac

All check started parallel for execution speed up. 
We use [pcntl php ext](https://www.php.net/manual/en/book.pcntl.php) for this.

## Hook steps

* Get modified files
* Exit with error if modified files list is empty
* Start php beautifier for php modified files
* Add modified files to git
* Start git hook console command

### Hook console command steps

* Read runner config. You can found config [there](../../tools/config/pre-commit-hook.yaml)
* Prepare runner configuration 
  * Parse commands from config
  * Create processes pool
  * Start all processes with commands config from config file
  * Wait while all commands are finished
  * Check processes exit code. Output error information to stdout if some commands fails.
  * Run result table with commands runs report

#### Example table report with errors

```shell

_________________________________________
┌──────────────────────────────┬────────┐
│ command                      │ result │
├──────────────────────────────┼────────┤
│ Static Analyzer src folder   │   √    │
│ Static Analyzer tools folder │   X    │
│ Static Analyzer tests folder │   √    │
│ Code style src folder        │   √    │
│ Code style tools folder      │   X    │
│ Code style tests folder      │   √    │
│ DepTrac                      │   X    │
└──────────────────────────────┴────────┘

```

#### Example success table report

```shell

_________________________________________
┌──────────────────────────────┬────────┐
│ command                      │ result │
├──────────────────────────────┼────────┤
│ Static Analyzer src folder   │   √    │
│ Static Analyzer tools folder │   √    │
│ Static Analyzer tests folder │   √    │
│ Code style src folder        │   √    │
│ Code style tools folder      │   √    │
│ Code style tests folder      │   √    │
│ DepTrac                      │   √    │
└──────────────────────────────┴────────┘
 [OK] Pre hook commit run successfully    
```
