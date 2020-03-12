import { IAccountEntity } from './IBookApi';

export interface IGlossEntity {
    account?: IAccountEntity;
    accountId?: number;
    childGlossId?: number;
    comments?: string;
    createdAt?: string;
    etymology?: null;
    externalId?: string;
    glossDetails?: IGlossDetail[];
    glossGroup?: IGlossGroup;
    glossGroupId?: number;
    hasDetails?: boolean;
    id?: number;
    isDeleted?: boolean;
    isIndex?: boolean;
    isLatest?: boolean;
    isRejected?: boolean;
    isUncertain?: boolean;
    keywords?: IKeywordEntity[];
    languageId: number;
    originGlossId?: number;
    phonetic?: null;
    sense: ISenseEntity;
    senseId?: number;
    source: string;
    speechId: number;
    tengwar?: string;
    translations: ITranslationEntity[];
    updatedAt?: string;
    word: IWordEntity;
}

export interface IGlossDetail {
    category: string;
    order: number;
    text: string;
}

export interface IGlossGroup {
    createdAt: string;
    externalLinkFormat?: string;
    id: number;
    isCanon?: boolean;
    isOld?: boolean;
    name?: string;
    updatedAt: string;
}

export interface IKeywordEntity {
    id?: number;
    word: string;
}

export interface ISenseEntity {
    createdAt?: string;
    description?: string;
    id?: number;
    updatedAt?: string;
    word: IWordEntity;
}

export interface ITranslationEntity {
    translation: string;
}

export interface IWordEntity {
    accountId?: number;
    createdAt?: string;
    id?: number;
    normalizedWord?: string;
    reversedNormalizedWord?: string;
    updatedAt?: string;
    word: string;
}

export interface IGetGlossResponse {
    gloss: IGlossEntity;
}

export default interface IGlossResourceApi {
    delete(glossId: number, replacementId: number): Promise<void>;
    gloss(glossId: number): Promise<IGlossEntity>;
}
