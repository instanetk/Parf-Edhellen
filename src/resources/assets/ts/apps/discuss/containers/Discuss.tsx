import React, {
    useCallback,
    useEffect,
    useRef,
    useState,
} from 'react';
import { connect } from 'react-redux';

import { fireEvent } from '@root/components/Component';
import { IComponentEvent } from '@root/components/Component._types';
import Pagination from '@root/components/Pagination';

import DiscussActions from '../actions/DiscussActions';
import Form from '../components/Form';
import Post from '../components/Post';
import RespondButton from '../components/RespondButton';
import { IProps } from '../index._types';
import { RootReducer } from '../reducers';

function Discuss(props: IProps) {
    const [ newPostView, setNewPostView ] = useState(false);
    const postRef = useRef(null);

    const {
        currentPage,
        noOfPages,
        onPageChange,
        pages,
        posts,
        thread,
    } = props;

    useEffect(() => {
        // If the customer wants to respond to the thread, ensure that the component scrolls
        // into view.
        if (newPostView) {
            postRef.current.scrollIntoView({
                block: 'start',
            });
        }
    });

    const onPaginate = useCallback((ev: IComponentEvent<number>) => {
        if (ev.value !== currentPage) {
            // Cancel editing mode
            setNewPostView(false);

            // The component is `null` because `this` reference is finicky for functional components.
            fireEvent(/* component:*/ null, onPageChange, {
                pageNumber: ev.value,
                thread,
            });
        }
    }, [ currentPage, thread, onPageChange ]);

    // Event handler for the "Respond" buttons.
    const onNewPostViewChange = useCallback(() => {
        setNewPostView(! newPostView);
    }, [ newPostView, setNewPostView ]);

    const onPostSubmit = useCallback((ev: IComponentEvent<any>) => {
        
    }, [ thread ]);

    return <>
        {posts.map((post) => <Post post={post} key={post.id} />)}
        <Pagination currentPage={currentPage}
            noOfPages={noOfPages}
            onClick={onPaginate}
            pages={pages}
        />
        <aside ref={postRef}>
            {newPostView
                ? <Form subjectEnabled={false} 
                        onCancel={onNewPostViewChange}
                        onSubmit={onPostSubmit}
                  />
                : <RespondButton onClick={onNewPostViewChange} />}
        </aside>
    </>;
}

const mapStateToProps = (state: RootReducer) => ({
    ...state.pagination,
    posts: state.posts,
    thread: state.thread,
} as Partial<IProps>);

const actions = new DiscussActions();
const mapDispatchToProps = (dispatch: any) => ({
    onPageChange: (ev) => dispatch(actions.thread({
        entityId: ev.value.thread.entityId,
        entityType: ev.value.thread.entityType,
        id: ev.value.thread.id,
        offset: ev.value.pageNumber,
    })),
} as Partial<IProps>);

export default connect(mapStateToProps, mapDispatchToProps)(Discuss);
