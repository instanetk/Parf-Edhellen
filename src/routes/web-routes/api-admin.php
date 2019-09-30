<?php

// Admin API
Route::group([ 
    'namespace' => API_NAMESPACE, 
    'prefix'    => API_PATH,
    'middleware' => ['auth', 'auth.require-role:Administrators']
], function () {
    Route::delete('gloss/{id}', [ 'uses' => 'GlossApiController@destroy' ]);

    Route::get('account',       [ 'uses' => 'AccountApiController@index' ]);
    Route::get('account/{id}',  [ 'uses' => 'AccountApiController@getAccount' ]);

    Route::get('book/group',    [ 'uses' => 'BookApiController@getGroups' ]);
});
