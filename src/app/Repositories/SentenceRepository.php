<?php

namespace App\Repositories;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Auth\AuthManager;

use App\Events\{
    SentenceCreated,
    SentenceEdited
};
use App\Models\{
    Gloss,
    Keyword,
    Sentence,
    SentenceFragment,
    SentenceFragmentInflectionRel,
    Speech
};
use App\Helpers\{
    SentenceHelper,
    StringHelper
};
use Illuminate\Support\Collection;

class SentenceRepository
{
    private $_keywordRepository;
    /**
     * @var AuthManager
     */
    private $_authManager;

    public function __construct(KeywordRepository $keywordRepository, AuthManager $authManager)
    {
        $this->_keywordRepository = $keywordRepository;
        $this->_authManager = $authManager;
    }

    /**
     * Gets the languages for all available sentences.
     * @return mixed
     */
    public function getLanguages()
    {
        return DB::table('languages as l')
            ->join('sentences as s', 'l.id', '=', 's.language_id')
            ->select('l.name', 'l.id', 'l.description')
            ->distinct()
            ->get();
    }

    /**
     * Gets sentences for the specified language.
     * @return mixed
     */
    public function getByLanguage(int $languageId)
    {
        return DB::table('sentences as s')
            ->leftJoin('accounts as a', 's.account_id', '=', 'a.id')
            ->where('s.is_approved', 1)
            ->where('s.language_id', $languageId)
            ->select('s.id', 's.description', 's.source', 's.is_neologism', 's.account_id',
                'a.nickname as account_name', 's.name')
            ->orderBy('s.name')
            ->get();
    }

    public function getAllGroupedByLanguage()
    {
        return DB::table('sentences as s')
            ->join('languages as l', 's.language_id', 'l.id')
            ->leftJoin('accounts as a', 's.account_id', '=', 'a.id')
            ->where('s.is_approved', 1)
            ->select('s.id', 's.description', 's.source', 's.is_neologism', 's.account_id',
                'a.nickname as account_name', 's.name', 'l.name as language_name')
            ->orderBy('language_name')
            ->orderBy('name')
            ->get()
            ->groupBy('language_name');
    }

    /**
     * Gets inflections for the specified IDs. Returns an associative array
     * keyed with the sentence fragment associated with the inflection.
     *
     * @param number[] $ids
     * @return array
     */
    public function getInflectionsForGlosses(array $ids)
    {
        return DB::table('sentence_fragments as sf')
            ->join('speeches as sp', 'sf.speech_id', 'sp.id')
            ->join('sentences as s', 'sf.sentence_id', 's.id')
            ->join('languages as l', 's.language_id', 'l.id')
            ->leftJoin('sentence_fragment_inflection_rels as r', 'sf.id', 'r.sentence_fragment_id')
            ->leftJoin('inflections as i', 'r.inflection_id', 'i.id')
            ->whereIn('sf.gloss_id', $ids)
            ->select('sf.gloss_id', 'sf.fragment as word', 'i.name as inflection', 'sp.name as speech', 
                'sf.sentence_id', 'sf.id as sentence_fragment_id', 's.name as sentence_name', 'l.name as language_name',
                'l.id as language_id')
            ->orderBy('sf.fragment')
            ->get()
            ->groupBy('sentence_fragment_id')
            ->toArray();
    }

    public function getSentence(int $id)
    {
        $sentence = Sentence::findOrFail($id)
            ->load('account', 'language');

        $fragments = $sentence->sentence_fragments;
        $fragmentIds = $fragments->map(function ($f) {
            return $f->id;
        });

        $inflections = SentenceFragmentInflectionRel::whereIn('sentence_fragment_id', $fragmentIds)
            ->join('inflections', 'inflections.id', 'inflection_id')
            ->select('sentence_fragment_id', 'inflections.name', 'inflections.id as inflection_id')
            ->get()
            ->groupBy('sentence_fragment_id');

        $translations = $sentence->sentence_translations()
            ->select('sentence_number', 'paragraph_number', 'translation')
            ->get()
            ->transform(function ($item) {
                $item->makeHidden('paragraph_number');
                return $item;
            })->mapWithKeys(function ($item) {
                return [ $item->paragraph_number => $item ];
            });

        $sentence->makeHidden(['account_id', 'language_id', 'sentence_translations', 'sentence_fragments']);

        $speeches = $this->getSpeechesForFragments($fragments);
        return [
            'inflections' => $inflections,
            'sentence' => $sentence,
            'sentence_fragments' => $fragments,
            'sentence_translations' => $translations,
            'sentence_transformations' => resolve(SentenceHelper::class)->buildSentences($fragments),
            'speeches' => $speeches
        ];
    }

