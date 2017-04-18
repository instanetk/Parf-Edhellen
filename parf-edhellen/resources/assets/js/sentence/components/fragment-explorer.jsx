import React from 'react';
import classNames from 'classnames';
import { connect } from 'react-redux';
import { selectFragment } from '../actions';
import EDFragment from './fragment';
import EDTengwarFragment from './tengwar-fragment';
import EDBookGloss from '../../search/components/book-gloss';
import { Parser as HtmlToReactParser } from 'html-to-react';

class EDFragmentExplorer extends React.Component {
    constructor(props) {
        super(props);
        this.state = {
            fragmentIndex: 0
        };
    }

    /**
     * Component has mounted and initial state retrieved from the server should be applied.
     */
    componentDidMount() {
        let fragmentIndex = 0;
        // Does the shebang specify the fragment ID?
        if (/^#![0-9]+$/.test(window.location.hash)) {
            const fragmentId = parseInt(String(window.location.hash).substr(2), 10);
            if (fragmentId) {
                fragmentIndex = Math.max(this.props.fragments.findIndex(f => f.id === fragmentId), 0);
            }
        }

        // A little hack for causing the first fragment to be highlighted
        this.onNavigate({}, fragmentIndex);
    }

    /**
     * Retrieves the fragment index for the next fragment, or returns the current fragment index
     * if none exists.
     */
    nextFragmentIndex() {
        for (let i = this.state.fragmentIndex + 1; i < this.props.fragments.length; i += 1) {
            const fragment = this.props.fragments[i];

            if (!fragment.interpunctuation) {
                return i;
            }
        }

        return this.state.fragmentIndex;
    }

    /**
     * Retrieves the fragment index for the previous fragment, or returns the current fragment index
     * if none exists.
     */
    previousFragmentIndex() {
        for (let i = this.state.fragmentIndex - 1; i > -1; i -= 1) {
            const fragment = this.props.fragments[i];

            if (!fragment.interpunctuation) {
                return i;
            }
        }

        return this.state.fragmentIndex;
    }

    /**
     * Event handler for the onFragmentClick event.
     * 
     * @param {*} ev 
     */
    onFragmentClick(ev) {
        if (ev.preventDefault) { 
            ev.preventDefault();
        }

        const fragmentIndex = this.props.fragments.findIndex(f => f.id === ev.id);
        if (fragmentIndex === -1) {
            return;
        }

        this.setState({
            fragmentIndex
        });
        window.location.hash = `!${ev.id}`;

        this.props.dispatch(selectFragment(ev.id, ev.translationId));
    }

    /**
     * Navigates to the specified fragment index by receiving it from the array of fragments, and
     * dispatching a select fragment signal.
     * 
     * @param {*} ev 
     * @param {*} fragmentIndex 
     */
    onNavigate(ev, fragmentIndex) {
        if (ev.preventDefault) {
            ev.preventDefault();
        }
    
        const fragment = this.props.fragments[fragmentIndex];
        this.onFragmentClick({
            id: fragment.id,
            translationId: fragment.translationId
        })
    }

    render() {
        let section = null;
        let fragment = null;
        let parser = null;

        if (!this.props.loading && this.props.bookData && this.props.bookData.sections.length > 0) {
            // Comments may contain HTML because it's parsed by the server as markdown. The HtmlToReact parser will
            // examine the HTML and turn it into React components.
            section = this.props.bookData.sections[0];
            fragment = this.props.fragments.find(f => f.id === this.props.fragmentId);
            parser = new HtmlToReactParser();
        }

        const previousIndex = this.previousFragmentIndex();
        const nextIndex = this.nextFragmentIndex();

        return <div className="well ed-fragment-navigator">
            <p className="tengwar ed-tengwar-fragments">
                { this.props.fragments.map(f => <EDTengwarFragment fragment={f}
                                                                   key={`tng${f.id}`}
                                                                   selected={f.id === this.props.fragmentId} />) }
            </p>
            <p className="ed-elvish-fragments">
                { this.props.fragments.map(f => <EDFragment fragment={f}
                                                            key={`frg${f.id}`}
                                                            selected={f.id === this.props.fragmentId}
                                                            onClick={this.onFragmentClick.bind(this)} />) }
            </p>
            <nav>
                <ul className="pager">
                    <li className={classNames('previous', { 'hidden': previousIndex === this.state.fragmentIndex })}>
                        <a href="#" onClick={ev => this.onNavigate(ev, previousIndex)}>← {this.props.fragments[previousIndex].fragment}</a>
                    </li>
                    <li className={classNames('next', { 'hidden': nextIndex === this.state.fragmentIndex })}>
                        <a href="#" onClick={ev => this.onNavigate(ev, nextIndex)}>{this.props.fragments[nextIndex].fragment} →</a>
                    </li>
                </ul>
            </nav>
            {this.props.loading
                ? <div className="sk-spinner sk-spinner-pulse"></div>
                : (section ? (<div>
                {fragment.grammarType ? <div><em>{fragment.grammarType}</em></div> : ''}
                <div>{fragment.comments ? parser.parse(fragment.comments) : ''}</div>
                <hr />
                <div>
                    {section.glosses.map(g => <EDBookGloss gloss={g}
                                                           language={section.language}
                                                           key={g.TranslationID} />)}
                </div>
            </div>) : '')}
        </div>;
    }
}

const mapStateToProps = (state) => {
    return {
        fragments: state.fragments,
        fragmentId: state.fragmentId,
        bookData: state.bookData,
        loading: state.loading
    };
};

export default connect(mapStateToProps)(EDFragmentExplorer);
