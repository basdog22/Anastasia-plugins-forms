<?php
\App::register('\Plugins\forms\Kris\LaravelFormBuilder\FormBuilderServiceProvider');


register_dashboard_widget(array(
    'forms::hooks/widgets/db'
));

require_once plugins_path().'/forms/helpers.php';

register_plugin_install_handler('forms','forms_install');
register_plugin_uninstall_handler('forms','forms_uninstall');

register_footer_items(array(
    'forms::hooks/backend/footer'
));

register_dashboard_sidebar_menu(array(
    'forms::hooks/backend/sidebar'
));

register_navbar_addon_links(array(
    'forms::hooks/backend/menulinks'
));

register_shortcode('form',function($shortcode,$content,$object,$c){
    $form = \Plugins\forms\models\Form::find($shortcode->id);
    return (string) $form;
},'Displays a specific form by id');

register_shortcode('formdata',function($shortcode,$content,$object,$c){
    $form = \Plugins\forms\models\Form::find($shortcode->id);
    $rows = \DB::table($form->action_object)->paginate(get_config_value('paging'));
    return (string) \View::make('forms::frontend/dbdata')->withRows($rows)->withForm($form);
},'Displays form data as a table ');


register_content_block(array(
    'forms_select_form'  =>  array(
        'name'  =>  'forms_select_form',
        'title' =>  t('forms::strings.form'),
        'tpl'   =>  array(
            'forms::blocks/form'=>'default'
        ),
        'model'  =>  '\Plugins\forms\models\Form',
        'action'    => 'find',
        'params'    => 1,
        'params_action' => 'publishedlist',
        'params_args' => array('title', 'id'),
        'params_title'=>0,
        'icon'  =>  'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAABr0lEQVRYhe2UMUvDUBDHD4eCIG5dHETBTcVN6SJt73+Xvk2HzDr4AcRJEaSCoINL0c3VScRP4CBUXJ0FQeqogoKDiNq6vEIML7VNUgvFg5vye/n93yUcUQolIlsAGuFm5u003t8/csuv9lLe5JKFSChP9nn+5X0lF5GtnsoBNHoqbxngL+SRAVR1yiGvichimvLIAADWA9A7gJ1cLjeYtrxVgKoFnjzPm3BCKcidvDEmC+DTAod/KiciYualJqSqcy5GVYtdkRMRATix4E0LptoVue/7GQAvFt785faXzCypye1BtXC9VCqNRdx+L0qcSG4PV+yBi19hRzHzQmw5ERGAWwANEVnpVJ7P54cA3MeWB7bfmzFmuNMAzLwfW070c/sx85mqjrd71vO8GQAfseU2QDV0g8disTgbxZfL5QFVnWfmIwDPieTB7RfqBwCjQbZQKEwC2AVQS/TDBSu4/Rx9LCIjzLwG4Nrx/AvAFTNvAJjuWE70Y/u5uu6YziuAUxFZNsZkY0mbFdp+kc3MdwAORER9388kkgYrsP3SH22bASpdGW27BeC8K6Nts74BsvWEohdBerMAAAAASUVORK5CYII=',
        'multiple'  =>  true,
        'configurable'=> true
    )
));


\Event::listen('forms.collect.actions',function(){
    return array(
        'Forms Plugin' =>  array(
            'forms/sendmail'  =>  t('forms::strings.send_email'),
            'forms/savetodb'  =>  t('forms::strings.save_to_db'),
            'forms/survey'    =>  t('forms::strings.survey')
        )
    );
});

register_content_type(array(
    array(
        'type' => 'forms', //the content type
        'title' => t('forms::strings.forms'), //the title to display
        'slug' => 'forms', //the slug that will be prepend on the item slug. eg: /page/about-us
        'model' => '\Plugins\forms\models\Form', //the model to pull items from
        'controller' => '\Plugins\forms\controllers\FormsFrontendController' //the plugin controller
    ),
));

function get_survey_forms(){
    $forms = \Plugins\forms\models\Form::where('status','=',1)->lists('title','id');
    return $forms;
}

function get_table_columns_list($table){
    $columns = \Schema::getColumnListing($table);
    $new = array();
    foreach($columns as $column){
        $new[$column] = $column;
    }
    return $new;
}

function get_db_submits(){
    $forms = \Plugins\forms\models\Form::where('action','=','forms/savetodb')->where('status','=',1)->get();
    $submits = array();
    foreach($forms as $k=>$v){
        if(starts_with($v->action_object,'form_')){
            $fsubmits = \DB::table($v->action_object)->count();
            $submits[$v->id] = $fsubmits;
        }
    }
    ob_start();
    ?>
    <ul class="nav nav-stacked">
        <?php foreach($submits as $form=>$item):?>
            <?php $form = \Plugins\forms\models\Form::find($form);?>
            <li class="clearfix"><a href="<?php echo url('backend/forms/view')."/". $form->action_object ?>"><?php echo $form->title?> (<?php echo $item?>)</a></li>
        <?php endforeach?>
    </ul>
    <?php
    return ob_get_clean();

}

/**
 * @param $table
 * @return array
 */
function get_table_columns($table){
    $prefix = \DB::getTablePrefix();
    $cols = \DB::select(" SHOW COLUMNS FROM ".$prefix.$table);
    return $cols;
}

function get_db_tables(){
    $tables = array('add_new'=>t('forms::strings.new_db_table'));
    $tables['default'] = '------APP Tables------';
    $dbtables = \DB::select('SHOW TABLES');
    $dbtables = jsonencodedecode($dbtables);
    foreach($dbtables as $k=>$table){
        $table = str_replace(\DB::getTablePrefix(),'',reset($table));
        $tables[$table] = $table;
    }
    return $tables;
}

