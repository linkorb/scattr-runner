# Scattr Runner

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
```

and put this script to cron, to find out whether runner.phar is running.


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

