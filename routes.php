<?php

\Route::group(array('before' => 'isroot','as'=>'root','prefix'=>'backend/forms'),function(){
    Route::get('/','\Plugins\forms\controllers\FormsBackendController@formslist');
    Route::get('list/{status?}','\Plugins\forms\controllers\FormsBackendController@formslist');
    Route::get('new','\Plugins\forms\controllers\FormsBackendController@newform');
    Route::get('settings','\Plugins\forms\controllers\FormsBackendController@settings');
    Route::get('view/{tablename}','\Plugins\forms\controllers\FormsBackendController@viewdbdata');
    Route::get('export/data/{formid}','\Plugins\forms\controllers\FormsBackendController@exportformdata')->where('formid','\d{1,}');
    Route::get('edit/{formid}','\Plugins\forms\controllers\FormsBackendController@edit')->where('formid','\d{1,}');
    Route::get('delete/{formid}','\Plugins\forms\controllers\FormsBackendController@delete')->where('formid','\d{1,}');
    Route::get('restore/{formid}','\Plugins\forms\controllers\FormsBackendController@restore')->where('formid','\d{1,}');


    Route::post('save','\Plugins\forms\controllers\FormsBackendController@save');
    Route::post('savesettings','\Plugins\forms\controllers\FormsBackendController@savesettings');
});

//manage form submitions handled by our plugin
Route::post('forms/sendmail','\Plugins\forms\controllers\FormsFrontendController@sendmail');
Route::post('forms/savetodb','\Plugins\forms\controllers\FormsFrontendController@savetodb');
Route::post('forms/survey','\Plugins\forms\controllers\FormsFrontendController@managesurvey');