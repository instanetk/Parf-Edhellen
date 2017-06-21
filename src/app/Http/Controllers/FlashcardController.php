<?php

namespace App\Http\Controllers;

use App\Models\{Flashcard, Language, Translation};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FlashcardController extends Controller
{
    public function index(Request $request)
    {
        $flashcards = Flashcard::all();
        return view('flashcard.index', ['flashcards' => $flashcards]);
    }

    public function cards(Request $request, int $id)
    {
        $flashcard = Flashcard::findOrFail($id);
        return view('flashcard.cards', ['flashcard' => $flashcard]);
    }

    public function card(Request $request)
    {
        $this->validate($request, [
            'id'    => 'numeric|exists:flashcards,id',
            'not'   => 'sometimes|array',
            'not.*' => 'sometimes|numeric'
        ]);

        $id = intval( $request->input('id') );
        
        $not = [];
        if ($request->has('not')) {
            $not = $request->input('not');
        }

        // retrieve the flashcard for its language and translation group 
        // which will be used to filter amongst the translations.
        $flashcard = Flashcard::find($id);

        // select a random translation
        $q = Translation::active()
            ->where([
                ['language_id', $flashcard->language_id],
                ['translation_group_id', $flashcard->translation_group_id]
            ])
            ->inRandomOrder();

        // the _not_ list contains reference to previous flash cards, to avoid
        // repetition.
        if (! empty($not)) {
            $q = $q->whereNotIn('id', $not);
        }
        
        // retrieve the random translation or fail (if none exists!)
        $translation = $q->firstOrFail();

        // Compile a list of options
        $options = [$translation->translation];

        $fakeOptions = $q->where([
                ['id', '<>', $translation->id],
                ['translation', '<>', $translation->translation]
            ])
            ->select('translation')
            ->take(4)
            ->get();

        foreach ($fakeOptions as $option) {
            $options[] = $option->translation;
        }

        shuffle($options);

        return [ 
            'word'           => $translation->word->word,
            'options'        => $options,
            'translation_id' => $translation->id 
         ];
    }
}
