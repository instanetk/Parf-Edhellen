<?php

// Public unrestricted API for discuss
Route::group([ 
    'namespace' => API_NAMESPACE, 
    'prefix'    => API_PATH.'/discuss'
], function () {
    Route::get('group',           [ 'uses' => 'DiscussApiController@groups' ]);
    Route::get('group/{groupId}', [ 'uses' => 'DiscussApiController@groupAndThreads' ])
        ->where([ 'groupId' => REGULAR_EXPRESSION_NUMERIC ]);
    Route::get('thread', [ 'uses' => 'DiscussApiController@latestThreads' ]);
    Route::get('thread/{threadId}', [ 'uses' => 'DiscussApiController@thread' ])
        ->where([ 'threadId' => REGULAR_EXPRESSION_NUMERIC ]);
    Route::get('thread/resolve/{entityType}/{entityId}', [ 'uses' => 'DiscussApiController@resolveThread' ])
        ->where([
            'entityType' => '[a-z]+',
            'entityId' => REGULAR_EXPRESSION_NUMERIC
        ]);
});