function forms_install(){
    Schema::create('forms', function($table)
    {
        $table->increments('id');
        $table->string('title');
        $table->string('slug')->unique('forms_slug_unique');
        $table->string('action');
        $table->string('action_object');
        $table->enum('form_method', array('POST', 'GET'));
        $table->text('content', 65535);
        $table->string('success_message');
        $table->string('failure_message');
        $table->boolean('status');
        $table->timestamps();
    });

    Schema::create('form_fields', function($table)
    {
        $table->increments('id');
        $table->integer('form_id')->index('form_id');
        $table->string('label');
        $table->string('field_type');
        $table->string('validator');
        $table->text('field_value');
        $table->string('default_value');
        $table->string('validator_params');
        $table->boolean('required');
        $table->boolean('status');
        $table->timestamps();
    });

    \Settings::create(
        array(
            'namespace' => 'forms',
            'setting_name' => 'wrapper_class',
            'setting_value' => 'form-group',
            'autoload' => 1,
        )
    );
    \Settings::create(
        array(
            'namespace' => 'forms',
            'setting_name' => 'wrapper_error_class',
            'setting_value' => 'has-error',
            'autoload' => 1,
        )
    );
    \Settings::create(
        array(
            'namespace' => 'forms',
            'setting_name' => 'label_class',
            'setting_value' => 'control-label',
            'autoload' => 1,
        )
    );

    \Settings::create(
        array(
            'namespace' => 'forms',
            'setting_name' => 'field_class',
            'setting_value' => 'form-control',
            'autoload' => 1,
        )
    );
    \Settings::create(
        array(
            'namespace' => 'forms',
            'setting_name' => 'help_block_class',
            'setting_value' => 'help-block',
            'autoload' => 1,
        )
    );
    \Settings::create(
        array(
            'namespace' => 'forms',
            'setting_name' => 'error_class',
            'setting_value' => 'text-danger',
            'autoload' => 1,
        )
    );
    \Settings::create(
        array(
            'namespace' => 'forms',
            'setting_name' => 'default_namespace',
            'setting_value' => '',
            'autoload' => 1,
        )
    );
    \Settings::create(
        array(
            'namespace' => 'forms',
            'setting_name' => 'custom_fields',
            'setting_value' => '',
            'autoload' => 1,
        )
    );

    \Settings::create(
        array(
            'namespace' => 'forms_templates',
            'setting_name' => 'mainpage_layout',
            'setting_value' => 'posts_singlepost',
            'autoload' => 1,
        )
    );

    \Settings::create(
        array(
            'namespace' => 'forms_templates',
            'setting_name' => 'form',
            'setting_value' => 'forms::form',
            'autoload' => 1,
        )
    );
    \Settings::create(
        array(
            'namespace' => 'forms_templates',
            'setting_name' => 'text',
            'setting_value' => 'forms::text',
            'autoload' => 1,
        )
    );
    \Settings::create(
        array(
            'namespace' => 'forms_templates',
            'setting_name' => 'textarea',
            'setting_value' => 'forms::textarea',
            'autoload' => 1,
        )
    );
    \Settings::create(
        array(
            'namespace' => 'forms_templates',
            'setting_name' => 'button',
            'setting_value' => 'forms::button',
            'autoload' => 1,
        )
    );
    \Settings::create(
        array(
            'namespace' => 'forms_templates',
            'setting_name' => 'radio',
            'setting_value' => 'forms::radio',
            'autoload' => 1,
        )
    );
    \Settings::create(
        array(
            'namespace' => 'forms_templates',
            'setting_name' => 'checkbox',
            'setting_value' => 'forms::checkbox',
            'autoload' => 1,
        )
    );
    \Settings::create(
        array(
            'namespace' => 'forms_templates',
            'setting_name' => 'select',
            'setting_value' => 'forms::select',
            'autoload' => 1,
        )
    );
    \Settings::create(
        array(
            'namespace' => 'forms_templates',
            'setting_name' => 'choice',
            'setting_value' => 'forms::choice',
            'autoload' => 1,
        )
    );
    \Settings::create(
        array(
            'namespace' => 'forms_templates',
            'setting_name' => 'repeated',
            'setting_value' => 'forms::repeated',
            'autoload' => 1,
        )
    );
    \Settings::create(
        array(
            'namespace' => 'forms_templates',
            'setting_name' => 'child_form',
            'setting_value' => 'forms::child_form',
            'autoload' => 1,
        )
    );
    \Settings::create(
        array(
            'namespace' => 'forms_templates',
            'setting_name' => 'collection',
            'setting_value' => 'forms::collection',
            'autoload' => 1,
        )
    );
    \Settings::create(
        array(
            'namespace' => 'forms_templates',
            'setting_name' => 'static',
            'setting_value' => 'forms::static',
            'autoload' => 1,
        )
    );

}

function forms_get_config(){
    $settings = \Settings::where('namespace','=','forms_templates')->get();
    $new = array();
    foreach($settings as $setting){
        $new[$setting->setting_name] = $setting->setting_value;
    }
    return $new;
}
function forms_get_config_default(){
    $settings = \Settings::where('namespace','=','forms')->get();
    $new = array();
    foreach($settings as $setting){
        $new[$setting->setting_name] = $setting->setting_value;
    }
    return $new;
}

function forms_uninstall(){
    Schema::drop('forms');
    Schema::drop('form_fields');
    $settings = \Settings::where('namespace','=','forms')->orWhere('namespace','=','forms_templates')->get();
    foreach($settings as $setting){
        $setting->delete();
    }
}