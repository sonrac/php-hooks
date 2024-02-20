# Main git hook config options

## Template variables
Every string element in config can use 3 template variables

| Variable       | Description                                                                 |
|----------------|-----------------------------------------------------------------------------|
| %php_cmd%      | Php binary full path. Don't use relative path to php in this tool           |
| %composer_cmd% | Composer binary full path. Don't use relative path to composer in this tool |
| %project_dir%  | Project root dir                                                            |

### Example 

In this example we register runner with one command which will be start command from composer commands 

```shell
composer deptrac
```

```yaml
# We use variable in title.
name: "Pre-commit hook. Started in %project_dir%"
description: Pre-commit hook parallel execution
globalEnvFile: null
globalEnv:
  XDEBUG_MODE: off
commands:
  deptrac:
    name: DepTrac
    description: Check layers dependency
    cmd:
      # Using variable in commands.
      - "%composer_cmd%"
      - deptrac
    envFile: null
    includeFilesPattern:
      php: src
    reverseOutput: false
```

##  name

> Type: string

> Required: true

> Runner title

##  description

> Type: string

> Required: true

> Runner description

##  globalEnvFile

> Type: string|null

> Required: false

> Default: null

> Path to global file with env variables for add them into every commands processes

## globalEnv

> Type: array<string, string>|null

> Required: false

> Default: null

> Additional global environment variables which will be attached to every command process

## commands 

> Type: array<string, object>

> Required: true

> Commands list. 

### Commands block attributes

#### name

> Type: string

> Required: true

> Command name

####  description

> Type: string

> Required: true

> Command description

#### cmd 

> Type: string[]

> Required: true

> Command to start process. Each argument must be set as array element,.

```yaml
cmd:
    - "%php_cmd%"
    - "-dmemory_limit=-1"
    - "./vendor/bin/phpstan"
    - analyse
    - "-cphpstan.neon"
```

####  errorMsg

> Type: string|null

> Required: false

> Default: null

> Additional message for output 

####  envFile

> Type: string

> Required: false

> Default: null

> Path to file with env variables for add them into command process

#### env

> Type: array<string, string>

> Required: false

> Default: null

> Additional environment variables which will be attached to every command process

#### includeFiles

> Type: bool

> Required: false

> Default: false

> Hook will add to command start file list or skip this step if file list will be empty when option set to `true`

#### includeFilesPattern

> Type: array<string|int, string>

> Required: false

> Default: [php]

> Additional filter for files from diff.
> For every command you can use pattern in next format
> 
> `file_extension`: `start/path`
> 
> `file_extension` - extension which we need add as argument to command
> 
> `start/path` - file path without root directory for files filtering. 
> 
> Runner will add only files with needed extensions from all path which beginning from `start/path`
> 
> if you want to add all files with extension, add only extension with integer key

### Example filtering by path

```yaml
# We use variable in title.
name: "Pre-commit hook. Started in %project_dir%"
description: Pre-commit hook parallel execution
globalEnvFile: null
globalEnv:
  XDEBUG_MODE: off
commands:
  Phpstan:
    name: Phpstan all
    description: Phpstan for whole project
    cmd:
        - "%php_cmd%"
        - "-dmemory_limit=-1"
        - "./vendor/bin/phpstan"
        - analyse
        - "-cphpstan.neon"
    includeFiles: true
    includeFilesPattern:
      # Include all files
      0: php
    reverseOutput: true
```

```yaml
# We use variable in title.
name: "Pre-commit hook. Started in %project_dir%"
description: Pre-commit hook parallel execution
globalEnvFile: null
globalEnv:
  XDEBUG_MODE: off
commands:
  Phpstan:
    name: Phpstan tools
    description: Phpstan for tools directory
    cmd:
        - "%php_cmd%"
        - "-dmemory_limit=-1"
        - "./vendor/bin/phpstan"
        - analyse
        - "-cphpstan.neon"
    includeFiles: true
    includeFilesPattern:
      # Include only php files from tools directory
      php: tools
    reverseOutput: true
```

#### forceDisableAttachArgs

> Type: bool

> Required: false

> Default: false

> If you need only check existed files in diff set to `true` this option and file list from included pattern will not added

#### reverseOutput

> Type: bool

> Required: false

> Default: false

> Reverse output during formatting errors command.
> Some tools (phpstan for example) write normal
> output to pipe-stderr and error output to pipe-stdout.
> For phpstan we use this option for formatting messages
> during errors
