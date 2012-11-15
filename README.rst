python-stanbicmm
================

A python library providing an interface to interact with the Stanbic Mobile Money service

Installing
==========

You can install python-stanbicmm via `PyPi`_. To install the latest stable version::

  $ pip install python-stanbicmm

.. _PyPi: http://pypi.python.org/pypi/python-stanbicmm

Usage
=====

Using python-stanbicmm in your project is very easy::

  import stanbic
  mm = stanbic.StanbicMM('2348012345678', '1111') # specify your account number and pin
  transactions = mm.get_transactions()
  specific_transaction = mm.get_transaction(txn_ref="012345")

License
=======

python-stanbicmm is free software, available under the BSD license.

Dependencies
============

* `Mechanize <http://wwwsearch.sourceforge.net/mechanize/>`_

