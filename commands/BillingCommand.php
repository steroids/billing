<?php

namespace steroids\billing\commands;

use steroids\billing\BillingModule;
use yii\console\Controller;
use yii\helpers\StringHelper;

/**
 * Class BillingCommand
 * @package steroids\billing\commands
 */
class BillingCommand extends Controller
{
    /**
     * @param string $names
     * @param string $force
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     */
    public function actionRates($names = 'all', $force = '0')
    {
        $names = $names === 'all' ? null : StringHelper::explode($names ?: '');
        $force = $force === '1';

        BillingModule::fetchRates($names, $force);
    }
}
