# Scattr Runner

### Configuration

Scattr Runner takes it's configuration from environment variables. Here's an example setup:

```sh
export SCATTR_USERNAME=joe
export SCATTR_PASSWORD=secret
export SCATTR_ACCOUNTNAME=demo
export SCATTR_POOLNAME=test
export SCATTR_URL="http://127.0.0.1:8080"
```

### Usage

runner.phar can be directly runned by
```sh
$ php runner.phar config_file.json
```

You can also write php script with content
```php
// somescript.php
<?php
if(file_exists('runner.pid'))
{
    $sPid = file_get_contents('runner.pid');
    if($sPid !== false)
    {
        exec("ps $sPid", $ProcessState);
        if (count($ProcessState) < 2)
        {
            $pid = shell_exec('nohup php runner.phar > runner.log 2>&1 & echo $!');
            if($pid)
            {
                file_put_contents('runner.pid', trim($pid));
            }
        }
    }
}
else
{
    $pid = shell_exec('nohup php runner.phar > runner.log 2>&1 & echo $!');
    if($pid)
    {
        file_put_contents('runner.pid', trim($pid));
    }
}
```

and put this script to cron, to find out whether runner.phar is running and if is not run it.


### Further developing

Phar file is building by Box https://github.com/box-project/box2.
Settings to build phar file is in box.json.

Box can be updated by
```sh
$ php box.phar update
```

to build run
```sh
$ php box.phar build -v
```

### Run examples
Run as application
```sh
$ cd example
$ php run_app.php jobs.json
```

Run as phar
```sh
$ cd example
$ php run_phar.php
```
