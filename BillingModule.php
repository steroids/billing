<?php

namespace steroids\billing;

use steroids\billing\operations\BaseOperation;
use steroids\billing\operations\ManualOperation;
use steroids\billing\models\BillingAccount;
use steroids\billing\models\BillingCurrency;
use steroids\billing\models\BillingOperation;
use steroids\billing\rates\BaseRate;
use steroids\core\base\Enum;
use steroids\core\base\Module;
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;

class BillingModule extends Module
{
    const OPERATION_MANUAL = 'manual';

    const EVENT_OPERATION_EXECUTE = 'operation_execute';

    /**
     * @var array|string
     */
    public $userAccountNameEnum = 'app\\billing\\enums\\UserAccountName';

    /**
     * @var array|string
     */
    public $systemAccountNameEnum = 'app\\billing\\enums\\SystemAccountName';

    /**
     * @var array
     */
    public array $classesMap = [
        'steroids\billing\models\BillingAccount' => BillingAccount::class,
        'steroids\billing\models\BillingCurrency' => BillingCurrency::class,
        'steroids\billing\models\BillingOperation' => BillingOperation::class,
    ];

    /**
     * @var array
     */
    public array $operationsMap = [
        self::OPERATION_MANUAL => ManualOperation::class,
    ];

    /**
     * @var array|BaseRate[]
     */
    public array $rates = [];

    /**
     * Maximum rate changes in percent for fix bad api results
     * @var int
     */
    public int $rateMaxDeviationPercent = 15;

    /**
     * @return array[]
     */
    public static function cron()
    {
        return [
            [
                'handler' => [static::class, 'fetchRates'],
                'expression' => '15 * * * *', // Every hour at 15 min
            ],
        ];
    }

    /**
     * Remote fetch rates via rate providers
     * @param array|null $names
     * @param bool $skipValidation
     * @throws InvalidConfigException
     */
    public static function fetchRates(array $names = null, bool $skipValidation = false)
    {
        $module = static::getInstance();
        foreach ($module->rates as $name => $rate) {
            // Filter by names
            if ($names && !in_array($name, $names)) {
                continue;
            }

            // Lazy create rates fetcher
            if (is_string($rate) || is_array($rate)) {
                $module->rates[$name] = $rate = \Yii::createObject($rate);
            }

            // Fetch key-value rates
            $rates = $rate->fetch();

            // Save
            /** @var BillingCurrency $currencyClass */
            $currencyClass = static::resolveClass(BillingCurrency::class);
            $currencyClass::updateRates($rates, $skipValidation);
        }
    }

    /**
     * @return string[]
     * @throws InvalidConfigException
     */
    public function getUserAccountNames()
    {
        return $this->resolveEnumValues($this->userAccountNameEnum);
    }

    /**
     * @return string[]
     * @throws InvalidConfigException
     */
    public function getSystemAccountNames()
    {
        return $this->resolveEnumValues($this->systemAccountNameEnum);
    }

    /**
     * @param string $name
     * @return BaseRate
     * @throws InvalidConfigException
     */
    public function getRateProvider(string $name)
    {
        if (!isset($this->rates[$name])) {
            throw new InvalidConfigException('Not found rate provider "' . $name . '"');
        }
        if (is_array($this->rates[$name])) {
            $this->rates[$name] = \Yii::createObject($this->rates[$name]);
        }
        return $this->rates[$name];
    }

    /**
     * @param string $name
     * @return BaseOperation|string
     */
    public function getOperationClass(string $name)
    {
        return ArrayHelper::getValue($this->operationsMap, $name);
    }

    /**
     * @param string $className
     * @return int|string
     * @throws InvalidConfigException
     * @throws \yii\base\Exception
     */
    public function getOperationName(string $className)
    {
        foreach (BillingModule::getInstance()->operationsMap as $name => $cn) {
            if (trim($className, '\\') === trim($cn, '\\')) {
                return $name;
            }
        }

        throw new InvalidConfigException('Not fount operation name by class: ' . static::class);
    }

    /**
     * @param string|array $enum
     * @return array|string[]
     * @throws InvalidConfigException
     */
    protected function resolveEnumValues($enum)
    {
        if (is_string($enum)) {
            /** @var Enum $enumClass */
            $enumClass = $enum;
            if (!class_exists($enumClass)) {
                throw new InvalidConfigException('Not found account name enum: ' . $enumClass);
            }

            return $enumClass::getKeys();
        }

        if (is_array($enum)) {
            return ArrayHelper::isAssociative($enum) ? array_keys($enum) : array_values($enum);
        }

        throw new InvalidConfigException('Wrong "accountNameEnum" format, need enum class or array.');
    }
}
