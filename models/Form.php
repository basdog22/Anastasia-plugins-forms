<?php

namespace Plugins\forms\models;


use \Cviebrock\EloquentSluggable\SluggableInterface;
use \Cviebrock\EloquentSluggable\SluggableTrait;
use \Plugins\forms\Kris\LaravelFormBuilder\Form as Builder;
use \Plugins\forms\Kris\LaravelFormBuilder\FormBuilder;


class Form extends \Eloquent implements SluggableInterface
{
    use SluggableTrait;
    use \Venturecraft\Revisionable\RevisionableTrait;
    use \Translatable;

    protected $appends = ['actions', 'fields_available','validators','validation_rules'];

    /**
     * @var string
     */
    protected $table = 'forms';


    protected $translatable = array('title', 'content');
    /**
     * @var array
     */
    protected $sluggable = array(
        'build_from' => 'title',
        'save_to' => 'slug',
    );

    public function fields()
    {
        return $this->hasMany('\Plugins\forms\models\Field');
    }

    public static  function publishedlist($param1,$param2){
        return self::where('status','=',1)->lists($param1,$param2);
    }

    public function delete()
    {
        $this->fields()->delete();

        return parent::delete();
    }

    public function renderContent()
    {
        $content = $this->content;
        $oembed = new \AutoEmbed;
        $content = $oembed->parse($content);
        return $content;
    }

    public function getActionsAttribute()
    {
        $actions = \Event::fire('forms.collect.actions');
        $new = array();
        foreach($actions as $action){
           foreach($action as $k=>$v){
               $new[$k]=$v;
           }
        }
        return $new;
    }

    public function getValidationRulesAttribute(){
        return [
            'Age'   =>  ['minAge'=>13,'maxAge'=>60],
            'Alpha' =>  '',
            'Email' =>  '',
            'Phone' =>  '',
            'Contains'=> Field::find($this->active_field)->validator_params,
            'EndsWith'=> Field::find($this->active_field)->validator_params,
            'StartsWith'=> Field::find($this->active_field)->validator_params,
            'Equals'=> Field::find($this->active_field)->validator_params,
            'In'=> Field::find($this->active_field)->validator_params,
            'LeapDate'=> Field::find($this->active_field)->validator_params,
            'Max'=> Field::find($this->active_field)->validator_params,
            'Min'=> Field::find($this->active_field)->validator_params,
            'Multiple'=> Field::find($this->active_field)->validator_params,
            'PostalCode'=> Field::find($this->active_field)->validator_params,
            'Regex'=> Field::find($this->active_field)->validator_params,
            'Length'=> explode('-',Field::find($this->active_field)->validator_params),
        ];
    }

    public function getValidatorsAttribute(){
        return [
         ''    =>   t('strings.please_select'),
         'Age'=>  'Age',
         'Alnum'=>  'Alnum',
         'Alpha'=>  'Alpha',
         'Bool'=>  'Bool',
         'Charset'=>  'Charset',
         'Cnh'=>  'Cnh',
         'Cnpj'=>  'Cnpj',
         'Cntrl'=>  'Control Chars',
         'Consonant'=>  'Consonant',
         'Contains'=>  'Contains',
         'CountryCode'=>  'CountryCode',
         'Cpf'=>  'Cpf',
         'CreditCard'=>  'CreditCard',
         'Date'=>  'Date',
         'Digit'=>  'Digit',
         'Domain'=>  'Domain',
         'Email'=>  'Email',
         'EndsWith'=>  'EndsWith',
         'Equals'=>  'Equals',
         'Even'=>  'Even',
         'Exists'=>  'Exists',
         'False'=>  'False',
         'File'=>  'File',
         'Float'=>  'Float',
         'Graph'=>  'Graph',
         'HexRgbColor'=>  'HexRgbColor',
         'In'=>  'In',
         'Int'=>  'Int',
         'Ip'=>  'Ip',
         'Json'=>  'Json',
         'LeapDate'=>  'LeapDate',
         'LeapYear'=>  'LeapYear',
         'Length'=>  'Length',
         'Lowercase'=>  'Lowercase',
         'MacAddress'=>  'MacAddress',
         'Max'=>  'Max',
         'Min'=>  'Min',
         'Multiple'=>  'Multiple',
         'Negative'=>  'Negative',
         'NfeAccessKey'=>  'NfeAccessKey',
         'No'=>  'No',
         'NoWhitespace'=>  'NoWhitespace',
         'NotEmpty'=>  'NotEmpty',
         'Numeric'=>  'Numeric',
         'Odd'=>  'Odd',
         'PerfectSquare'=>  'PerfectSquare',
         'Phone'=>  'Phone',
         'Positive'=>  'Positive',
         'PostalCode'=>  'PostalCode',
         'PrimeNumber'=>  'PrimeNumber',
         'Prnt'=>  'Prnt',
         'Punct'=>  'Punct',
         'Readable'=>  'Readable',
         'Regex'=>  'Regex',
         'Roman'=>  'Roman',
         'Slug'=>  'Slug',
         'Space'=>  'Space',
         'StartsWith'=>  'StartsWith',
         'String'=>  'String',
         'Tld'=>  'Tld',
         'True'=>  'True',
         'Uploaded'=>  'Uploaded',
         'Uppercase'=>  'Uppercase',
         'Url'=>  'Url',
         'Version'=>  'Version',
         'Vowel'=>  'Vowel',
         'Writable'=>  'Writable',
         'Xdigit'=>  'Xdigit',
         'Yes'=>  'Yes',
        ];
    }

