<?php

namespace Plugins\forms\controllers;

use \Plugins\forms\models\Form;
use \Plugins\forms\models\Field;

class FormsBackendController extends \BackendbaseController{

    var $layout = 'layouts.backend.laradmin';

    public function formslist($status=false){
        if($status!==false){
            $forms = Form::where('status','=',(int) $status)->paginate(get_config_value('paging'));
        }else{
            $forms = Form::whereIn('status',array(0,1,2))->paginate(get_config_value('paging'));
        }


        $this->setPageTitle('Forms - APP');
        $this->layout->content = \View::make('forms::backend/list')->withForms($forms);
    }

    public function exportformdata($formid){
        $form = Form::find($formid);
        if(is_null($form)){
            return \Redirect::back()->withMessage($this->notifyView(t('forms::messages.no_form_found'),'error'));
        }
        try{
            $rows = \DB::table($form->action_object)->paginate(get_config_value('paging'));
            $resp = '';
            foreach($rows as $k=>$v){
                foreach($v as $o=>$a){
                    if($k===0){
                        $resp .= "\t{$o}";
                    }
                }
            }
            $resp .= "\n";
            foreach($rows as $k=>$v){
                foreach($v as $o=>$a){

                        $resp .= "\t{$a}";

                }
                $resp .= "\n";
            }
            $headers = array(
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="ExportFileName.csv"'
            );
            return \Response::make($resp,200,$headers);
        }catch (\Exception $e){
            return \Redirect::back()->withMessage($this->notifyView($e->getMessage(),'danger'));
        }
    }

    /**
     * Shows the settings screen
     */
    public function settings(){
        add_breadcrumb(trans('forms::strings.settings'),url('backend/forms/settings'));
        $settings = \Settings::whereIn('namespace',array('forms','forms_templates'))->get();

        $this->setPageTitle('Form Settings - APP');
        $this->layout->content = \View::make('forms::backend/settings')->with('settings',$settings);
    }

    /**
     * Saves the system settings
     *
     * @return mixed
     */
    public function savesettings(){
        if(requested('id')){
            $settings = \Settings::find(requested('id'));
        }
        $settings->namespace = requested('namespace');
        $settings->setting_name = requested('setting_name');
        $settings->setting_value = requested('setting_value');
        $settings->autoload = (requested('autoload'))?requested('autoload'):0;

        if($settings->save()){
            return \Redirect::to('backend/forms/settings')->withMessage($this->notifyView(t('messages.settings_saved'),'success'));
        }
        return \Redirect::to('backend/forms/settings')->withMessage($this->notifyView(t('messages.error_occured'),'success'));
    }


    public function newform(){
        add_breadcrumb(trans('forms::strings.new_form'),url('backend/forms/new'));

        $form = new Form;
        $form->status = 3;
        $form->save();

        if(\Request::ajax()){
            return \View::make('forms::backend/new')->withForm($form);
        }
        $this->layout->content = \View::make('forms::backend/new')->withForm($form);
    }

    public function edit($formid){
        add_breadcrumb(trans('strings.edit'),url('backend/forms/edit'));
        $form = Form::find($formid);

        if(\Request::ajax()){
            return \View::make('forms::backend/new')->withForm($form);
        }
        $this->layout->content = \View::make('forms::backend/new')->withForm($form);
    }

    public function delete($formid){
        $form = Form::find($formid);
        if(is_null($form)){
            return \Redirect::back()->withMessage($this->notifyView(t('forms::messages.no_form_found'),'error'));
        }
        if($form->status==2){
            $form->delete();
            return \Redirect::back()->withMessage($this->notifyView(t('forms::messages.form_deleted'),'success'));
        }else{
            $form->status=2;
            $form->save();
            return \Redirect::back()->withMessage($this->notifyView(t('forms::messages.form_trushed'),'success'));
        }
    }
    public function restore($formid){
        $form = Form::find($formid);
        if(is_null($form)){
            return \Redirect::to('/backend/forms/list')->withMessage($this->notifyView(t('forms::messages.no_form_found'),'error'));
        }
        $form->status = 0;
        $form->save();
        return \Redirect::back()->withMessage($this->notifyView(t('forms::messages.form_restored'),'success'));
    }

