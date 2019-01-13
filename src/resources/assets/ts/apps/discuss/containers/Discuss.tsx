import React from 'react';

import { IProps } from '../index._types';
import Post from '../components/Post';

export default class Discuss extends React.PureComponent<IProps> {
    public render() {
        const data = this.props.discussData;
        if (!data || ! Array.isArray(data.posts)) {
            return null;
        }

        return data.posts.map((post) => <Post post={post} key={post.id} />);
    }
}
