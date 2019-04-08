import SharedReference from '../../utilities/SharedReference';
import ApiConnector from '../ApiConnector';
import {
    ICreateRequest,
    ICreateResponse,
    IDeleteRequest,
    IDeleteResponse,
    ILikeRequest,
    ILikeResponse,
    IPostRequest,
    IPostResponse,
    IThreadMetadataRequest,
    IThreadMetadataResponse,
    IThreadRequest,
    IThreadResponse,
} from './DiscussApiConnector._types';

export default class DiscussApiConnector {
    constructor(private _api = new SharedReference(ApiConnector)) {
    }

    public thread(payload: IThreadRequest) {
        const params: Partial<IThreadRequest> = {};
        if (payload.offset !== undefined) {
            params.offset = payload.offset;
        }
        if (payload.forumPostId !== undefined) {
            params.forumPostId = payload.forumPostId;
        }

        return this._api.value.get<IThreadResponse>(
            this._makePath('thread/' +
                payload.id || `resolve/${payload.entityType}/${payload.entityId}`,
            ),
            params,
        );
    }

    public threadMetadata(payload: IThreadMetadataRequest) {
        return this._api.value.post<IThreadMetadataResponse>(
            this._makePath('thread/metadata'), payload,
        );
    }

    public post(payload: IPostRequest) {
        return this._api.value.get<IPostResponse>(
            this._makePath(`post/${payload.forumPostId}`),
        );
    }

    public createPost(payload: ICreateRequest) {
        return this._api.value.post<ICreateResponse>(
            this._makePath('store/post'),
            payload,
        );
    }

    public deletePost(payload: IDeleteRequest) {
        return this._api.value.delete<IDeleteResponse>(
            this._makePath(`post/${payload.forumPostId}`),
        );
    }

    public likePost(payload: ILikeRequest) {
        return this._api.value.post<ILikeResponse>(
            this._makePath('store/like'),
            payload,
        );
    }

    private _makePath(path: string) {
        return `discuss/${path}`;
    }
}