    public function getSpeechesForFragments(Collection $fragments)
    {
        $speechIds = $fragments->reduce(function ($carry, $f) {
            if ($f->speech_id !== null && !in_array($f->speech_id, $carry)) {
                $carry[] = $f->speech_id;
            }

            return $carry;
        }, []);
        $speeches = count($speechIds) < 1 ? [] : Speech::whereIn('id', $speechIds)
            ->select('id', 'name')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->id => $item->name];
            });
        return $speeches;
    }

    public function saveSentence(Sentence $sentence, array $fragments, array $inflections, array $translations = []) 
    {
        $changed = !! $sentence->id;
        $numberOfFragments = count($fragments);
        if ($numberOfFragments !== count($inflections)) {
            throw new \Exception('The number of fragments must match the number of inflections.');
        }
        
        try {
            DB::beginTransaction();

            $sentence->save();

            // Re-create all sentence fragments
            $this->destroyFragments($sentence);
            for ($i = 0; $i < $numberOfFragments; $i += 1) {
                $fragment = $fragments[$i];
                $fragment->sentence_id = $sentence->id;
                $fragment->save();

                if (! $fragment->type) {
                    try {
                        for ($j = 0; $j < count($inflections[$i]); $j += 1) {
                            $inflectionRel = $inflections[$i][$j];
                            $inflectionRel->sentence_fragment_id = $fragment->id;
                            $inflectionRel->save(); 
                        }
                    } catch (Exception $ex) {
                        throw new Exception(
                            sprintf('Failed to process inflections for "%s" (%i). Inflections: %s',
                                $fragment->fragment, $i, \json_encode($inflections)),
                            0, $ex);
                    }

                    try {
                        $this->_keywordRepository->createKeyword($fragment->gloss->word, $fragment->gloss->sense, 
                            $fragment->gloss, $fragment->fragment, $fragment->id);
                    } catch (Exception $ex) {
                        throw new Exception(sprintf('Failed to save keywords for "%s" (%i).', $fragment->fragment, $i), 0, $ex);
                    }
                }
            }

            // Re-create all sentence translations
            $sentence->sentence_translations()->delete();
            if (count($translations) > 0) {
                $sentence->sentence_translations()->saveMany($translations);
            }

            DB::commit();
        } catch (Exception $ex) {
            DB::rollBack();
            throw $ex;
        }

        // Inform listeners of this change.
        $event = ! $changed 
                ? new SentenceCreated($sentence, $sentence->account_id)
                : new SentenceEdited($sentence, $this->_authManager->user()->id);
        event($event);

        return $sentence;
    }

    public function destroyFragments(Sentence $sentence) 
    {
        foreach ($sentence->sentence_fragments as $fragment) {
            $fragment->inflection_associations()->delete();
            $fragment->keywords()->delete();
            $fragment->delete();
        }
    }

    public function suggestFragmentGlosses(Collection $fragments, int $languageId)
    {
        $distinctFragments = $fragments->filter(function ($f) {
            return $f->type === 0 && $f->gloss_id === 0; // = i.e. words
        })->map(function ($f) {
            return [
                'normalized' => StringHelper::normalize($f->fragment, true),
                'original'   => StringHelper::toLower($f->fragment)
            ];
        })->unique('normalized');

        $maximumFragments = config('ed.sentence_repository_maximum_fragments');
        if ($distinctFragments->count() > $maximumFragments) {
            $distinctFragments->splice(0, $maximumFragments);
        }

        $suggestions = [];
        foreach ($distinctFragments as $f) {
            $inflectionIds = [];
            $glossId = null;
            $speechId = null;

            $fragmentData = Keyword::where('normalized_keyword', $f['normalized'])
                ->whereNotNull('sentence_fragment_id')
                ->join('sentence_fragments', 'sentence_fragments.id', '=', 'keywords.sentence_fragment_id')
                ->where('is_sense', 0)
                ->select('sentence_fragment_id', 'speech_id', 'sentence_fragments.gloss_id')
                ->first();

            if ($fragmentData !== null) {
                $inflectionIds = SentenceFragmentInflectionRel::where('sentence_fragment_id', $fragmentData->sentence_fragment_id) //
                    ->pluck('inflection_id');
                $glossId = $fragmentData->gloss_id;
                $speechId = $fragmentData->speech_id;
            }

            if ($glossId === null) {
                $gloss = Gloss::active()
                    ->join('words', 'words.id', '=', 'glosses.word_id')
                    ->where('language_id', $languageId)
                    ->where('normalized_word', $f['normalized'])
                    ->orderBy('glosses.speech_id', 'desc')
                    ->select('glosses.id', 'glosses.speech_id')
                    ->first();
                
                if ($gloss !== null) {
                    $glossId = $gloss->id;
                    $speechId = $gloss->speech_id;
                }
            }

            if ($glossId !== null) {
                $suggestions[$f['original']] = [
                    'gloss_id' => $glossId,
                    'speech_id' => $speechId,
                    'inflection_ids' => $inflectionIds
                ];
            }
        }

        return $suggestions;
    }
}