    public function getFieldsAvailableAttribute()
    {
        return [
            ''  =>  t('strings.please_select'),
            'text' => t('forms::strings.textfield'),
            'email' => t('forms::strings.email'),
            'url' => t('forms::strings.url'),
            'tel' => t('forms::strings.tel'),
            'search' => t('forms::strings.search'),
            'password' => t('forms::strings.password'),
            'hidden' => t('forms::strings.hidden'),
            'number' => t('forms::strings.number'),
            'date' => t('forms::strings.date'),
            'textarea' => t('forms::strings.textarea'),
            'submit' => t('forms::strings.submit'),
            'reset' => t('forms::strings.reset'),
            'button' => t('forms::strings.button'),
            'file' => t('forms::strings.file'),
            'image' => t('forms::strings.image'),
            'select' => t('forms::strings.select'),
            'selectmultiple' => t('forms::strings.select_multiple'),
            'checkbox' => t('forms::strings.checkbox'),
            'radio' => t('forms::strings.radio'),
            'choice' => t('forms::strings.choice'),
            'color' => t('forms::strings.color'),
            'datetime-local' => t('forms::strings.datetime_local'),
            'month' => t('forms::strings.month'),
            'range' => t('forms::strings.range'),
            'time' => t('forms::strings.time'),
            'week' => t('forms::strings.week'),
            'form' => t('forms::strings.form'),
            'collection' => t('forms::strings.collection'),
            'repeated' => t('forms::strings.repeated'),
            'static' => t('forms::strings.static')
        ];
    }



    public function setFormHelper($formhelper){
        $this->formHelper = $formhelper;
    }
    public function setFormBuilder($formbuilder){
        $this->formBuilder = $formbuilder;
    }


    /**
     * @return mixed|string
     */
    public function __toString()
    {

        \Session::put('currentform',$this->id);
        $builder = new FormBuilder(app(),app('laravel-form-helper'));

        if($this->action=='forms/survey'){
            \Session::forget('in_survey');
            \Session::put('in_survey',true);
            \Session::forget('survey_chain');
            \Session::put('survey_chain',explode(",",$this->action_object));
        }

        if(\Session::has('in_survey')){
            if(in_array($this->id,\Session::get('survey_chain'))){
                $this->action = 'forms/survey';
            }
        }


        $form = $builder->create('\Plugins\forms\models\AbstractForm',[
            'method' => $this->form_method,
            'url' => url($this->action)
        ]);




        return form($form);

    }

    public function save(array $options = array())
    {

        $this->title = ($this->title) ? $this->title : '';
        $this->content = ($this->content) ? $this->content : '';
        $this->action = ($this->action) ? $this->action : '';
        $this->form_method = ($this->form_method) ? $this->form_method : 'POST';
        $this->action_object = ($this->action_object)? $this->action_object:'';
        $this->status = ($this->status) ? $this->status : 0;
        $this->success_message = ($this->success_message)?$this->success_message:'';
        $this->failure_message = ($this->failure_message)?$this->failure_message:'';



        return parent::save($options);
    }

}
