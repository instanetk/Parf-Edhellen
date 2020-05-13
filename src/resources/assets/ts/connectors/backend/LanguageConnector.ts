import {
    LocalStorageLanguages,
} from '../../config';

import ExpiringCache, { IDataWithExpiration, TimeUnit } from '../../utilities/ExpiringCache';
import LazyLoader from '../../utilities/LazyLoader';
import BookApiConnector from './BookApiConnector';
import {
    ILanguageEntity,
    ILanguagesResponse,
} from './IBookApi';
import ILanguageApi from './ILanguageApi';

export default class LanguageConnector implements ILanguageApi {
    constructor(private _api: BookApiConnector = new BookApiConnector(),
        private _cache?: LazyLoader<ILanguagesResponse>) {
        if (_cache === undefined) {
            let cache: LazyLoader<ILanguagesResponse> = null;
            try {
                cache = ExpiringCache.withLocalStorage(this._load.bind(this), LocalStorageLanguages);
            } catch (e) {
                console.info(
                    'Falling back to in-memory storage because LanguageConnector ' +
                    `failed to initialize with localStorage: ${e}.`,
                );
                cache = null;
            } finally {
                // fallback - in-memory cache
                if (cache === null) {
                    cache = ExpiringCache.withMemoryStorage(this._load.bind(this), LocalStorageLanguages);
                }
            }

            this._cache = cache;
        }
    }

    /**
     * Gets all languages.
     */
    public all() {
        return this._cache.get();
    }

    /**
     * Finds one, and only one, language that matches the specified `value`.
     * Returns `null` if no languages matches your query.
     * @param value
     * @param key (optional) the property on the language object that contains the expected `value`.
     * @param cmpFunc (optional) comparer; `===` by default.
     */
    public async find<TKey extends keyof ILanguageEntity>(value: ILanguageEntity[TKey], key: TKey,
        cmpFunc = (a: ILanguageEntity[TKey], b: ILanguageEntity[TKey]) => a === b) {
        const categorizedLanguages = await this.all();
        const categories = Object.keys(categorizedLanguages);

        for (const category of categories) {
            const languages = categorizedLanguages[category];
            const language = languages.find((l: ILanguageEntity) => cmpFunc(l[key], value));

            if (typeof language === 'object') {
                return language;
            }
        }

        return null;
    }

    /**
     * Default language loader. Makes a request to the service API.
     */
    private async _load() {
        const languages = await this._api.languages();
        const cacheConfig: IDataWithExpiration<ILanguagesResponse> = {
            data: languages,
            lifetime: 1,
            unit: TimeUnit.Days,
        };

        return cacheConfig;
    }
}
