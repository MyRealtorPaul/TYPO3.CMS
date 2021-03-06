
.. include:: ../../Includes.txt

==============================================
Breaking: #77460 - Extbase query cache removed
==============================================

See :issue:`77460`

Description
===========

The PHP-based query cache functionality within the Extbase persistence layer has been removed.

The following public methods within the Extbase persistence layer have been removed:
 * `Typo3DbBackend->quoteTextValueCallback()`
 * `Typo3DbBackend->initializeObject()`
 * `Typo3DbBackend->injectCacheManager()`
 * Interface definition in `QuerySettingsInterface->getUseQueryCache()`


Impact
======

The according cache configuration set via `$TYPO3_CONF_VARS[SYS][cache][cacheConfigurations][extbase_typo3dbbackend_queries]` has no effect anymore.


Affected Installations
======================

Any installation effectively relying on the query cache via a third party extension or explicitly deactivating the query cache of extbase.


Migration
=========

Remove the according lines and migrate to Doctrine.

.. index:: Database, PHP-API, LocalConfiguration, ext:extbase