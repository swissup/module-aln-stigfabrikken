# module-aln-stigfabrikken


###### For maintainers

```bash
cd <magento_root>
composer config repositories.swissup composer https://docs.swissuplabs.com/packages/
composer require swissup/module-aln-stigfabrikken:dev-master --prefer-source --ignore-platform-reqs
bin/magento module:enable Swissup_Core Swissup_Ajaxlayerednavigation Swissup_AlnStigfabrikken
bin/magento setup:upgrade
bin/magento setup:di:compile
```
