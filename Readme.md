# Installation

1) Composer install
```shell
composer require koempf/akeneo-attributes-to-variants-bundle
```

2) Add bundle to config/bundles.php
```php
<?php

return [
    \Koempf\AttributeToVariantsBundle\KoempfAttributeToVariantsBundle::class => ['dev' => true, 'test' => true, 'prod' => true],
];
```

# Usage

Example:
```shell
php bin/console koempf:attribute-to-variants:add --attribute=attribute_code --families=family_code1,family_code2,family_code3
php bin/console koempf:attribute-to-variants:add --attribute=attribute_code --families='*'
```