    public function viewdbdata($table){
        add_breadcrumb(trans('forms::strings.forms'),url('backend/forms/list'));
        add_breadcrumb(trans('Data'),url('backend/forms/view/'));
        $form = Form::where('action_object','=',$table)->first();
        if(is_null($form)){
            return \Redirect::back()->withMessage($this->notifyView(t('forms::messages.form_not_found'),'danger'));
        }
        try{
            $rows = \DB::table($form->action_object)->paginate(get_config_value('paging'));
            $this->layout->content = \View::make('forms::backend/dbdata')->withRows($rows)->withForm($form);
        }catch (\Exception $e){
            return \Redirect::back()->withMessage($this->notifyView($e->getMessage(),'danger'));
        }
    }

    public function save(){
        $formid = (int) requested('formid');
        $title = requested('title');
        $slug = requested('slug');
        $content = requested('content');
        $fields = (requested('fields'))?requested('fields'):array();
        $newfields = (requested('newfields'))?requested('newfields'):array();
        $action = requested('form_action');
        $action_object = requested('action_object');

        if(is_array($action_object)){
            $action_object = implode(",",$action_object);
        }

        $success = requested('success_message');
        $failure = requested('failure_message');

        $params = requested('validator_params_fields');
        $newparams = requested('validator_params_newfields');


        $form = Form::find($formid);

        $form->title = $title;
        $form->content = $content;
        $form->action = $action;

        $form->success_message = ($success)?$success:'';
        $form->failure_message = ($failure)?$failure:'';

        if($action_object!=='default' && $action_object!=='default@email.com'){
            $form->action_object = $action_object;
        }


        $a = (trim($slug))?$form->slug = $slug:'';
        $a = (requested('status'))?$form->status = (int) requested('status'):$form->status = 0;

        if($action_object=='add_new' && $action=='forms/savetodb'){
            $action_object = 'form_'.$formid.'_data';
            $form->action_object = $action_object;
        }



        if($form->save()){

            //remove
            $form->fields()->delete();

            if($action=='forms/savetodb' && $action_object!='default' && !starts_with($action_object,'form_')){
                $form->action_object = $action_object;
                $form->save();
            }else{
                //and save the old fields
                foreach($fields as $k=>$field){
                    if(!trim($field['type'])){
                        continue;
                    }
                    $newfield = new Field;
                    $newfield->field_type = $field['type'];
                    $newfield->label = $field['label'];
                    $newfield->validator = $field['validator'];
                    $newfield->validator_params = (isset($params[$k]['validator']))?$params[$k]['validator']:'';
                    $newfield->field_value = $field['field_value'];
                    $newfield->default_value = $field['default_value'];
                    $newfield->required = (isset($field['required']))?1:0;
                    $newfield->status = 1;
                    $form->fields()->save($newfield);
                }

                foreach($newfields as $k=>$field){
                    if(!trim($field['type'])){
                        continue;
                    }
                    $newfield = new Field;
                    $newfield->field_type = $field['type'];
                    $newfield->label = $field['label'];
                    $newfield->validator = $field['validator'];
                    $newfield->validator_params = (isset($newparams[$k]['validator']))?$newparams[$k]['validator']:'';
                    $newfield->field_value = $field['field_value'];
                    $newfield->default_value = $field['default_value'];
                    $newfield->required = (isset($field['required']))?1:0;
                    $newfield->status = 1;
                    $form->fields()->save($newfield);
                }
                if($action=='forms/savetodb' && $action_object!='default'){
                    if (\Schema::hasTable($action_object)){
                        foreach($form->fields as $field){
                            if (!\Schema::hasColumn($action_object, 'form_field_'.\AnastasiaStr::slug($field->label)))
                            {
                                \Schema::table($action_object, function($table) use ($field)
                                {
                                    $table->string('form_field_'.\AnastasiaStr::slug($field->label));
                                });
                            }
                        }
                    }else{
                        \Schema::create($action_object, function($table) use ($form)
                        {
                            $table->increments('id');
                            foreach($form->fields as $field){

                                $table->string('form_field_'.\AnastasiaStr::slug($field->label));
                            }
                            $table->string('user_ip',50)->index('submit_ip_'.$form->id);
                            $table->timestamps();
                        });
                    }
                }
            }




            if(requested('saveclose')){
                return \Redirect::to('backend/forms/list')->withMessage($this->notifyView(t('forms::messages.form_saved')));
            }
            return \Redirect::to('backend/forms/edit/'.$form->id)->withMessage($this->notifyView(t('forms::messages.form_saved')));
        }
        return \Redirect::back()->withMessage($this->notifyView(t('messages.error_occured'),'danger'));
    }

}
