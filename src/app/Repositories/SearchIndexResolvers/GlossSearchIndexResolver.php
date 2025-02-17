<?php

namespace App\Repositories\SearchIndexResolvers;

use App\Repositories\ValueObjects\{
    SearchIndexSearchValue,
    SpecificEntitiesSearchValue
};
use App\Models\Gloss;
use App\Repositories\{
    DiscussRepository,
    GlossRepository,
    SentenceRepository
};
use App\Adapters\BookAdapter;
use App\Helpers\StringHelper;

class GlossSearchIndexResolver implements ISearchIndexResolver
{
    private $_glossRepository;
    private $_sentenceRepository;
    private $_discussRepository;
    private $_bookAdapter;

    public function __construct(GlossRepository $glossRepository, SentenceRepository $sentenceRepository,
        DiscussRepository $discussRepository, BookAdapter $bookAdapter)
    {
        $this->_glossRepository    = $glossRepository;
        $this->_sentenceRepository = $sentenceRepository;
        $this->_discussRepository  = $discussRepository;
        $this->_bookAdapter        = $bookAdapter;
    }

    public function resolve(SearchIndexSearchValue $value): array
    {
        if ($value instanceof SpecificEntitiesSearchValue) {
            $glosses = $this->_glossRepository->getGlosses($value->getIds());
        } else {
            $normalizedWord = StringHelper::normalize($value->getWord(), /* accentsMatter = */ true, /* retainWildcard = */ false);
            $glosses = $this->_glossRepository->getWordGlosses(
                $normalizedWord,
                $value->getLanguageId(),
                $value->getIncludesOld(),
                $value->getSpeechIds(),
                $value->getGlossGroupIds()
            );
        }
        $glossIds = array_map(function ($v) {
            return $v->id;
        }, $glosses);

        $inflections = $value->getIncludesInflections() //
            ? $this->_sentenceRepository->getInflectionsForGlosses($glossIds) //
            : [];
        $comments = $this->_discussRepository->getNumberOfPostsForEntities(Gloss::class, $glossIds);

        return $this->_bookAdapter->adaptGlosses($glosses, $inflections, $comments, $value->getWord());
    }
}
