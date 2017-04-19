<?php

namespace ferguson\base\behaviors;


use yii\base\Behavior;
use yii\base\InvalidParamException;
use yii\base\Model;
use yii\db\BaseActiveRecord;
use yii\validators\BooleanValidator;
use yii\validators\NumberValidator;
use yii\validators\StringValidator;

class AttributeTypecastBehavior extends Behavior
{
    const TYPE_JSON = 'json_code';
    const TYPE_IMPLODE = 'implode';
    const TYPE_SERIALIZE = 'serialize';
    const TYPE_INTEGER = 'integer';
    const TYPE_FLOAT = 'float';
    const TYPE_BOOLEAN = 'boolean';
    const TYPE_STRING = 'string';
    /**
     * @var array internal static cache for auto detected [[attributeTypes]] values
     * in format: ownerClassName => attributeTypes
     */
    private static $autoDetectedAttributeTypes = [];
    public $delimiter = ' ';
    /**
     * @var Model|BaseActiveRecord the owner of this behavior.
     */
    public $owner;
    /**
     * @var array attribute typecast map in format: attributeName => type.
     * Type can be set via PHP callable, which accept raw value as an argument and should return
     * typecast result.
     * For example:
     *
     * ```php
     * [
     *     'amount' => 'integer',
     *     'price' => 'float',
     *     'is_active' => 'boolean',
     *     'date' => function ($value) {
     *         return ($value instanceof \DateTime) ? $value->getTimestamp(): (int)$value;
     *     },
     * ]
     * ```
     *
     * If not set, attribute type map will be composed automatically from the owner validation rules.
     */
    public $attributeTypes;
    /**
     * @var bool whether to skip typecasting of `null` values.
     * If enabled attribute value which equals to `null` will not be type-casted (e.g. `null` remains `null`),
     * otherwise it will be converted according to the type configured at [[attributeTypes]].
     */
    public $skipOnNull = true;
    public $typecastBeforeValidate = false;
    /**
     * @var bool whether to perform typecasting after owner model validation.
     * Note that typecasting will be performed only if validation was successful, e.g.
     * owner model has no errors.
     * Note that changing this option value will have no effect after this behavior has been attached to the model.
     */
    public $typecastAfterValidate = true;
    /**
     * @var bool whether to perform typecasting before saving owner model (insert or update).
     * This option may be disabled in order to achieve better performance.
     * For example, in case of [[\yii\db\ActiveRecord]] usage, typecasting before save
     * will grant no benefit an thus can be disabled.
     * Note that changing this option value will have no effect after this behavior has been attached to the model.
     */
    public $typecastBeforeSave = false;
    /**
     * @var bool whether to perform typecasting after retrieving owner model data from
     * the database (after find or refresh).
     * This option may be disabled in order to achieve better performance.
     * For example, in case of [[\yii\db\ActiveRecord]] usage, typecasting after find
     * will grant no benefit in most cases an thus can be disabled.
     * Note that changing this option value will have no effect after this behavior has been attached to the model.
     */
    public $typecastAfterFind = false;

    /**
     * Clears internal static cache of auto detected [[attributeTypes]] values
     * over all affected owner classes.
     */
    public static function clearAutoDetectedAttributeTypes()
    {
        self::$autoDetectedAttributeTypes = [];
    }

    /**
     * @inheritdoc
     */
    public function attach($owner)
    {
        parent::attach($owner);

        if ($this->attributeTypes === null) {
            $ownerClass = get_class($this->owner);
            if (!isset(self::$autoDetectedAttributeTypes[$ownerClass])) {
                self::$autoDetectedAttributeTypes[$ownerClass] = $this->detectAttributeTypes();
            }
            $this->attributeTypes = self::$autoDetectedAttributeTypes[$ownerClass];
        }
    }

    /**
     * Composes default value for [[attributeTypes]] from the owner validation rules.
     * @return array attribute type map.
     */
    protected function detectAttributeTypes()
    {
        $attributeTypes = [];
        foreach ($this->owner->getValidators() as $validator) {
            $type = null;
            if ($validator instanceof BooleanValidator) {
                $type = self::TYPE_BOOLEAN;
            } elseif ($validator instanceof NumberValidator) {
                $type = $validator->integerOnly ? self::TYPE_INTEGER : self::TYPE_FLOAT;
            } elseif ($validator instanceof StringValidator) {
                $type = self::TYPE_STRING;
            }

            if ($type !== null) {
                foreach ((array)$validator->attributes as $attribute) {
                    $attributeTypes[$attribute] = $type;
                }
            }
        }
        return $attributeTypes;
    }

