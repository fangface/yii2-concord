<?php

return array(

    1 => array(
        'entityId'          => 1,
        'sortOrder'         => 1,
        'attributeName'     => "field1",
        'dataType'          => "char",
        'length'            => 50,
        'defaultValue'      => "",
        'deleteOnDefault'   => 1,
    ),

    2 => array(
        'entityId'          => 1,
        'sortOrder'         => 2,
        'attributeName'     => "field2",
        'dataType'          => "char",
        'length'            => 50,
        'defaultValue'      => "",
        'deleteOnDefault'   => 1,
        'lazyLoad'          => 1,
    ),

    3 => array(
        'entityId'          => 1,
        'sortOrder'         => 3,
        'attributeName'     => "field3",
        'dataType'          => "char",
        'length'            => 50,
        'defaultValue'      => "",
        'deleteOnDefault'   => 1,
        'lazyLoad'          => 1,
    ),

    4 => array(
        'entityId'          => 1,
        'sortOrder'         => 4,
        'attributeName'     => "createdAt",
        'dataType'          => "datetime",
        'defaultValue'      => "0000-00-00 00:00:00",
        'deleteOnDefault'   => 1,
    ),

    5 => array(
        'entityId'          => 1,
        'sortOrder'         => 5,
        'attributeName'     => "createdBy",
        'dataType'          => "int",
        'length'            => 16,
        'unsigned'          => 1,
        'defaultValue'      => "0",
        'deleteOnDefault'   => 1,
    ),

    6 => array(
        'entityId'          => 1,
        'sortOrder'         => 6,
        'attributeName'     => "modifiedAt",
        'dataType'          => "datetime",
        'defaultValue'      => "0000-00-00 00:00:00",
        'deleteOnDefault'   => 1,
    ),

    7 => array(
        'entityId'          => 1,
        'sortOrder'         => 7,
        'attributeName'     => "modifiedBy",
        'dataType'          => "int",
        'length'            => 16,
        'unsigned'          => 1,
        'defaultValue'      => "0",
        'deleteOnDefault'   => 1,
    ),

);
