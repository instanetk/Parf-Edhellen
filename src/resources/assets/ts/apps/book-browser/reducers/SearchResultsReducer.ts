import {
    Actions,
    ISearchResultAction,
    ISearchResultState,
} from './constants';

const SearchResultsReducer = (state: ISearchResultState[] = [],
    action: ISearchResultAction) => {
    switch (action.type) {
        case Actions.ReceiveSearchResults:
            return [
                ...action.items,
            ];
    }

    return state;
};

export default SearchResultsReducer;
