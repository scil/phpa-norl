# phpa-norl
PHP shell for Mac OS X and Windows

* **Homepage:** <http://www.fischerlaender.net/php/phpa-norl>

## License
phpa-norl is, like the original phpa, public domain. Feel free to do with it whatever you want.

## Install
1. `$ git clone https://github.com/jmagnusson/phpa-norl.git`
2. `$ mv ./phpa-norl/phpa-norl /usr/local/bin`
3. `$ rm -rf ./phpa-norl`

## How-To
1. `$ phpa-norl`

If you get `-bash: phpa-norl: command not found` you probably need to add `/usr/local/bin/` to the path environment variable. Add it with:

1. `echo 'export PATH="/usr/local/bin:$PATH"' >> ~/.profile``
2. `source ~/.profile`


# Usage

1. 'q' : exit phpa. related constant: __PHPA_EXIT_COMMAND
2. history command:
	1. 'h': echo recent input code.
	2. 'h1': run last input code again.
	3. 'h2': run the second last input code.
	4. related constant: __PHPA_MAX_HIST , __PHPA_HISTORY_COMMAND
3. hint
	* if you type '$_G'  => then click tab and enter  => then you can get hint '$_GET' .
	* related constants: __PHPA_HINT , __PHPA_HINT_STRICT , __PHPA_HINT_ONLYUSER
4. recover last session
	* related : __PHPA_LOG_INHERIT , history.txt , PHPALog
5. alias
	* if your return array('a'=>'1+1') in file 'aliases.php', then input '%a' in phpa shell, then you get '2'
6. config
	* you can define constants or other setting in config file 'include.php'.


there are useful comments at phpa-norl.php