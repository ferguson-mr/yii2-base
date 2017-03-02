<?php

namespace ferguson\base\components;

use yii\base\Action;
use yii\base\InvalidConfigException;
use yii\db\ActiveRecord;
use yii\db\ActiveRecordInterface;
use yii\helpers\Json;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class EditableAction extends Action
{
    /**
     * @var \yii\db\ActiveRecord string class name of the model which will be handled by this action.
     * The model class must implement [[ActiveRecordInterface]].
     * This property must be set.
     */
    public $modelClass;
    /**
     * @var callable a PHP callable that will be called to return the model corresponding
     * to the specified primary key value. If not set, [[findModel()]] will be used instead.
     * The signature of the callable should be:
     *
     * ```php
     * function ($id, $action) {
     *     // $id is the primary key value. If composite primary key, the key values
     *     // will be separated by comma.
     *     // $action is the action object currently running
     * }
     * ```
     *
     * The callable should return the model found, or throw an exception if not found.
     */
    public $findModel;

    public $scenario = 'editable';

    /**
     * @inheritdoc
     */
    public function init()
    {
        if ($this->modelClass === null) {
            throw new InvalidConfigException(get_class($this) . '::$modelClass must be set.');
        }
    }

    /**
     * Runs the action.
     * This method displays the view requested by the user.
     * @throws NotFoundHttpException if the view file cannot be found
     */
    public function run($id)
    {
        $model = $this->findModel($id);

        $model->setScenario($this->scenario);
        // Check if there is an Editable ajax request
        if (isset($_POST['hasEditable'])) {
            \Yii::$app->response->format = Response::FORMAT_JSON;
            if ($model->load(\Yii::$app->request->post()) && $model->save()) {
                // return JSON encoded output in the below format
                return ['output'=>'', 'message' =>''];
            } else {
                return ['output' => '', 'message' => Json::htmlEncode($model->getFirstErrors())];
            }
        }
        return ['output'=>'', 'message' => \Yii::t('app', 'Error Parameters.')];
    }

    /**
     * Returns the data model based on the primary key given.
     * If the data model is not found, a 404 HTTP exception will be raised.
     * @param string $id the ID of the model to be loaded. If the model has a composite primary key,
     * the ID must be a string of the primary key values separated by commas.
     * The order of the primary key values should follow that returned by the `primaryKey()` method
     * of the model.
     * @return ActiveRecordInterface|ActiveRecord the model found
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function findModel($id)
    {
        if ($this->findModel !== null) {
            return call_user_func($this->findModel, $id, $this);
        }

        /* @var $modelClass ActiveRecordInterface */
        $modelClass = $this->modelClass;
        $keys = $modelClass::primaryKey();
        if (count($keys) > 1) {
            $values = explode(',', $id);
            if (count($keys) === count($values)) {
                $model = $modelClass::findOne(array_combine($keys, $values));
            }
        } elseif ($id !== null) {
            $model = $modelClass::findOne($id);
        }

        if (isset($model)) {
            return $model;
        } else {
            throw new NotFoundHttpException("Object not found: $id");
        }
    }
}