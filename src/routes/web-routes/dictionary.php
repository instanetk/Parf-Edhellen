<?php

// Dictionary
Route::get('/w/{word}/{language?}',   [ 'uses' => 'BookController@pageForWord' ]);
Route::get('/wt/{id}',                [ 'uses' => 'BookController@pageForGlossId' ])
    ->where([ 'id' => REGULAR_EXPRESSION_NUMERIC ])->name('gloss.ref');
    Route::get('/wt/{id}/latest',     [ 'uses' => 'BookController@redirectToLatest' ])
        ->where([ 'id' => REGULAR_EXPRESSION_NUMERIC ])->name('gloss.ref.latest');
Route::get('/wt/{id}/versions',       [ 'uses' => 'BookController@versions' ])
    ->where([ 'id' => REGULAR_EXPRESSION_NUMERIC ])->name('gloss.ref.version');
