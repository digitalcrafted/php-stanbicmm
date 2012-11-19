php-stanbicmm
================

A php port of the [python library] (https://github.com/timbaobjects/python-stanbicmm) providing an interface to interact with the [Stanbic Mobile Money service] (http://mobilemoney.stanbic.com/)

Usage
=====

The usage is as simple as this

	try {
		$mm = new StanbicMM('2348181019104', '0000');
		print_r($mm->get_transactions());
	} catch(SyntaxException $e) {
		echo 'Syntax ish :/';
	} catch(Exception $e) {
		echo $e;
	}

License
=======

php-stanbicmm is free software, available under the BSD license.

Bugs
============

Send bugs to [@digitalcraft] (http://twitter.com/digitalcraft), [@kehers] (http://twitter.com/kehers), [@takinbo] (http://twitter.com/takinbo)