    /**
     * @inheritdoc
     */
    public function events()
    {
        $events = [];

        if ($this->typecastBeforeValidate) {
            $events[Model::EVENT_BEFORE_VALIDATE] = 'beforeValidate';
        }
        if ($this->typecastAfterValidate) {
            $events[Model::EVENT_AFTER_VALIDATE] = 'afterValidate';
        }
        if ($this->typecastBeforeSave) {
            $events[BaseActiveRecord::EVENT_BEFORE_INSERT] = 'beforeSave';
            $events[BaseActiveRecord::EVENT_BEFORE_UPDATE] = 'beforeSave';
        }
        if ($this->typecastAfterFind) {
            $events[BaseActiveRecord::EVENT_AFTER_FIND] = 'afterFind';
        }

        return $events;
    }

    /**
     * Typecast owner attributes according to [[attributeTypes]].
     * @param $event \yii\base\Event
     * @param array $attributeNames list of attribute names that should be type-casted.
     * If this parameter is empty, it means any attribute listed in the [[attributeTypes]]
     * should be type-casted.
     */
    public function typecastAttributes($event, $attributeNames = null)
    {
        $attributeTypes = [];

        if ($attributeNames === null) {
            $attributeTypes = $this->attributeTypes;
        } else {
            foreach ($attributeNames as $attribute) {
                if (!isset($this->attributeTypes[$attribute])) {
                    throw new InvalidParamException("There is no type mapping for '{$attribute}'.");
                }
                $attributeTypes[$attribute] = $this->attributeTypes[$attribute];
            }
        }

        foreach ($attributeTypes as $attribute => $type) {
            $value = $this->owner->{$attribute};
            if ($this->skipOnNull && $value === null) {
                continue;
            }
            if ($event->name == BaseActiveRecord::EVENT_AFTER_FIND) {
                $this->owner->{$attribute} = $this->typecastReverseValue($value, $type);
            } else {
                $this->owner->{$attribute} = $this->typecastValue($value, $type);
            }
        }
    }

    protected function typecastReverseValue($value, $type)
    {
        if (is_scalar($type)) {
            if (is_object($value) && method_exists($value, '__toString')) {
                $value = $value->__toString();
            }

            switch ($type) {
                case self::TYPE_INTEGER:
                    return (int)$value;
                case self::TYPE_FLOAT:
                    return (float)$value;
                case self::TYPE_BOOLEAN:
                    return (bool)$value;
                case self::TYPE_STRING:
                    return (string)$value;
                case self::TYPE_JSON:
                    return json_decode($value);
                case self::TYPE_IMPLODE:
                    return explode($this->delimiter, $value);
                case self::TYPE_SERIALIZE:
                    return unserialize($value);
                default:
                    throw new InvalidParamException("Unsupported type '{$type}'");
            }
        }

        return call_user_func($type, $value);
    }

    /**
     * Casts the given value to the specified type.
     * @param mixed $value value to be type-casted.
     * @param string|callable $type type name or typecast callable.
     * @return mixed typecast result.
     */
    protected function typecastValue($value, $type)
    {
        if (is_scalar($type)) {
            if (is_object($value) && method_exists($value, '__toString')) {
                $value = $value->__toString();
            }

            switch ($type) {
                case self::TYPE_INTEGER:
                    return (int)$value;
                case self::TYPE_FLOAT:
                    return (float)$value;
                case self::TYPE_BOOLEAN:
                    return (bool)$value;
                case self::TYPE_STRING:
                    return (string)$value;
                case self::TYPE_JSON:
                    return is_string($value) ? $value : json_encode($value);
                case self::TYPE_IMPLODE:
                    return is_string($value) ? $value : (string)implode($this->delimiter, $value);
                case self::TYPE_SERIALIZE:
                    return is_string($value) ? $value : serialize($value);
                default:
                    throw new InvalidParamException("Unsupported type '{$type}'");
            }
        }

        return call_user_func($type, $value);
    }

    /**
     * Handles owner 'afterValidate' event, ensuring attribute typecasting.
     * @param \yii\base\Event $event event instance.
     */
    public function afterValidate($event)
    {
        if (!$this->owner->hasErrors()) {
            $this->typecastAttributes($event);
        }
    }

    /**
     * Handles owner 'afterValidate' event, ensuring attribute typecasting.
     * @param \yii\base\Event $event event instance.
     */
    public function beforeValidate($event)
    {
        $this->typecastAttributes($event);
    }

    /**
     * Handles owner 'afterInsert' and 'afterUpdate' events, ensuring attribute typecasting.
     * @param \yii\base\Event $event event instance.
     */
    public function beforeSave($event)
    {
        $this->typecastAttributes($event);
    }

    /**
     * Handles owner 'afterFind' event, ensuring attribute typecasting.
     * @param \yii\base\Event $event event instance.
     */
    public function afterFind($event)
    {
        $this->typecastAttributes($event);
    }
}