import {
    ComponentEventHandler,
} from '@root/components/Component._types';
import {
    IPostEntity,
    IThreadEntity,
} from '@root/connectors/backend/IDiscussApi';

import { IFormChangeData } from './components/Form._types';
import { IThreadMetadataArgs } from './components/toolbar/index._types';
import { IThreadMetadataState } from './reducers/ThreadMetadataReducer._types';
import { ICreatePostAction } from './reducers/ThreadReducer._types';

export interface IProps {
    entityId?: number;
    entityType?: string;
    prefetched?: boolean;

    currentPage?: number;
    jumpPostId?: number;
    newPostContent?: string;
    newPostEnabled?: boolean;
    newPostLoading?: boolean;
    noOfPages?: number;
    pages?: Array<string | number>;
    onExistingPostChange?: ComponentEventHandler<number>;
    onExistingThreadMetadataChange?: ComponentEventHandler<IThreadMetadataArgs>;
    onNewPostChange?: ComponentEventHandler<IFormChangeData>;
    onNewPostCreate?: ComponentEventHandler<void>;
    onNewPostSubmit?: ComponentEventHandler<ICreatePostAction>;
    onNewPostDiscard?: ComponentEventHandler<void>;
    onPageChange?: ComponentEventHandler<IPageChangeEvent>;
    posts?: IPostEntity[];
    thread: IThreadEntity;
    threadMetadata?: IThreadMetadataState;
    threadPostId?: number;
}

interface IPageChangeEvent {
    pageNumber: number;
    thread: IThreadEntity;
}
