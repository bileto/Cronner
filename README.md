# Cronner [![Build Status](https://travis-ci.org/stekycz/Cronner.svg?branch=master)](https://travis-ci.org/stekycz/Cronner)

- [Description](#description)
- [Usage](#usage)
- [Annotations](#annotations)
- [Author](#author)
- [License](#license)

## Description

Simple tool which helps with maintenance of cron tasks.

It requires **PHP >= 7.1.0** and **Nette Framework >= 2.4.0**.

## Usage

It is very simple to use it because configuration is only in method annotations. Example class with tasks follows.

```php
class CronTasks
{
    /**
     * @cronner-task E-mail sending
     * @cronner-period 1 day
     * @cronner-days working days
     * @cronner-time 23:30 - 05:00
     */
    public function sendEmails(): void
    {
        // Code which sends all your e-mails
    }


    /**
     * @cronner-task Important data replication
     * @cronner-period 3 hours
     */
    public function replicateImportantData(): void
    {
        // Replication code
    }
}
```

 It is recommend to use compiler extension.

```neon
extension:
    cronner: stekycz\Cronner\DI\CronnerExtension
```

It does not require any configuration however your own implementation of timestamp storage could be better
then the default storage. Your storage must be defined as a service in `config.neon` and Cronner will find it.
However you can specify service manually if it is not autowireable.

```neon
cronner:
    timestampStorage: myCoolTimestampStorage
```

Or you can change the directory for default storage.

```neon
cronner:
    timestampStorage: stekycz\Cronner\TimestampStorage\FileStorage(%wwwDir%/../temp/cronner)
```

It is also possible to define `maxExecutionTime` for Cronner so you do not have make it by you own code
(and probably for all your requests). Option `criticalSectionTempDir` can be change however the directory
must be writable for php process. It is used to run each task only once at time.

At the end you would need to specify your task objects. It would be some service with high probability.
You can add tag `cronner.tasks` to all services with Cronner tasks and those services will be bind
automatically. However you can still add new task objects by your own using `addTasks` method.

Then you can use it very easily in `Presenter`

```php
class CronPresenter extends \Nette\Application\UI\Presenter
{
    /**
     * @var \stekycz\Cronner\Cronner
     * @inject
     */
    public $cronner;

    
    public function actionCron(): void
    {
        $this->cronner->run();
    }
}
```

 or in `Command` from [Kdyby/Console](https://github.com/Kdyby/Console).

Service configuration is also possible but it **should not** be used using new versions of Nette because extension
usage is recommended and preferable way. However you will still need to call `run` method somewhere in your
`Presenter` or console `Command`.

```neon
services:
    cronner: stekycz\Cronner\Cronner(stekycz\Cronner\TimestampStorage\FileStorage(%wwwDir%/../temp/cronner))
    setup:
    	- addTasks(new CronTasks())
```

## Annotations

### @cronner-task

This annotations is **required** for all public methods which should be used as a task.
Its value is used as a name of task. If the value is missing the name is build from class name
and method name.

If this annotation is single (for Cronner) in task method comment then the task is run
every time when Cronner runs.

**Note:** Magic methods cannot be used as task (`__construct`, `__sleep`, etc.).

#### Example

```php
/**
 * @cronner-task Fetches all public data from all registered social networks
 */
```

### @cronner-period

Not required but recommended annotation which specifies period of task execution.
The period is minimal time between two executions of the task. It's value can be
anything what is acceptable for `strtotime()` function. The only restriction is usability
with "+" sign before the time because it is added by Cronner automatically. So `first day of this month`
is not acceptable however `1 month` is acceptable.

**Attention!** The value of this annotation must not contain any sign (+ or -).

#### Example

```php
/**
 * @cronner-period 1 day
 */
```

### @cronner-days

Allows run the task only on specified days. Possible values are abbreviations of week day names.
It means `Mon`, `Tue`, `Wed`, `Thu`, `Fri`, `Sat` and `Sun`. There are two shortcuts for easier usage:
`working days` (`Mon`, `Tue`, `Wed`, `Thu`, `Fri`) and `weekend` (`Sat` and `Sun`) which are internally
expanded to specific days. Multiple values must be separated by comma (`Mon, Wed, Fri`) or can be specified by range `Mon-Thu`.

#### Example

```php
/**
 * @cronner-days working days, Sun
 */
```

### @cronner-time

Specifies day time range (or ranges) in which the task can be run. It can be range or a specific minute.
It uses 24 hour time model. Multiple values must be separated by comma.

The time can be defined over midnight as it is in following example.

**Note:** There is tolerance time of 5 seconds to run task as soon as possible if previous run have had slower
start from any reason.

#### Example

```php
/**
 * @cronner-time 11:00, 23:30 - 05:00
 */
```
