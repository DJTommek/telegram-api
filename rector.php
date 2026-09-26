<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

return RectorConfig::configure()
	->withSets([
//		\Rector\Set\ValueObject\SetList::PHP_74,
//		\Rector\Set\ValueObject\SetList::PHP_80,
//		\Rector\Set\ValueObject\SetList::PHP_81,
//		\Rector\Set\ValueObject\SetList::PHP_82,
//		\Rector\Set\ValueObject\SetList::PHP_83,
//		\Rector\Set\ValueObject\SetList::PHP_84,
//		\Rector\Set\ValueObject\SetList::PHP_85,
	])
    ->withPaths([
        __DIR__ . '/src',
    ])
	->withRules([
		\Rector\Php84\Rector\Param\ExplicitNullableParamTypeRector::class,
	])
    ->withTypeCoverageLevel(0)
    ->withDeadCodeLevel(0)
    ->withCodeQualityLevel(0)
;
