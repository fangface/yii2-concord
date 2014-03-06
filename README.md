Yii2 - Concord
==============

Yii2 - Concord is a work in progress. This is a project exploring the use of GitHub, Travis CI, Scrutinizer CI, Packagist, PHPUnit and Yii2.  The project so far mostly consists of extensions to the Yii2 Active Record implementation as well as a database connection manager.  At the time of writing, Yii2 is still a moving target and very much in Alpha status.

[![Build Status](https://travis-ci.org/fangface/yii2-concord.png?branch=master)](https://travis-ci.org/fangface/yii2-concord)
[![Code Coverage](https://scrutinizer-ci.com/g/fangface/yii2-concord/badges/coverage.png?s=79d37b83797710c3bd2cf9ed14b84d0c85928c6f)](https://scrutinizer-ci.com/g/fangface/yii2-concord/)
[![Scrutinizer Quality Score](https://scrutinizer-ci.com/g/fangface/yii2-concord/badges/quality-score.png?s=6e58c479f62d699de6877e28e7fed8e5dcd51354)](https://scrutinizer-ci.com/g/fangface/yii2-concord/)

Installation
------------

The preferred way to install the Yii framework is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist "fangface/yii2-concord *"
```

or add

```json
"fangface/yii2-concord": "*"
```

to the require section of your composer.json.

Example Usage
-------------

With appropriate models in place you are then able to work with hasOne, hasMany and hasEav relations similar to the following;

```php
$customer = new Customer();

$customer->field1 = 'Field1';
$customer->field2 = 'Field2';

$customer->address->setAttributes(array(
    'title'       => 'Mr',
    'forename'    => 'A',
    'surname'     => 'Sample',
    'jobTitle'    => 'Job',
    'company'     => 'Company',
    'address1'    => 'Address1',
    'address2'    => 'Address2',
    'address3'    => 'Address3',
    'city'        => 'City',
    'region'      => 'Region',
    'countryCode' => 'GBR',
),false);

$customer->phone->telno = '0123456789';

$customer->customerAttributes->field1 = 'CAField1';
$customer->customerAttributes->field2 = 'CAField2';

$newOrder = new Order(array(
    'field1' => 'Order-Field-1',
    'field2' => 'Order-Field-2',
    'field3' => 'Order-Field-3',
));

// add new item
$newItem = new Item();
$newItem->productCode = 'CODE1';
$newItem->quantity    = 3;
$newItem->totalValue  = 3.36;
$newItem->field1      = 'Item-Field-1';
$newItem->field2      = 'Item-Field-2';
$newItem->field3      = 'Item-Field-3';
$newOrder->items[] = $newItem;

// add new item
$newOrder->items[] = new Item(array(
    'productCode'   => 'CODE2',
    'quantity'      => 2,
    'totalValue'    => 4.80,
    'field1'        => 'Item-Field-1',
    'field2'        => 'Item-Field-2',
    'field3'        => 'Item-Field-3',
));

// add new item
$newOrder->items['abc']->productCode = 'CODE3';
$newOrder->items['abc']->quantity'   = 1;
$newOrder->items['abc']->totalValue' = 3.20;
$newOrder->items['abc']->field1'     = 'Item-Field-1';
$newOrder->items['abc']->field2'     = 'Item-Field-2';
$newOrder->items['abc']->field3'     = 'Item-Field-3';

// add new item
$newOrder->items['xyz'] = new Item(array(
    'productCode'   => 'POST',
    'quantity'      => 1,
    'totalValue'    => 3.98,
    'field1'        => 'Item-Field-1',
    'field2'        => 'Item-Field-2',
    'field3'        => 'Item-Field-3',
));

$customer->orders[] = $newOrder;

$customer->saveAll();
```

All linking attributes will have been taken care of;

```php
print_r($customer->allToArray());
```

```
Array
(
    [1] => Array
        (
            [id] => 1
            [addressId] => 1
            [phoneId] => 1
            [createdAt] => 2050-12-31 23:59:59
            [createdBy] => 99
            [modifiedAt] => 2050-12-31 23:59:59
            [modifiedBy] => 99
            [customerAttributes] => Array
                (
                    [field1] => Field1
                    [field2] => Field2
                    [createdAt] => 2050-12-31 23:59:59
                    [createdBy] => 99
                    [modifiedAt] => 2050-12-31 23:59:59
                    [modifiedBy] => 99
                )

            [address] => Array
                (
                    [id] => 1
                    [customerId] => 1
                    [title] => Mr
                    [forename] => A
                    [surname] => Sample
                    [jobTitle] => Job
                    [company] => Company
                    [address1] => Address1
                    [address2] => Address2
                    [address3] => Address3
                    [city] => City
                    [region] => Region
                    [countryCode] => GBR
                    [createdAt] => 2050-12-31 23:59:59
                    [createdBy] => 99
                    [modifiedAt] => 2050-12-31 23:59:59
                    [modifiedBy] => 99
                    [country] => Array
                        (
                            [id] => 1
                            [countryCode] => GBR
                            [shortName] => UK
                            [longName] => United Kingdom
                            [createdAt] => 2050-12-31 23:59:59
                            [createdBy] => 99
                            [modifiedAt] => 2050-12-31 23:59:59
                            [modifiedBy] => 99
                        )

                )

            [phone] => Array
                (
                    [id] => 1
                    [customerId] => 1
                    [telno] => 0123456789
                )

            [orders] => Array
                (
                    [1] => Array
                        (
                            [id] => 1
                            [customerId] => 1
                            [field1] => Order-Field-1
                            [field2] => Order-Field-2
                            [field3] => Order-Field-3
                            [createdAt] => 2050-12-31 23:59:59
                            [createdBy] => 99
                            [modifiedAt] => 2050-12-31 23:59:59
                            [modifiedBy] => 99
                            [items] => Array
                                (
                                    [1] => Array
                                        (
                                            [id] => 1
                                            [customerId] => 1
                                            [orderId] => 1
                                            [productCode] => CODE1
                                            [quantity] => 3
                                            [totalValue] => 3.36
                                            [field1] => Item-Field-1
                                            [field2] => Item-Field-2
                                            [field3] => Item-Field-3
                                            [product] => Array
                                                (
                                                    [id] => 1
                                                    [productCode] => CODE1
                                                    [description] => Description for productCode CODE1
                                                    [createdAt] => 2050-12-31 23:59:59
                                                    [createdBy] => 99
                                                    [modifiedAt] => 2050-12-31 23:59:59
                                                    [modifiedBy] => 99
                                                )

                                        )

                                    [2] => Array
                                        (
                                            [id] => 2
                                            [customerId] => 1
                                            [orderId] => 1
                                            [productCode] => CODE2
                                            [quantity] => 2
                                            [totalValue] => 4.8
                                            [field1] => Item-Field-1
                                            [field2] => Item-Field-2
                                            [field3] => Item-Field-3
                                            [product] => Array
                                                (
                                                    [id] => 2
                                                    [productCode] => CODE2
                                                    [description] => Description for productCode CODE2
                                                    [createdAt] => 2050-12-31 23:59:59
                                                    [createdBy] => 99
                                                    [modifiedAt] => 2050-12-31 23:59:59
                                                    [modifiedBy] => 99
                                                )

                                        )

                                    [3] => Array
                                        (
                                            [id] => 3
                                            [customerId] => 1
                                            [orderId] => 1
                                            [productCode] => CODE3
                                            [quantity] => 1
                                            [totalValue] => 3.2
                                            [field1] => Item-Field-1
                                            [field2] => Item-Field-2
                                            [field3] => Item-Field-3
                                            [product] => Array
                                                (
                                                    [id] => 3
                                                    [productCode] => CODE3
                                                    [description] => Description for productCode CODE3
                                                    [createdAt] => 2050-12-31 23:59:59
                                                    [createdBy] => 99
                                                    [modifiedAt] => 2050-12-31 23:59:59
                                                    [modifiedBy] => 99
                                                )

                                        )

                                    [4] => Array
                                        (
                                            [id] => 4
                                            [customerId] => 1
                                            [orderId] => 1
                                            [productCode] => POST
                                            [quantity] => 1
                                            [totalValue] => 3.98
                                            [field1] => Item-Field-1
                                            [field2] => Item-Field-2
                                            [field3] => Item-Field-3
                                            [product] => Array
                                                (
                                                    [id] => 4
                                                    [productCode] => POST
                                                    [description] => Postage and Packaging
                                                    [createdAt] => 2050-12-31 23:59:59
                                                    [createdBy] => 99
                                                    [modifiedAt] => 2050-12-31 23:59:59
                                                    [modifiedBy] => 99
                                                )

                                        )

                                )

                        )

                )

        )

)
```
