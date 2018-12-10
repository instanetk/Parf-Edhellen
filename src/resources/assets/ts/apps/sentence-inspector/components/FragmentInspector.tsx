import React from 'react';
import Loadable from 'react-loadable';

import { fireEvent } from '@root/components/Component';
import { IComponentEvent } from '@root/components/Component._types';
import { IReferenceLinkClickDetails } from '@root/components/HtmlInject._types';
import Spinner from '@root/components/Spinner';
import { ISentenceFragmentEntity } from '@root/connectors/backend/BookApiConnector._types';
import GlobalEventConnector from '@root/connectors/GlobalEventConnector';

import { IProps } from './FragmentInspector._types';

export default class FragmentInspector extends React.PureComponent<IProps> {
    private _globalEvents = new GlobalEventConnector();

    public render() {
        const {
            fragment,
        } = this.props;

        return <aside className="fragment-inspector">
            {fragment ? this._renderFragment(fragment) : this._renderUnknownFragment()}

            <nav aria-label="Fragment navigator">
                <ul className="pager">
                    <li className="previous">
                        <a href="#previous" onClick={this._onPreviousClick}>&larr; Previous</a>
                    </li>
                    <li className="next">
                        <a href="#next" onClick={this._onNextClick}>Next &rarr;</a>
                    </li>
                </ul>
            </nav>
        </aside>;
    }

    private _renderFragment(fragment: ISentenceFragmentEntity) {
        return <article>
            <header>
                <h1>{fragment.fragment}</h1>
            </header>
            <section className="abstract">
                {fragment.comments}
            </section>
            <section>
                <GlossInspector gloss={this.props.gloss}
                    onReferenceLinkClick={this._onReferenceLinkClick}
                    toolbar={false} />
            </section>
        </article>;
    }

    private _renderUnknownFragment() {
        return <span>Unknown fragment...</span>;
    }

    private _onPreviousClick = (ev: React.MouseEvent<HTMLAnchorElement>) => {
        const {
            fragment,
            onFragmentMoveClick,
        } = this.props;

        ev.preventDefault();
        fireEvent(this, onFragmentMoveClick, fragment.previousFragmentId);
    }

    private _onNextClick = (ev: React.MouseEvent<HTMLAnchorElement>) => {
        const {
            fragment,
            onFragmentMoveClick,
        } = this.props;

        ev.preventDefault();
        fireEvent(this, onFragmentMoveClick, fragment.nextFragmentId);
    }

    private _onReferenceLinkClick = (ev: IComponentEvent<IReferenceLinkClickDetails>) => {
        this._globalEvents.fire(this._globalEvents.loadReference, ev.value);
    }
}

const GlossInspector = Loadable({
    loader: () => import('@root/apps/book-browser/components/Gloss'),
    loading: Spinner,
});
