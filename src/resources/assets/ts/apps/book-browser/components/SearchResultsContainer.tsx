import React from 'react';
import { connect } from 'react-redux';

import { IComponentEvent } from '../../../components/Component.types';
import { ISearchResult } from '../reducers/SearchResultsReducer.types';
import SearchResult from './SearchResult';
import { IProps } from './SearchResultsContainer.types';

class SearchResultsContainer extends React.PureComponent<IProps> {
    static get defaultProps() {
        return {
            searchResults: [],
        } as IProps;
    }

    public render() {
        return <ul className="search-result">
            {this.props.searchResults.map((result) => <li key={result.id}>
                <SearchResult searchResult={result} onClick={this._onClick} />
            </li>)}
        </ul>;
    }

    private _onClick = (ev: IComponentEvent<ISearchResult>) => {
        // TODO
        console.log(ev.value);
    }
}

const mapStateToProps = (state: any) => ({
    searchResults: state.searchResults,
});

export default connect(mapStateToProps)(SearchResultsContainer);
