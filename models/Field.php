<?php

namespace Plugins\forms\models;




class Field extends \Eloquent
{
    use \Translatable;




    /**
     * @var string
     */
    protected $table='form_fields';


    protected $translatable = array('label','field_value','default_value');


    public function form(){
        return $this->belongsTo('\Plugins\forms\models\Form');
    }




    public function save(array $options = array()){

        $this->label = ($this->label)?$this->label:'';
        $this->field_type = ($this->field_type)?$this->field_type:'text';
	    $this->validator = ($this->validator)?$this->validator:'';
	    $this->field_value = ($this->field_value)?$this->field_value:'';
	    $this->default_value = ($this->default_value)?$this->default_value:'';

        return parent::save($options);
    }

}
