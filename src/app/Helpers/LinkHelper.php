<?php

namespace App\Helpers;

use data\entities\AuthProvider;

class LinkHelper
{
    public function author(int $authorId, string $authorName)
    {
        $nickname = StringHelper::normalizeForUrl($authorName);
        if (empty($nickname)) {
            return route('author.profile-without-nickname', [
                'id' => $authorId
            ]);
        }

        return route('author.profile', [
            'id' => $authorId,
            'nickname' => $nickname
        ]);
    }

    public function translation(int $translationId)
    {
        return route('translation.ref', [
            'id' => $translationId
        ]);
    }
    
    public function authRedirect(string $url)
    {
        return route('auth.redirect', [
            'providerName' => $url
        ]);
    }
    
    public function sentencesByLanguage(int $languageId, string $languageName)
    {
        $languageName = StringHelper::normalizeForUrl($languageName);

        return route('sentence.public.language', [
            'langId'   => $languageId,
            'langName' => $languageName
        ]);
    }

    public function sentence(int $languageId, string $languageName, int $sentenceId, string $sentenceName)
    {
        $languageName = StringHelper::normalizeForUrl($languageName);
        $sentenceName = StringHelper::normalizeForUrl($sentenceName);

        return route('sentence.public.sentence', [
            'langId'   => $languageId,
            'langName' => $languageName,
            'sentId'   => $sentenceId,
            'sentName' => $sentenceName
        ]);
    }
}