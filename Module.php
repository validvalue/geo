<?php

namespace validvalue\geo;

use yii;
use yii\base\Module as BaseModule;

/**
 * This is the main module class for the team.
 *
 * @property array $modelMap
 *
 * @author Pavel Aleksandrov <inblank@yandex.ru>
 */
class Module extends BaseModule
{
    const VERSION = '0.1.0';

    /** @var array Model map */
    public $modelMap = [];

    /**
     * @var string The prefix for geo module URL.
     *
     * @See [[GroupUrlRule::prefix]]
     */
    public $urlPrefix = 'geo';

    /** @var array The rules to be used in URL management. */
    public $urlRules = [
    ];

    /**
     * Default Geo object if location not found.
     * MUST BE ARE CITY IDENTIFIER
     * @var int
     */
    public $defaultGeo;
}
