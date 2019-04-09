import classNames from 'classnames';
import React, {
    useEffect,
    useRef,
} from 'react';

import DateLabel from '@root/components/DateLabel';
import HtmlInject from '@root/components/HtmlInject';
import { makeVisibleInViewport } from '@root/utilities/func/visual-focus';

import Avatar from './Avatar';
import { IProps } from './Post._types';
import ProfileLink from './ProfileLink';

export function Post(props: IProps) {
    const {
        post,
        renderToolbar,
    } = props;
    const {
        _isFocused: focused,
        _isThreadPost: isThreadPost,
    } = props.post;

    const postRef = useRef(null);

    useEffect(() => {
        if (focused) {
            makeVisibleInViewport(postRef.current);
        }
    }, [ focused, postRef ]);

    return <section className={classNames('forum-post', { 'forum-post--thread': isThreadPost })} ref={postRef}>
        <div className="post-profile-picture">
            <Avatar account={post.account} />
        </div>
        <div className="post-content">
            <header>
                <ProfileLink account={post.account} className="nickname" />
                {renderToolbar && renderToolbar(props)}
            </header>
            <div className="post-body">
                {post.isDeleted
                    ? <em>{post.account.nickname} has redacted their post.</em>
                    : <HtmlInject html={post.content} />}
            </div>
            <footer>
                <DateLabel dateTime={post.createdAt} />
                <a href={`?forum_post_id=${post.id}`} className="post-no">
                    {post.id}
                </a>
            </footer>
        </div>
    </section>;
}

export default Post;
