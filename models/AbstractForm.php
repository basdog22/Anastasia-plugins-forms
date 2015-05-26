<?php namespace Plugins\forms\models;

use \Plugins\forms\Kris\LaravelFormBuilder\Form as KrisForm;

class AbstractForm extends KrisForm
{

    public function buildForm()
    {
        $form = Form::find(\Session::get('currentform'));
        $nextbtn=false;
        if(\Session::has('in_survey')){
            if(in_array($form->id,\Session::get('survey_chain'))){
                $nextbtn=true;
            }
        }


        if($form->action=='forms/savetodb' && !starts_with($form->action_object,"form_")){
            $columns = get_table_columns($form->action_object);

            foreach($columns as $k=>$column){

                $options = [
                    'attr' => ['placeholder' => $column->Field],
                    'label' => $column->Field
                ];
                $coltype = preg_replace("#\(\d{1,}\)#",'',$column->Type);
                $coltype = explode(" ",$coltype);
                $coltype = $coltype[0];
                $coltype = trim($coltype);

                switch($coltype){
                    case "varchar":
                        $type = 'text';
                        break;
                    case "text":
                        $type = 'textarea';
                        break;
                    case "int":
                        $type = 'number';
                        break;
                    case "timestamp":
                        $type = 'datetime-local';
                        break;
                    default:
                        $type = 'text';
                }
                if($column->Null=='NO'){
                    $options['attr']['required'] = 'required';
                }

                try{
                    if($column->Extra!=='auto_increment' && $column->Field!=='created_at' && $column->Field!=='updated_at'){
                        $this->add($column->Field,$type,$options);
                    }
                }catch (\Exception $e){
                    ll($e->getMessage());
                }
            }
            if(!$nextbtn){
                $this->add('submit','submit');
            }

        }else{
            foreach($form->fields as $field){
                $options = [
                    'help_block' => [
                        'text' => $field->field_value,  // If text is set, automatically adds help text under the field. Default: null
                    ],
                    'attr' => ['placeholder' => $field->field_value],
                    'label' => $field->label
                ];
                switch($field->field_type){
                    case "select":
                        if(class_exists($field->default_value)){
                            $model=$field->default_value;
                            $options['choices'] = $model::lists('title','id');
                        }else{
                            $choices = explode(PHP_EOL,$field->default_value);
                            foreach($choices as $choice){
                                $options['choices'][$choice] = $choice;
                            }

                        }
                        $options['empty_value'] = t('strings.please_select');
                        $options['selected']    =  $field->field_value;
                        $options['multiple'] = false;
                        break;
                    case "selectmultiple":
                        if(class_exists($field->default_value)){
                            $model=$field->default_value;
                            $options['choices'] = $model::lists('title','id');
                        }else{
                            $choices = explode(PHP_EOL,$field->default_value);
                            foreach($choices as $choice){
                                $options['choices'][$choice] = $choice;
                            }
                        }
                        $options['empty_value'] = t('strings.please_select');
                        $options['multiple'] = true;
                        $options['selected'] =  $field->field_value;
                        $field->field_type = 'select';
                        break;
                    case "choice":
                        if(class_exists($field->default_value)){
                            $model=$field->default_value;
                            $options['choices'] = $model::lists('title','id');
                        }else{
                            $choices = explode(PHP_EOL,$field->default_value);
                            foreach($choices as $choice){
                                $options['choices'][$choice] = $choice;
                            }
                        }
                        $options['selected'] =  $field->field_value;
                        $options['expanded'] = true;
                        break;
                    case "checkbox":
                        $options['default_value'] = $field->default_value;
                        $options['checked'] = $field->field_value;
                        break;
                    case "repeated":
                        $options['type'] = $field->default_value;
                        $options['second_name'] = $field->field_value;
                        break;
                    case "range":
                    case "number":
                        if(trim($field->default_value)){
                            $choice = explode("-",$field->default_value);

                            $options['attr']['min'] = $choice[0];
                            $options['attr']['max'] = $choice[1];
                            $options['attr']['step'] = $choice[2];
                        }
                        break;
                    case "date":
                        if(trim($field->default_value)){
                            $choice = explode("-",$field->default_value);

                            $options['attr']['min'] = $choice[0];
                            $options['attr']['max'] = $choice[1];
                        }
                        break;
                }
                if($field->required){
                    $options['attr']['required'] = 'required';
                }
                if($field->field_type=='submit'){
                    if(!$nextbtn){
                        $this->add('forms_field_'.$field->id,$field->field_type,$options);
                    }
                }else{
                    $this->add('forms_field_'.$field->id,$field->field_type,$options);
                }

            }
        }
        \Session::put('honeypot', true);
        $this->add('hn_pt','text',['label'=>false,'attr'=>['class'=>'hidden']]);
        $this->add('form_id','hidden',['default_value'=>$form->id]);

        if($nextbtn){
            $this->add('nextbtn','submit');
        }
    }
}