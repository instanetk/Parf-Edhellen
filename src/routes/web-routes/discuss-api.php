<?php

// Public unrestricted API for discuss
Route::group([ 
    'namespace' => API_NAMESPACE, 
    'prefix'    => API_PATH.'/discuss'
], function () {
    Route::get('group',           [ 'uses' => 'DiscussApiController@getGroups' ]);
    Route::get('group/{groupId}', [ 'uses' => 'DiscussApiController@getGroupAndThreads' ])
        ->where([ 'groupId' => REGULAR_EXPRESSION_NUMERIC ]);
    Route::get('thread', [ 'uses' => 'DiscussApiController@getLatestThreads' ]);
    Route::get('thread/{threadId}', [ 'uses' => 'DiscussApiController@getThread' ])
        ->where([ 'threadId' => REGULAR_EXPRESSION_NUMERIC ]);
    Route::get('thread/resolve/{entityType}/{entityId}', [ 'uses' => 'DiscussApiController@resolveThread' ])
        ->where([
            'entityType' => '[a-z]+',
            'entityId' => REGULAR_EXPRESSION_NUMERIC
        ])
        ->name('discuss.resolve');
    Route::get('post/{postId}', [ 'uses' => 'DiscussApiController@getPost' ])
        ->where([ 'postId' => REGULAR_EXPRESSION_NUMERIC ]);

    Route::post('thread/metadata', [ 'uses' => 'DiscussApiController@getThreadMetadata' ]);
    Route::post('store/post', [ 'uses' => 'DiscussApiController@storePost' ]);
    Route::post('store/like', [ 'uses' => 'DiscussApiController@storeLike' ]);
});
