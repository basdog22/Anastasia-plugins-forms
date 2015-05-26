<?php

namespace Plugins\forms\controllers;

use \Plugins\forms\models\Form;
use \Plugins\forms\models\Field;
use \Respect\Validation\Exceptions\ValidationExceptionInterface;

class FormsFrontendController extends \FrontendbaseController{


    public function __construct(){
        $this->config = forms_get_config();
        $this->config['defaults'] = forms_get_config_default();
    }

    public function showpage($post){
        add_breadcrumb(get_config_value('brand'), url('/'));
        if($post instanceof \Plugins\forms\models\Form){

        }else{
            $postid = (int) $post;
            $post = \Plugins\forms\models\Form::find($postid);

        }
        add_breadcrumb($post->title, url(\URL::current()));
        \Session::forget('gridsrun');
        $this->loadLayout($post,'forms::frontend/page',$this->config['mainpage_layout']);
        $this->setPageTitle($post->title);
//        $this->layout->content =  \View::make('page_template')->withPage($page);

    }

    public function managesurvey(){
        $formid = (int) requested('form_id');
        $form = Form::find($formid);

        if($errors = $this->checkForm($form)){
            return \Redirect::back()->withMessage(t('forms::messages.form_has_errors'))->withInput()->withErrors($errors);
        }
        if(\Session::has('survey_data')){
            $formdata = \Session::get('survey_data');
        }else{
            $formdata = array();
        }
        $formdata[$form->id] = requested();
        \Session::forget('survey_data');
        \Session::put('survey_data',$formdata);

        $next = $formid;
        //get the next form to show. If any. Else we save the data and inform
        $chain = \Session::get('survey_chain');
        foreach($chain as $k=>$v){

            if($v==$formid && isset($chain[$k+1])){
                $next = $chain[$k+1];
            }
        }

        if($next==$formid){
            $survey = $this->saveSurveyData();
            $message = (trim($survey->success_message))?$survey->success_message:t('forms::messages.form_submited_thanks');
            \Session::forget('survey_data');
            \Session::forget('survey_chain');
            \Session::forget('in_survey');
            if(starts_with($message,'http')){
                return \Redirect::to($message);
            }else{
                return \Redirect::to('/')->withMessage($message);
            }
        }else{
            $nextform = Form::find($next);
            return \Redirect::to($nextform->slug);
        }
    }


    function saveSurveyData(){
        $submited = array();
        foreach(\Session::get('survey_data') as $data){
            $form = (int)$data['form_id'];
            $form = Form::find($form);
            foreach($form->fields as $field){
                $submited[($field->label)?$field->label:$field->id] = $data['forms_field_'.$field->id];
            }
        }
        $form = array();
        foreach($submited as $k=>$v){
            $form[$k] = $v;
        }
        $chain = \Session::get('survey_chain');
        $survey_id = reset($chain);
        $survey = Form::find($survey_id);
        \Mail::send('forms::emails.sendmail', array('form' => $survey,'data'=>$form), function ($message){
            $message->from(get_config_value('admin_email'), get_config_value('brand'));
            $message->to(get_config_value('admin_email'))->subject(t('forms::strings.form_submition'));
        });

        return $survey;
    }

    public function savetodb(){
        $formid = (int) requested('form_id');
        $form = Form::find($formid);

        if($errors = $this->checkForm($form)){
            return \Redirect::back()->withMessage(t('forms::messages.form_has_errors'))->withInput()->withErrors($errors);
        }
        $formdata = array();
        if($form->action=='forms/savetodb' && !starts_with($form->action_object,"form_")){
            $columns = get_table_columns($form->action_object);
            foreach($columns as $field){
                $value = requested($field->Field);
                if(trim($value)){
                    $formdata[$field->Field] = $value;
                }else{
                    $formdata[$field->Field] = $field->Field;
                }
            }
        }else{
            foreach($form->fields as $field){
                $value = requested('forms_field_'.$field->id);
                if(trim($value)){
                    $formdata['form_field_'.\AnastasiaStr::slug($field->label)] = $value;
                }else{
                    $formdata['form_field_'.\AnastasiaStr::slug($field->label)] = $field->label;
                }
            }
            $formdata['user_ip'] = getenv ( "REMOTE_ADDR" );
        }


        try{
            if(\DB::table('form_'.$formid.'_data')->where('user_ip','=',$formdata['user_ip'])->count()){
                $formdata['updated_at'] = \Carbon\Carbon::now()->toDateTimeString();
                \DB::table('form_'.$formid.'_data')->where('user_ip','=',$formdata['user_ip'])->update($formdata);
            }else{
                $formdata['created_at'] = $formdata['updated_at'] = \Carbon\Carbon::now()->toDateTimeString();
                \DB::table('form_'.$formid.'_data')->insert($formdata);
            }
            $message = (trim($form->success_message))?$form->success_message:t('forms::messages.form_submited_thanks');
            if(starts_with($message,'http')){
                return \Redirect::to($message);
            }else{
                return \Redirect::back()->withMessage($message);
            }

        }catch (\Exception $e){
            $message = (trim($form->failure_message))?$form->failure_message:t('messages.error_occured').' - '.$e->getMessage();
            if(starts_with($message,'http')){
                return \Redirect::to($message);
            }else{
                return \Redirect::back()->withMessage($message)->withInput();
            }
        }

    }

    public function sendmail(){


        $formid = (int) requested('form_id');
        $form = Form::find($formid);

        if($errors = $this->checkForm($form)){
            return \Redirect::back()->withMessage(t('forms::messages.form_has_errors'))->withInput()->withErrors($errors);
        }
        $formdata = array();
        foreach($form->fields as $field){
            $value = requested('forms_field_'.$field->id);
            if(trim($value)){
                $formdata[$field->label] = $value;
            }
        }
        \Mail::send('forms::emails.sendmail', array('form' => $form,'data'=>$formdata), function ($message) use ($form){
            $message->from(get_config_value('admin_email'), get_config_value('brand'));
            $message->to($form->action_object)->subject(t('forms::strings.form_submition'));
        });

        $message = (trim($form->success_message))?$form->success_message:t('forms::messages.form_submited_thanks');
        if(starts_with($message,'http')){
            return \Redirect::to($message);
        }else{
            return \Redirect::back()->withMessage($message);
        }
    }

    private function checkForm($form){

        if(is_null($form)){
            return \Redirect::back()->withMessage(t('forms::messages.no_form_found'));
        }

        $fields = $form->fields;
        $errors = array();
        foreach($fields as $field){
            $checkfield = requested('forms_field_'.$field->id);
            $form->active_field = $field->id;
            //do we have a validator for this field?
            if(trim($field->validator) && isset($form->validation_rules[$field->validator])){
                $validator = trim($field->validator);
                $options = $form->validation_rules[$validator];
                if(is_array($options)){
                    try{
                        \RespectValidator::NotEmpty()->$validator(extract($options))->check($checkfield);
                    }catch (ValidationExceptionInterface $e){
                        $errors[$field->label] = $e->getMessage();
                    }
                }else{
                    try{
                        \RespectValidator::NotEmpty()->$validator($options)->check($checkfield);
                    }catch(ValidationExceptionInterface $e){
                        $errors[$field->label] = $e->getMessage();
                    }
                }

            }
        }
        if(count($errors)){
            return $errors;
        }
    }
}