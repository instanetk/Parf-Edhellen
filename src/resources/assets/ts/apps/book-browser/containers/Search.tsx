import React from 'react';
import {
    connect,
} from 'react-redux';

import { IComponentEvent } from '../../../components/Component._types';
import LanguageSelect from '../../../components/LanguageSelect';
import debounce from '../../../utilities/func/debounce';
import SharedReference from '../../../utilities/SharedReference';
import { SearchActions } from '../actions';
import SearchQueryInput from '../components/SearchQueryInput';
import { IRootReducer } from '../reducers';

import {
    IProps,
    IState,
} from './Search._types';

export class SearchQuery extends React.PureComponent<IProps, IState> {

    public state: IState;

    private _actions: SharedReference<SearchActions>;
    private _beginSearch: () => void;

    constructor(props: IProps) {
        super(props);

        this.state = {
            includeOld: props.includeOld,
            languageId: props.languageId,
            reversed: props.reversed,
            word: props.word,
        };

        this._actions = new SharedReference(SearchActions);
        this._beginSearch = debounce(500, this._search);
    }

    public render() {
        return <form onSubmit={this._onSubmit}>
            <div className="row">
                <div className="col-md-12">
                    <SearchQueryInput
                        autoFocus={true}
                        name="query"
                        onChange={this._onQueryChange}
                        onSearchResultNavigate={this._onSearchResultNavigate}
                        tabIndex={1}
                        value={this.state.word}
                    />
                </div>
            </div>
            <div className="row">
                <div className="search-language-select">
                    <label className="inline input-sm">
                        <input checked={this.state.reversed}
                            name="reversed"
                            onChange={this._onReverseChange}
                            type="checkbox"
                        /> Reversed
                    </label>
                    <label className="inline input-sm">
                        <input checked={this.state.includeOld}
                            name="excludeOld"
                            onChange={this._onIncludeOldChange}
                            type="checkbox"
                        /> Old sources
                    </label>
                    <LanguageSelect
                        name="languageId"
                        onChange={this._onLanguageChange}
                        value={this.state.languageId}
                    />
                </div>
            </div>
        </form>;
    }

    private _onQueryChange = (ev: IComponentEvent<string>) => {
        this.setState({
            word: ev.value,
        });

        this._beginSearch();
    }

    private _onReverseChange = (ev: React.ChangeEvent<HTMLInputElement>) => {
        this.setState({
            reversed: ev.target.checked,
        });

        this._beginSearch();
    }

    private _onIncludeOldChange = (ev: React.ChangeEvent<HTMLInputElement>) => {
        this.setState({
            includeOld: ev.target.checked,
        });

        this._beginSearch();
    }

    private _onLanguageChange = (ev: IComponentEvent<number>) => {
        this.setState({
            languageId: ev.value,
        });

        this._beginSearch();
    }

    private _onSearchResultNavigate = (ev: IComponentEvent<number>) => {
        this.props.dispatch(this._actions.value.selectNextResult(ev.value));
    }

    private _onSubmit = (ev: React.FormEvent<HTMLFormElement>) => {
        ev.preventDefault();
    }

    private _search() {
        this.props.dispatch(
            this._actions.value.search(this.state),
        );
    }
}

const mapStateToProps = (state: IRootReducer) => ({
    includeOld: state.search.includeOld,
    languageId: state.search.languageId,
    reversed: state.search.reversed,
    word: state.search.word,
});

export default connect(mapStateToProps)(SearchQuery);
