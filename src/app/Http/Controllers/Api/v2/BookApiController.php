<?php

namespace App\Http\Controllers\Api\v2;

use Illuminate\Http\Request;
use Cache;

use App\Http\Controllers\Controller;
use App\Helpers\StringHelper;
use App\Models\{
    Keyword,
    Gloss, 
    GlossGroup, 
    Language,
    Word
};
use App\Http\Controllers\Traits\{
    CanTranslate, 
    CanGetGloss 
};

class BookApiController extends Controller 
{
    use CanTranslate, CanGetGloss { 
        CanTranslate::__construct insteadof CanGetGloss;
        CanTranslate::translate as protected doTranslate; 
    } // ;

    /**
     * HTTP GET. Gets the word which corresponds to the specified ID. 
     *
     * @param Request $request
     * @param int $id
     * @return void
     */
    public function getWord(Request $request, int $id)
    {
        $word = Word::find($id);
        if (! $word) {
            return response(null, 404);
        }

        return $word;
    }

    public function getLanguages()
    {
        $languages = Cache::remember('ed.languages', 60 * 60 /* seconds */, function () {
            return Language::all()
                ->sortBy('order')
                ->sortBy('name')
                ->groupBy('category')
                ->toArray();
        });

        return $languages;
    }

    /**
     * HTTP GET. Gets all available gloss groups.
     *
     * @param Request $request
     * @return void
     */
    public function getGroups(Request $request)
    {
        return GlossGroup::orderBy('name')->get();
    }

    /**
     * HTTP POST. Performs a forward search among words for the specified word parameter.
     *
     * @param Request $request
     * @return void
     */
    public function findWord(Request $request) 
    {
        $this->validate($request, [
            'word' => 'required|string|max:64',
            'max'  => 'sometimes|numeric|min:1'
        ]);

        $normalizedWord = StringHelper::normalize( $request->input('word') );
        $max = intval( $request->input('max') );

        $query = Word::where('normalized_word', 'like', $normalizedWord.'%');

        if ($max > 0) {
            $query = $query->take($max);
        }

        return $query->select('id', 'word')->get();
    }

    /**
     * HTTP POST. Finds keywords for the specified word.
     *
     * @param Request $request
     * @return void
     */
    public function find(Request $request)
    {
        $this->validateBasicRequest($request, [
            'reversed' => 'boolean'
        ]);

        $glossGroupIds = $request->has('gloss_group_ids') ? $request->input('gloss_group_ids') : null;
        $includeOld    = boolval($request->input('include_old'));
        $languageId    = intval($request->input('language_id'));
        $reversed      = $request->input('reversed') === true;
        $speechIds     = $request->has('speech_ids') ? $request->input('speech_ids') : null;
        $word          = StringHelper::normalize( $request->input('word'), /* accentsMatter: */ false, /* retainWildcard: */ true );

        $keywords = $this->_glossRepository->getKeywordsForLanguage($word, $reversed, $languageId, $includeOld,
            $speechIds, $glossGroupIds);
        return $keywords;
    }

    /**
     * HTTP POST. Translates the specified word.
     *
     * @param Request $request
     * @return void
     */
    public function translate(Request $request)
    {
        $this->validateBasicRequest($request, [
            'inflections' => 'sometimes|boolean'
        ]);

        $glossGroupIds = $request->has('gloss_group_ids') ? $request->input('gloss_group_ids') : null;
        $includeOld = $request->has('include_old') ? boolval($request->input('include_old')) : true;
        $inflections = $request->has('inflections') && $request->input('inflections');
        $languageId = $request->has('language_id') ? intval($request->input('language_id')) : 0;
        $speechIds = $request->has('speech_ids') ? $request->input('speech_ids') : null;
        $word = StringHelper::normalize( $request->input('word') );

        return $this->doTranslate($word, $languageId, $inflections, $includeOld, $speechIds, $glossGroupIds);
    }

    /**
     * HTTP GET. Gets the gloss corresponding to the specified ID.
     *
     * @param Request $request
     * @param int $glossId
     * @return void
     */
    public function get(Request $request, int $glossId)
    {
        $gloss = $this->getGloss($glossId);
        if (! $gloss) {
            return response(null, 404);
        }

        return $gloss;
    }

    private function validateBasicRequest(Request $request, array $additional = [])
    {
        $this->validateGetGlossConfiguration($request);
        $this->validate($request, $additional + [
            'word' => 'required|min:1|max:255',
        ]);
    }
}
